<?php
require_once 'includes/config.php';
require_login(); // Ensure user is logged in

$user_id = $_SESSION['id'];
$message = $_GET['msg'] ?? '';

// Fetch user's files
$sql = "SELECT id, original_name, size_bytes, upload_date, is_shared, share_token FROM files WHERE user_id = :user_id ORDER BY upload_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to format file size
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    // 1 << 10 * $pow is the same as pow(1024, $pow) but faster
    $bytes /= (1 << (10 * $pow)); 
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Handle file deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_file_id'])) {
    $file_id_to_delete = $_POST['delete_file_id'];
    
    // 1. Get stored name from DB (crucial for security)
    $sql_fetch = "SELECT stored_name FROM files WHERE id = :id AND user_id = :user_id";
    $stmt_fetch = $pdo->prepare($sql_fetch);
    $stmt_fetch->bindParam(':id', $file_id_to_delete, PDO::PARAM_INT);
    $stmt_fetch->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $file_info = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if ($file_info) {
        $stored_filename = $file_info['stored_name'];
        $filepath = UPLOAD_DIR . $stored_filename;

        // 2. Delete physical file
        if (file_exists($filepath) && unlink($filepath)) {
            // 3. Delete DB record
            $sql_delete = "DELETE FROM files WHERE id = :id AND user_id = :user_id";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->bindParam(':id', $file_id_to_delete, PDO::PARAM_INT);
            $stmt_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            if ($stmt_delete->execute()) {
                header("location: dashboard.php?msg=" . urlencode("File deleted successfully."));
                exit;
            }
        } else {
            // DB record cleanup if physical file is missing
            $sql_delete = "DELETE FROM files WHERE id = :id AND user_id = :user_id";
            $stmt_delete = $pdo->prepare($sql_delete);
            $stmt_delete->bindParam(':id', $file_id_to_delete, PDO::PARAM_INT);
            $stmt_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            // Execute even if file was missing to clean up the metadata
            $stmt_delete->execute(); 
            header("location: dashboard.php?msg=" . urlencode("File deleted (physical file was missing, metadata removed)."));
            exit;
        }
    } else {
        header("location: dashboard.php?msg=" . urlencode("Error: File not found or not owned by user."));
        exit;
    }
}

// Handle file sharing toggle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_share_id'])) {
    $file_id_to_share = $_POST['toggle_share_id'];
    $current_state = $_POST['current_state'] == '1' ? true : false;
    
    if ($current_state) {
        // Unshare: set is_shared to FALSE and share_token to NULL
        $sql = "UPDATE files SET is_shared = FALSE, share_token = NULL WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $file_id_to_share, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
             header("location: dashboard.php?msg=" . urlencode("File unshared successfully."));
             exit;
        }
    } else {
        // Share: set is_shared to TRUE and generate a unique share_token
        $token = bin2hex(random_bytes(16)); // 32-character hex token
        $sql = "UPDATE files SET is_shared = TRUE, share_token = :token WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':id', $file_id_to_share, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            // Need to reload the files data to get the new token for display
            // But since we are redirecting, the next dashboard.php load will fetch the new data
            header("location: dashboard.php?msg=" . urlencode("File shared. Share Token: " . $token));
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drive Lite - Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background-color: #333; color: white; padding: 10px 20px; border-radius: 5px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #d9534f; }
        .header a:hover { background-color: #c9302c; }

        .upload-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .upload-box h3 { margin-top: 0; color: #4CAF50; }
        .upload-box input[type="file"] { padding: 10px; border: 1px solid #ccc; border-radius: 4px; display: inline-block; }
        .upload-box button { padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; }
        .upload-box button:hover { background-color: #45a049; }

        .file-list { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .file-list h3 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; }
        .download-btn { background-color: #5bc0de; color: white; }
        .download-btn:hover { background-color: #31b0d5; }
        .delete-btn { background-color: #d9534f; color: white; }
        .delete-btn:hover { background-color: #c9302c; }
        .share-btn { background-color: #f0ad4e; color: white; }
        .unshare-btn { background-color: #5cb85c; color: white; }

        .alert-message { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
        <a href="logout.php">Logout</a>
    </div>

    <?php 
    // Display alert message if present in the URL
    if (!empty($message)): 
        $class = strpos($message, 'ERROR:') !== false ? 'error' : 'success';
    ?>
        <p class="alert-message <?php echo $class; ?>"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <!-- File Upload Section -->
    <div class="upload-box">
        <h3>Upload a New File (Max: <?php echo defined('MAX_FILE_SIZE') ? formatBytes(MAX_FILE_SIZE) : '10 MB'; ?>)</h3>
        <form action="upload_handler.php" method="post" enctype="multipart/form-data">
            <input type="file" name="uploaded_file" required>
            <button type="submit">Upload File</button>
        </form>
    </div>

    <!-- File List Section -->
    <div class="file-list">
        <h3>My Files</h3>
        <table>
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Upload Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($files) > 0): ?>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['original_name']); ?></td>
                            <td><?php echo formatBytes($file['size_bytes']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($file['upload_date'])); ?></td>
                            <td>
                                <?php if ($file['is_shared']): ?>
                                    <span style="color: green;">Shared</span>
                                <?php else: ?>
                                    <span style="color: red;">Private</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Download Button -->
                                <a href="download.php?id=<?php echo $file['id']; ?>" class="action-btn download-btn">Download</a>
                                
                                <!-- Share/Unshare Button -->
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to change sharing status?');">
                                    <input type="hidden" name="toggle_share_id" value="<?php echo $file['id']; ?>">
                                    <input type="hidden" name="current_state" value="<?php echo $file['is_shared'] ? '1' : '0'; ?>">
                                    <?php if ($file['is_shared']): ?>
                                        <button type="submit" class="action-btn unshare-btn">Unshare</button>
                                        <button type="button" class="action-btn share-btn" onclick="alert('Share Link: <?php echo 'http://localhost/drive-lite/share.php?token=' . htmlspecialchars($file['share_token']); ?>');">Get Link</button>
                                    <?php else: ?>
                                        <button type="submit" class="action-btn share-btn">Share</button>
                                    <?php endif; ?>
                                </form>

                                <!-- Delete Button -->
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this file? This cannot be undone.');">
                                    <input type="hidden" name="delete_file_id" value="<?php echo $file['id']; ?>">
                                    <button type="submit" class="action-btn delete-btn">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No files uploaded yet. Start uploading!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    // Simple script to remove the message query parameter after showing the alert
    // to prevent resubmission on refresh.
    if (window.history.replaceState) {
        let url = new URL(window.location.href);
        url.searchParams.delete('msg');
        window.history.replaceState({path: url.href}, '', url.href);
    }
    </script>
</body>
</html>

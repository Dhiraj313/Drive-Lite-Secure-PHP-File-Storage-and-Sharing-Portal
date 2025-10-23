<?php
require_once 'includes/config.php';
require_login(); // Only logged-in users can download their own files

// The user ID from the session is used to enforce file ownership
$user_id = $_SESSION['id']; 
$file_id = $_GET['id'] ?? null; // Get the file ID from the URL parameter

if (empty($file_id)) {
    // Stop execution if the file ID is missing
    die("Error: File ID is missing.");
}

// 1. Fetch file data and verify ownership
// We use both ID and user_id to prevent a user from downloading someone else's file
$sql = "SELECT original_name, stored_name, mime_type, size_bytes FROM files WHERE id = :id AND user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $file_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    // If no record is found, it means the file doesn't exist or doesn't belong to the user
    die("Error: File not found or you do not have permission to access it.");
}

// 2. Define the full path to the stored file
// UPLOAD_DIR is defined in includes/config.php
$filepath = UPLOAD_DIR . $file['stored_name'];

// 3. Check if the physical file exists on the server disk
if (!file_exists($filepath)) {
    die("Error: Physical file is missing from the server.");
}

// 4. Set HTTP headers to force the browser to download the file
// This is critical for security and functionality.
header('Content-Description: File Transfer');
// Set the correct MIME type (e.g., application/pdf)
header('Content-Type: ' . $file['mime_type']); 
// Tell the browser to download it, and suggest the original filename
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
// Provide the file size
header('Content-Length: ' . $file['size_bytes']); 
header('Cache-Control: must-revalidate');
header('Pragma: public');
flush(); // Flush system output buffer to ensure headers are sent

// 5. Read the file from the disk and output its contents directly to the browser
readfile($filepath);
exit;
?>

<?php
require_once 'includes/config.php';
require_login();

// Check if a file was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["uploaded_file"])) {
    
    $file = $_FILES["uploaded_file"];
    $user_id = $_SESSION['id'];
    
    // 1. Check for upload errors
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $error = "File upload failed with error code: " . $file["error"];
    } 
    // 2. Check file size
    elseif ($file["size"] > MAX_FILE_SIZE) {
        $error = "File size exceeds the limit of " . formatBytes(MAX_FILE_SIZE) . ".";
    } 
    // 3. Check MIME type against the ALLOWED_TYPES constant
    elseif (!in_array($file["type"], ALLOWED_TYPES)) {
        $allowed_list = implode(', ', ALLOWED_TYPES);
        $error = "File type '{$file["type"]}' is not allowed. Allowed types: {$allowed_list}";
    } 
    else {
        // --- Upload successful, proceed with saving ---
        
        // Sanitize the original file name
        $original_name = basename($file["name"]);

        // Generate a secure, unique filename (to prevent path traversal/overwrite)
        // We use the unique user ID + a unique string + the original file extension
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $stored_name = uniqid($user_id . '_', true) . '.' . $file_extension;
        $target_filepath = UPLOAD_DIR . $stored_name;

        // 4. Move the uploaded file from temp location
        if (move_uploaded_file($file["tmp_name"], $target_filepath)) {
            
            // 5. Insert file metadata into the database
            $sql = "INSERT INTO files (user_id, original_name, stored_name, mime_type, size_bytes) VALUES (:user_id, :original_name, :stored_name, :mime_type, :size_bytes)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':original_name', $original_name, PDO::PARAM_STR);
            $stmt->bindParam(':stored_name', $stored_name, PDO::PARAM_STR);
            $stmt->bindParam(':mime_type', $file["type"], PDO::PARAM_STR);
            $stmt->bindParam(':size_bytes', $file["size"], PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Success: Redirect to dashboard with a success message
                header("location: dashboard.php?msg=" . urlencode("File uploaded successfully."));
                exit;
            } else {
                // Database error: Clean up the physical file and display error
                $error = "Database error: Could not save file metadata.";
                unlink($target_filepath); 
            }
        } else {
            // Error moving file (usually permissions issue with the uploads directory)
            $error = "Could not move uploaded file to target directory. Check permissions on " . UPLOAD_DIR;
        }
    }
}

// If an error occurred during any step, redirect back to the dashboard with the error message
if (isset($error)) {
    header("location: dashboard.php?msg=" . urlencode("ERROR: " . $error));
    exit;
}

// Fallback for non-POST requests (shouldn't happen)
header("location: dashboard.php");
exit;
?>

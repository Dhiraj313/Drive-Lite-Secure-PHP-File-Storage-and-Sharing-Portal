<?php
require_once 'includes/config.php';
// IMPORTANT: DO NOT REQUIRE LOGIN HERE. This is for public sharing.

$share_token = $_GET['token'] ?? null;

if (empty($share_token) || strlen($share_token) !== 32) {
    die("Error: Invalid or missing share token.");
}

// 1. Fetch file data using the share token
$sql = "SELECT original_name, stored_name, mime_type, size_bytes FROM files WHERE share_token = :token AND is_shared = TRUE";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':token', $share_token, PDO::PARAM_STR);
$stmt->execute();
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    die("Error: File is not shared or the token is incorrect.");
}

$filepath = UPLOAD_DIR . $file['stored_name'];

// 2. Check if the physical file exists
if (!file_exists($filepath)) {
    die("Error: Physical file is missing from the server.");
}

// 3. Force download headers (same as download.php)
header('Content-Description: File Transfer');
header('Content-Type: ' . $file['mime_type']);
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Content-Length: ' . $file['size_bytes']);
header('Cache-Control: must-revalidate');
header('Pragma: public');
flush(); // Flush system output buffer

// 4. Read the file and output it to the browser
readfile($filepath);
exit;
?>

<?php
// Start the session at the very beginning of every script that uses session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------
// DATABASE CONFIGURATION
// ------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'drive_lite_db');
define('DB_USER', 'root'); // Default XAMPP user
define('DB_PASS', '');     // Default XAMPP password (no password)

// Establish PDO connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, display error and stop execution
    die("Database connection failed: " . $e->getMessage());
}

// ------------------------------------
// APPLICATION CONFIGURATION
// ------------------------------------

// Directory where uploaded files will be stored. Must have write permissions!
// Ensure this path is correct for your XAMPP installation.
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Maximum file size allowed (10 MB in bytes)
define('MAX_FILE_SIZE', 10 * 1024 * 1024); 

// Allowed MIME types for file uploads
define('ALLOWED_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif', // Added GIF support as well
    'image/avif', // <-- NEW: Added AVIF image format
    'application/pdf',
    'text/plain',
    'application/zip',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' // .xlsx
]);

// ------------------------------------
// UTILITY FUNCTIONS
// ------------------------------------

/**
 * Checks if a user is logged in. If not, redirects to the login page.
 */
function require_login() {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        // Redirect to login page
        header("location: index.php");
        exit;
    }
}

/**
 * Ensures the upload directory exists. If not, attempts to create it.
 */
function ensure_upload_dir() {
    if (!is_dir(UPLOAD_DIR)) {
        // Attempt to create the directory recursively
        if (!mkdir(UPLOAD_DIR, 0777, true)) {
            // Handle error if directory cannot be created
            error_log("Failed to create upload directory: " . UPLOAD_DIR);
            return false;
        }
    }
    return true;
}

// Ensure the directory is created when the app starts
ensure_upload_dir();
?>

<h1>Drive-Lite: Secure PHP File Storage and Sharing Portal</h1>

Drive-Lite is a simple, self-hosted web application built with PHP (PDO) and MySQL that provides secure user authentication and file management capabilities. It's a great project for mastering core PHP best practices.

✨ **Key Features
**
>> User Authentication: Secure registration and login using PHP sessions and strong password hashing (password_verify/password_hash).
>> Secure File Storage: Files are stored outside the public web root (/uploads/ directory) to prevent direct access.
>> Unique Filenames: Files are renamed upon upload with a unique, user-specific name to prevent file collisions and path traversal attacks.
>> File Metadata: File information (original name, size, type) is stored securely in the MySQL database.
>> Secure Downloads: Downloads are handled by a PHP script that verifies user ownership before delivering the file.
>> Public Sharing: Users can toggle file sharing on/off, generating a unique, temporary token link for public access.
>> File Deletion: Securely deletes both the database record and the physical file from the server.

💻 **Installation and Setup Instructions
**
This guide assumes you are using** XAMPP on Windows for your local environment**.

**_1. Prerequisites_**
Local Web Server: XAMPP (or MAMP for Mac users).
Code Editor: VS Code is recommended.

**_2. Project Setup_**
Start Services: Open your XAMPP Control Panel and start the Apache and MySQL services.
Locate Web Root: Navigate to the C:\xampp\htdocs\ directory.
Clone or Copy Project: Place the entire project folder inside htdocs and name the folder drive-lite.
Project Path: C:\xampp\htdocs\drive-lite\
Create Uploads Directory: Ensure the necessary storage folder exists:
Create an empty folder named uploads inside the drive-lite directory: C:\xampp\htdocs\drive-lite\uploads\

**_3. Database Configuration (MySQL)_**
We will use phpMyAdmin to set up the database.
Access phpMyAdmin: Open your browser and go to http://localhost/phpmyadmin/.
Create Database:
Click New on the left sidebar.
Name the database drive_lite_db and click Create.
Create Tables: With drive_lite_db selected, go to the SQL tab and run the following queries sequentially.
**Table 1:** users

CREATE TABLE users (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

**Table 2:** files

CREATE TABLE files (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    mime_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT NOT NULL,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_shared BOOLEAN DEFAULT FALSE,
    share_token VARCHAR(32) UNIQUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

**_4. Database Credentials Check_**
The application is configured to use default XAMPP/MAMP credentials. If you changed your MySQL password, you must update it in includes/config.php:
// Database Credentials (Check and update if necessary)
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', ''); // <-- Change this if your MySQL has a password

**_5. Running the Application_**
Open your browser and navigate to the project entry point:
$$\text{http://localhost/drive-lite/}$$
You will be redirected to the Login/Registration page.
Create a new user account to register.
Log in, and you will be taken to the dashboard.

**_6. File Upload Testing (Important)_**
The current configuration only allows specific file types and a maximum size of 10MB.
Allowed Types: image/jpeg, image/png, application/pdf, text/plain, application/zip.
To test a successful upload, ensure you use a file that matches one of these allowed MIME types (e.g., a small .png file).

_If you need to change the allowed file types or size, edit the $allowed_types array or MAX_FILE_SIZE constant in includes/config.php._

<img width="800" height="431" alt="Screenshot 2025-10-23 235632" src="https://github.com/user-attachments/assets/918a2d8e-213b-40a6-99f1-6931b34ea569" />
<img width="781" height="460" alt="Screenshot 2025-10-23 235641" src="https://github.com/user-attachments/assets/e8afe297-c1b4-46aa-b293-a59de1441a60" />
<img width="1899" height="416" alt="Screenshot 2025-10-24 000141" src="https://github.com/user-attachments/assets/85d23677-c502-4733-85e4-634adee9eb06" />
<img width="1912" height="545" alt="Screenshot 2025-10-24 000500" src="https://github.com/user-attachments/assets/b3ac31ef-f8dd-4e5e-9f63-16dcf2c44296" />
<img width="515" height="221" alt="Screenshot 2025-10-24 000547" src="https://github.com/user-attachments/assets/ca469b3f-4a40-44fc-97a5-40fb50af5744" />


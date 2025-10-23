<?php
require_once 'includes/config.php';

$error = '';
$message = '';

// Check if user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        // --- Registration Logic ---
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $error = "Please fill all fields.";
        } elseif (strlen($password) < 6) {
            $error = "Password must have at least 6 characters.";
        } else {
            $sql = "SELECT id FROM users WHERE username = :username OR email = :email";
            if ($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(":username", $username, PDO::PARAM_STR);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() > 0) {
                        $error = "This username or email is already taken.";
                    } else {
                        // Insert new user
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)";
                        if ($stmt = $pdo->prepare($sql)) {
                            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
                            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                            $stmt->bindParam(":password_hash", $password_hash, PDO::PARAM_STR);
                            if ($stmt->execute()) {
                                $message = "Registration successful. You can now log in!";
                            } else {
                                $error = "Something went wrong. Please try again later.";
                            }
                        }
                    }
                }
                unset($stmt);
            }
        }
    } elseif ($action === 'login') {
        // --- Login Logic ---
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter email and password.";
        } else {
            $sql = "SELECT id, username, password_hash FROM users WHERE email = :email";
            if ($stmt = $pdo->prepare($sql)) {
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() == 1) {
                        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $id = $row["id"];
                            $username = $row["username"];
                            $hashed_password = $row["password_hash"];
                            if (password_verify($password, $hashed_password)) {
                                // Password is correct, start a new session
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $username;
                                header("location: dashboard.php");
                            } else {
                                $error = "Invalid email or password.";
                            }
                        }
                    } else {
                        $error = "Invalid email or password.";
                    }
                }
                unset($stmt);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Drive Lite - Login/Register</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { display: flex; gap: 40px; padding: 20px; }
        .form-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); width: 300px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .input-group input[type="text"], .input-group input[type="email"], .input-group input[type="password"] {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .btn { width: 100%; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background-color: #45a049; }
        .error { color: #d9534f; text-align: center; margin-bottom: 15px; }
        .message { color: #5cb85c; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Login Form -->
        <div class="form-box">
            <h2>Login</h2>
            <?php if (!empty($error) && $action === 'login') echo "<p class='error'>$error</p>"; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="login">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Log In</button>
            </form>
        </div>

        <!-- Registration Form -->
        <div class="form-box">
            <h2>Register</h2>
            <?php if (!empty($error) && $action === 'register') echo "<p class='error'>$error</p>"; ?>
            <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="register">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Register</button>
            </form>
        </div>
    </div>
</body>
</html>

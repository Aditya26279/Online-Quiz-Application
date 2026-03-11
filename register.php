<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            $error = "Username is already taken.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'user')");
            try {
                $stmt->execute(['username' => $username, 'password' => $hashed_password]);
                $success = "Registration successful! You can now login.";
            } catch (PDOException $e) {
                $error = "Registration failed. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Quiz</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: #1e293b; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 24px 38px 3px rgb(0 0 0 / 0.14), 0 9px 46px 8px rgb(0 0 0 / 0.12); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 2rem; color: #38bdf8; font-size: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-size: 0.875rem; }
        input[type="text"], input[type="password"] { width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #334155; background-color: #0f172a; color: #f8fafc; box-sizing: border-box; transition: border-color 0.2s; outline: none; }
        input[type="text"]:focus, input[type="password"]:focus { border-color: #38bdf8; }
        button { width: 100%; padding: 0.875rem; border: none; border-radius: 6px; background-color: #0ea5e9; color: white; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background-color 0.3s; margin-top: 1rem; }
        button:hover { background-color: #0284c7; }
        .error { color: #fca5a5; background-color: #7f1d1d; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; font-size: 0.875rem; }
        .success { color: #86efac; background-color: #14532d; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; font-size: 0.875rem; }
        .links { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #94a3b8; }
        .links a { color: #38bdf8; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .links a:hover { color: #7dd3fc; }
    </style>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <form action="register.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Create Account</button>
        </form>
        <div class="links">
            <p>Already have an account? <a href="login.php">Log in</a></p>
            <p><a href="index.php">Back to Home</a></p>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            session_regenerate_id();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Quiz</title>
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
        .links { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #94a3b8; }
        .links a { color: #38bdf8; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .links a:hover { color: #7dd3fc; }
    </style>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Welcome Back</h2>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Sign In</button>
        </form>
        <div class="links">
            <p>Don't have an account? <a href="register.php">Register now</a></p>
            <p><a href="index.php">Back to Home</a></p>
        </div>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>

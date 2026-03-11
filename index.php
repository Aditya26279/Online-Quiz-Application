<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Online Quiz Application</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; margin: 0; text-align: center; overflow: hidden; }
        .bg-glow { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(14,165,233,0.15) 0%, rgba(15,23,42,0) 70%); top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: -1; pointer-events: none;}
        h1 { font-size: 4rem; margin-bottom: 1rem; color: transparent; background: linear-gradient(90deg, #38bdf8, #818cf8); -webkit-background-clip: text; background-clip: text; line-height: 1.2; font-weight: 800; }
        p { font-size: 1.25rem; color: #94a3b8; margin-bottom: 3rem; max-width: 600px; line-height: 1.6; }
        .btn-group { display: flex; gap: 1.5rem; position: relative; z-index: 10; }
        a.btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.875rem 2rem; text-decoration: none; border-radius: 9999px; font-weight: 600; font-size: 1.125rem; transition: all 0.3s ease; }
        .btn-primary { background-color: #0ea5e9; color: white; box-shadow: 0 4px 14px 0 rgba(14, 165, 233, 0.39); }
        .btn-primary:hover { background-color: #0284c7; box-shadow: 0 6px 20px rgba(14, 165, 233, 0.23); transform: translateY(-2px); }
        .btn-secondary { background-color: rgba(30, 41, 59, 0.5); border: 1px solid #334155; color: #e2e8f0; backdrop-filter: blur(10px); }
        .btn-secondary:hover { background-color: rgba(30, 41, 59, 0.8); border-color: #475569; transform: translateY(-2px); }
    </style>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="bg-glow"></div>
    <h1>Master Your Knowledge</h1>
    <p>Dive into interactive quizzes, track your progress, and challenge yourself with our state-of-the-art learning platform.</p>
    <div class="btn-group">
        <a href="login.php" class="btn btn-primary">Sign In</a>
        <a href="register.php" class="btn btn-secondary">Create Account</a>
    </div>
    <script src="assets/js/main.js"></script>
</body>
</html>

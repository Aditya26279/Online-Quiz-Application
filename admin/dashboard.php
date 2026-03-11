<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch some quick stats
$stats = [];
try {
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
    $stats['quizzes'] = $pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();
    $stats['questions'] = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    $stats['attempts'] = $pdo->query("SELECT COUNT(*) FROM user_responses")->fetchColumn();
} catch (PDOException $e) {
    $error = "Error fetching stats: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quiz App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --surface: #1e293b;
            --primary: #38bdf8;
            --primary-hover: #0ea5e9;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --danger: #ef4444;
            --success: #22c55e;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; display: flex; min-height: 100vh; }
        
        /* Sidebar layout */
        .sidebar { width: 250px; background-color: var(--surface); border-right: 1px solid var(--border); padding: 1.5rem 0; display: flex; flex-direction: column; }
        .sidebar-header { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; }
        .sidebar-header h2 { color: var(--primary); margin: 0; font-size: 1.5rem; font-weight: 700; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-links li { margin-bottom: 0.5rem; }
        .nav-links a { display: block; padding: 0.75rem 1.5rem; color: var(--text-muted); text-decoration: none; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-links a:hover { background-color: rgba(56, 189, 248, 0.1); color: var(--primary); }
        .nav-links a.active { border-left-color: var(--primary); color: var(--primary); background-color: rgba(56, 189, 248, 0.05); }
        .user-info { padding: 1.5rem; border-top: 1px solid var(--border); font-size: 0.875rem; color: var(--text-muted); }
        .logout-btn { display: inline-block; margin-top: 0.5rem; color: var(--danger); text-decoration: none; font-weight: 600; }
        
        /* Main Content */
        .main-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 2rem; font-weight: 700; color: white; }
        
        /* Dashboard Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .stat-card { background-color: var(--surface); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-4px); border-color: var(--primary); }
        .stat-title { color: var(--text-muted); font-size: 0.875rem; font-weight: 600; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: white; margin: 0; line-height: 1; }
        
        /* Recent Activity / Quick actions area */
        .card { background-color: var(--surface); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1.5rem; }
        .card h3 { margin-top: 0; margin-bottom: 1rem; font-size: 1.25rem; color: white; border-bottom: 1px solid var(--border); padding-bottom: 1rem; }
        
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; text-decoration: none; border: none; }
        .btn-primary { background-color: var(--primary); color: #01111e; }
        .btn-primary:hover { background-color: var(--primary-hover); }
    </style>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>QuizMaster</h2>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="quizzes.php">Manage Quizzes</a></li>
            <li><a href="users.php">View Users</a></li>
        </ul>
        <div class="user-info">
            Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <br>
            <a href="../logout.php" class="logout-btn">Log out</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>Overview</h1>
            <a href="quizzes.php" class="btn btn-primary">+ Create New Quiz</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div style="background:var(--danger);color:white;padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo $stats['users'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Quizzes</div>
                <div class="stat-value"><?php echo $stats['quizzes'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Questions</div>
                <div class="stat-value"><?php echo $stats['questions'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Quiz Attempts</div>
                <div class="stat-value"><?php echo $stats['attempts'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h3>Quick Start</h3>
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 1.5rem;">
                Welcome to the admin panel. From here, you can manage the entire application.
                Start by creating a new quiz and adding some multiple-choice questions to it.
                You can also view the performance of your users from the "View Users" tab.
            </p>
            <a href="quizzes.php" class="btn btn-primary">Go to Quizzes</a>
        </div>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>

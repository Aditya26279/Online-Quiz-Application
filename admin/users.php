<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch all standard users (not admins)
$message = '';

if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'user'");
        $stmt->execute(['id' => $del]);
        $message = "User deleted successfully.";
    } catch (PDOException $e) {
        $message = "Error deleting user.";
    }
}

try {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.created_at,
               COUNT(ur.id) as attempts,
               IFNULL(MAX(ur.taken_at), '-') as last_active
        FROM users u
        LEFT JOIN user_responses ur ON u.id = ur.user_id
        WHERE u.role = 'user'
        GROUP BY u.id
        ORDER BY attempts DESC, u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching users: ". $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #0f172a; --surface: #1e293b; --primary: #38bdf8; --primary-hover: #0ea5e9; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background-color: var(--surface); border-right: 1px solid var(--border); padding: 1.5rem 0; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; }
        .sidebar-header h2 { color: var(--primary); margin: 0; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-links a { display: block; padding: 0.75rem 1.5rem; color: var(--text-muted); text-decoration: none; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-links a:hover { background-color: rgba(56, 189, 248, 0.1); color: var(--primary); }
        .nav-links a.active { border-left-color: var(--primary); color: var(--primary); background-color: rgba(56, 189, 248, 0.05); }
        
        .main-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
        .header { margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 2rem; color: white; }
        
        .table-container { background-color: var(--surface); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 1rem; border-bottom: 1px solid var(--border); }
        th { background-color: rgba(15, 23, 42, 0.5); font-weight: 600; color: var(--text-muted); font-size: 0.875rem; text-transform: uppercase; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: rgba(255, 255, 255, 0.02); }

        .btn-danger { color: var(--danger); text-decoration: none; font-size: 0.875rem; }
        .btn-danger:hover { text-decoration: underline; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background-color: rgba(56, 189, 248, 0.1); border: 1px solid var(--primary); color: var(--primary); }
    </style>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>QuizMaster</h2>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="quizzes.php">Manage Quizzes</a></li>
            <li><a href="users.php" class="active">View Users</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>User Activity</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Joined Date</th>
                        <th style="text-align:center;">Quiz Attempts</th>
                        <th>Last Activity</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--text-muted);">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td style="text-align:center;">
                                    <span style="background:var(--bg-color);padding:2px 8px;border-radius:12px;font-size:0.8rem;">
                                        <?php echo $user['attempts']; ?>
                                    </span>
                                </td>
                                <td><?php echo $user['last_active'] !== '-' ? date('M d, Y H:i', strtotime($user['last_active'])) : '-'; ?></td>
                                <td style="text-align:right;">
                                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn-danger" onclick="return confirm('Are you sure? This will delete the user and all their quiz results.');">Ban/Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>

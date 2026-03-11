<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$action = $_GET['action'] ?? 'list';

// Handle form submission (Create or Edit Quiz)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $quiz_id = $_POST['quiz_id'] ?? null;
    
    if (empty($title)) {
        $message = "Quiz title cannot be empty.";
    } else {
        try {
            if ($quiz_id) { // Update
                $stmt = $pdo->prepare("UPDATE quizzes SET title = :title, description = :description WHERE id = :id");
                $stmt->execute(['title' => $title, 'description' => $description, 'id' => $quiz_id]);
                $message = "Quiz updated successfully.";
            } else { // Create
                $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, created_by) VALUES (:title, :description, :created_by)");
                $stmt->execute(['title' => $title, 'description' => $description, 'created_by' => $_SESSION['user_id']]);
                $message = "Quiz created successfully.";
            }
            $action = 'list'; // Go back to list after success
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Delete Quiz
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        $message = "Quiz deleted successfully.";
        $action = 'list';
    } catch (PDOException $e) {
        $message = "Error deleting quiz: " . $e->getMessage();
    }
}

// Fetch all quizzes for listing
$quizzes = [];
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT q.id, q.title, q.created_at, u.username as creator,
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as q_count
        FROM quizzes q
        LEFT JOIN users u ON q.created_by = u.id
        ORDER BY q.created_at DESC
    ");
    $quizzes = $stmt->fetchAll();
}

// Fetch single quiz data if editing
$edit_quiz = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $edit_quiz = $stmt->fetch();
    if (!$edit_quiz) $action = 'list'; // fallback if not found
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Admin Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #0f172a; --surface: #1e293b; --primary: #38bdf8; --primary-hover: #0ea5e9; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --danger: #ef4444; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; display: flex; min-height: 100vh; }
        
        /* Sidebar layout */
        .sidebar { width: 250px; background-color: var(--surface); border-right: 1px solid var(--border); padding: 1.5rem 0; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; }
        .sidebar-header h2 { color: var(--primary); margin: 0; font-size: 1.5rem; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-links a { display: block; padding: 0.75rem 1.5rem; color: var(--text-muted); text-decoration: none; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-links a:hover { background-color: rgba(56, 189, 248, 0.1); color: var(--primary); }
        .nav-links a.active { border-left-color: var(--primary); color: var(--primary); background-color: rgba(56, 189, 248, 0.05); }
        .user-info { padding: 1.5rem; border-top: 1px solid var(--border); font-size: 0.875rem; color: var(--text-muted); }
        
        /* Main Content */
        .main-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 2rem; }
        
        /* Utilities */
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; text-decoration: none; border: none; }
        .btn-primary { background-color: var(--primary); color: #01111e; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: transparent; border: 1px solid var(--border); color: var(--text-main); }
        .btn-secondary:hover { background-color: var(--surface); border-color: var(--text-muted); }
        .btn-danger { background-color: transparent; color: var(--danger); }
        .btn-danger:hover { text-decoration: underline; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background-color: rgba(56, 189, 248, 0.1); border: 1px solid var(--primary); color: var(--primary); }
        
        /* Table Styles */
        .table-container { background-color: var(--surface); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 1rem; border-bottom: 1px solid var(--border); }
        th { background-color: rgba(15, 23, 42, 0.5); font-weight: 600; color: var(--text-muted); font-size: 0.875rem; text-transform: uppercase; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: rgba(255, 255, 255, 0.02); }
        
        /* Form Styles */
        .form-card { background-color: var(--surface); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; max-width: 600px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem; }
        .form-control { width: 100%; padding: 0.75rem; background-color: var(--bg-color); border: 1px solid var(--border); color: var(--text-main); border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .empty-state { padding: 3rem; text-align: center; color: var(--text-muted); }
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
            <li><a href="quizzes.php" class="active">Manage Quizzes</a></li>
            <li><a href="users.php">View Users</a></li>
        </ul>
        <div class="user-info">Logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></div>
    </aside>

    <main class="main-content">
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="header">
                <h1>Quizzes</h1>
                <a href="quizzes.php?action=create" class="btn btn-primary">+ Create Quiz</a>
            </div>
            
            <div class="table-container">
                <?php if (empty($quizzes)): ?>
                    <div class="empty-state">No quizzes found. Create your first one!</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th style="text-align:center;">Questions</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($quiz['title']); ?></strong></td>
                                <td style="text-align:center;"><span style="background:var(--bg-color);padding:2px 8px;border-radius:12px;font-size:0.8rem;"><?php echo $quiz['q_count']; ?></span></td>
                                <td><?php echo htmlspecialchars($quiz['creator']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($quiz['created_at'])); ?></td>
                                <td style="text-align:right;">
                                    <a href="questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-secondary btn-sm" style="margin-right: 0.5rem;">Manage Questions</a>
                                    <a href="quizzes.php?action=edit&id=<?php echo $quiz['id']; ?>" class="btn btn-secondary btn-sm" style="margin-right: 0.5rem;">Edit</a>
                                    <a href="quizzes.php?delete=<?php echo $quiz['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this quiz? This will also delete all associated questions and attempts.');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <div class="header">
                <h1><?php echo $action === 'edit' ? 'Edit Quiz' : 'Create New Quiz'; ?></h1>
                <a href="quizzes.php" class="btn btn-secondary">Back to List</a>
            </div>
            
            <div class="form-card">
                <form method="POST" action="quizzes.php">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="quiz_id" value="<?php echo $edit_quiz['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Quiz Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required 
                               value="<?php echo htmlspecialchars($edit_quiz['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" class="form-control"><?php echo htmlspecialchars($edit_quiz['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Quiz'; ?></button>
                </form>
            </div>
        <?php endif; ?>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>

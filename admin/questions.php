<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$quiz_id = $_GET['quiz_id'] ?? null;
$action = $_GET['action'] ?? 'list';

if (!$quiz_id) {
    header("Location: quizzes.php");
    exit;
}

// Ensure the quiz exists
$stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = :id");
$stmt->execute(['id' => $quiz_id]);
$quiz = $stmt->fetch();
if (!$quiz) {
    header("Location: quizzes.php");
    exit;
}

// Handle Form Submission (Create or Edit Question)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $opt_a = trim($_POST['option_a']);
    $opt_b = trim($_POST['option_b']);
    $opt_c = trim($_POST['option_c']);
    $opt_d = trim($_POST['option_d']);
    $correct = $_POST['correct_option'];
    $q_id = $_POST['question_id'] ?? null;

    if (empty($question_text) || empty($opt_a) || empty($opt_b) || empty($opt_c) || empty($opt_d) || empty($correct)) {
        $message = "Please fill all fields and select a correct option.";
    } else {
        try {
            if ($q_id) { // Update
                $stmt = $pdo->prepare("UPDATE questions SET question_text=:qt, option_a=:oa, option_b=:ob, option_c=:oc, option_d=:od, correct_option=:correct WHERE id=:id AND quiz_id=:quiz_id");
                $stmt->execute(['qt'=>$question_text, 'oa'=>$opt_a, 'ob'=>$opt_b, 'oc'=>$opt_c, 'od'=>$opt_d, 'correct'=>$correct, 'id'=>$q_id, 'quiz_id'=>$quiz_id]);
                $message = "Question updated successfully.";
            } else { // Create
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (:q_id, :qt, :oa, :ob, :oc, :od, :correct)");
                $stmt->execute(['q_id'=>$quiz_id, 'qt'=>$question_text, 'oa'=>$opt_a, 'ob'=>$opt_b, 'oc'=>$opt_c, 'od'=>$opt_d, 'correct'=>$correct]);
                $message = "Question added to quiz successfully.";
                $action = 'list'; // Go back to list after success
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM questions WHERE id = :id AND quiz_id = :quiz_id");
        $stmt->execute(['id' => $del_id, 'quiz_id' => $quiz_id]);
        $message = "Question deleted.";
        $action = 'list';
    } catch (PDOException $e) {
        $message = "Error deleting question.";
    }
}

// Fetch all questions for listing
$questions = [];
if ($action === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :id ORDER BY id ASC");
    $stmt->execute(['id' => $quiz_id]);
    $questions = $stmt->fetchAll();
}

// Fetch single question data if editing
$edit_q = null;
if ($action === 'edit' && isset($_GET['q_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = :id AND quiz_id = :quiz_id");
    $stmt->execute(['id' => $_GET['q_id'], 'quiz_id' => $quiz_id]);
    $edit_q = $stmt->fetch();
    if (!$edit_q) $action = 'list';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #0f172a; --surface: #1e293b; --primary: #38bdf8; --primary-hover: #0ea5e9; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --danger: #ef4444; --success: #22c55e;}
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; display: flex; min-height: 100vh; }
        
        /* Shared Sidebar */
        .sidebar { width: 250px; background-color: var(--surface); border-right: 1px solid var(--border); padding: 1.5rem 0; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 0 1.5rem 1.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem; }
        .sidebar-header h2 { color: var(--primary); margin: 0; }
        .nav-links { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .nav-links a { display: block; padding: 0.75rem 1.5rem; color: var(--text-muted); text-decoration: none; font-weight: 500; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-links a:hover { background-color: rgba(56, 189, 248, 0.1); color: var(--primary); }
        .nav-links a.active { border-left-color: var(--primary); color: var(--primary); background-color: rgba(56, 189, 248, 0.05); }
        
        .main-content { flex-grow: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem; }
        .header-bg { margin: 0; font-size: 2rem; }
        .header-sub { color: var(--text-muted); font-size: 1rem; margin-top: 0.25rem; }
        
        /* Utilities */
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; text-decoration: none; border: none; }
        .btn-primary { background-color: var(--primary); color: #01111e; }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-secondary { background-color: transparent; border: 1px solid var(--border); color: var(--text-main); }
        .btn-secondary:hover { background-color: var(--surface); border-color: var(--text-muted); }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        .btn-danger { color: var(--danger); background: transparent; }
        .btn-danger:hover { text-decoration:underline; }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background-color: rgba(56, 189, 248, 0.1); border: 1px solid var(--primary); color: var(--primary); }
        
        /* Card styles for questions */
        .q-card { background-color: var(--surface); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 1rem; }
        .q-text { font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; line-height: 1.5; }
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; }
        .option { background-color: var(--bg-color); padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); font-size: 0.875rem; display: flex; flex-direction:column; gap:4px; }
        .opt-label { color:var(--text-muted); font-weight:600; font-size:0.7rem; text-transform:uppercase; }
        .is-correct { border-color: var(--success); background-color: rgba(34, 197, 94, 0.05); }
        .is-correct .opt-label { color: var(--success); }
        
        .q-actions { border-top: 1px solid var(--border); padding-top: 1rem; display: flex; justify-content: flex-end; gap: 0.5rem; }

        /* Form Styles */
        .form-card { background-color: var(--surface); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; max-width: 800px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-weight: 500; font-size: 0.875rem; }
        .form-control { width: 100%; padding: 0.75rem; background-color: var(--bg-color); border: 1px solid var(--border); color: var(--text-main); border-radius: 6px; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { resize: vertical; min-height: 80px; }
        
        .radio-group { display: flex; gap: 1rem; margin-top: 0.5rem; }
        .radio-item { display:flex; align-items:center; gap:0.25rem; cursor:pointer; }
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
    </aside>

    <main class="main-content">
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="header">
                <div>
                    <h1 class="header-bg">Questions Overview</h1>
                    <div class="header-sub">Quiz: <?php echo htmlspecialchars($quiz['title']); ?></div>
                </div>
                <div>
                    <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>&action=create" class="btn btn-primary">+ Add New Question</a>
                    <a href="quizzes.php" class="btn btn-secondary" style="margin-left:0.5rem;">Back to Quizzes</a>
                </div>
            </div>

            <?php if (empty($questions)): ?>
                <div style="padding: 3rem; text-align: center; color: var(--text-muted);">No questions yet. <br><br> <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>&action=create" class="btn btn-primary">Add Question</a></div>
            <?php else: ?>
                <?php foreach ($questions as $index => $q): ?>
                    <div class="q-card">
                        <div class="q-text">Q<?php echo $index + 1; ?>: <?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>
                        <div class="options-grid">
                            <div class="option <?php echo $q['correct_option'] === 'a' ? 'is-correct' : ''; ?>">
                                <span class="opt-label">Option A</span>
                                <span><?php echo htmlspecialchars($q['option_a']); ?></span>
                            </div>
                            <div class="option <?php echo $q['correct_option'] === 'b' ? 'is-correct' : ''; ?>">
                                <span class="opt-label">Option B</span>
                                <span><?php echo htmlspecialchars($q['option_b']); ?></span>
                            </div>
                            <div class="option <?php echo $q['correct_option'] === 'c' ? 'is-correct' : ''; ?>">
                                <span class="opt-label">Option C</span>
                                <span><?php echo htmlspecialchars($q['option_c']); ?></span>
                            </div>
                            <div class="option <?php echo $q['correct_option'] === 'd' ? 'is-correct' : ''; ?>">
                                <span class="opt-label">Option D</span>
                                <span><?php echo htmlspecialchars($q['option_d']); ?></span>
                            </div>
                        </div>
                        <div class="q-actions">
                            <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>&action=edit&q_id=<?php echo $q['id']; ?>" class="btn btn-secondary btn-sm">Edit Question</a>
                            <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>&delete=<?php echo $q['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this question?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        
        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <div class="header">
                <div>
                    <h1 class="header-bg"><?php echo $action === 'edit' ? 'Edit Question' : 'Add Question'; ?></h1>
                    <div class="header-sub">Quiz: <?php echo htmlspecialchars($quiz['title']); ?></div>
                </div>
                <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>

            <div class="form-card">
                <form method="POST" action="questions.php?quiz_id=<?php echo $quiz_id; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="question_id" value="<?php echo $edit_q['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="question_text">Question Text *</label>
                        <textarea id="question_text" name="question_text" class="form-control" required><?php echo htmlspecialchars($edit_q['question_text'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                        <div class="form-group">
                            <label for="option_a">Option A *</label>
                            <input type="text" id="option_a" name="option_a" class="form-control" required value="<?php echo htmlspecialchars($edit_q['option_a'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="option_b">Option b *</label>
                            <input type="text" id="option_b" name="option_b" class="form-control" required value="<?php echo htmlspecialchars($edit_q['option_b'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="option_c">Option C *</label>
                            <input type="text" id="option_c" name="option_c" class="form-control" required value="<?php echo htmlspecialchars($edit_q['option_c'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="option_d">Option D *</label>
                            <input type="text" id="option_d" name="option_d" class="form-control" required value="<?php echo htmlspecialchars($edit_q['option_d'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group" style="border-top: 1px solid var(--border); padding-top:1.5rem;">
                        <label>Which option is correct? *</label>
                        <div class="radio-group" style="color:white;">
                            <?php $curr_opt = $edit_q['correct_option'] ?? 'a'; ?>
                            <label class="radio-item"><input type="radio" name="correct_option" value="a" <?php echo $curr_opt === 'a' ? 'checked' : ''; ?>> A</label>
                            <label class="radio-item"><input type="radio" name="correct_option" value="b" <?php echo $curr_opt === 'b' ? 'checked' : ''; ?>> B</label>
                            <label class="radio-item"><input type="radio" name="correct_option" value="c" <?php echo $curr_opt === 'c' ? 'checked' : ''; ?>> C</label>
                            <label class="radio-item"><input type="radio" name="correct_option" value="d" <?php echo $curr_opt === 'd' ? 'checked' : ''; ?>> D</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width:100%;"><?php echo $action === 'edit' ? 'Update Question' : 'Save Question'; ?></button>
                </form>
            </div>
        <?php endif; ?>

    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>

<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$quiz_id = $_GET['id'] ?? null;
if (!$quiz_id) {
    header("Location: dashboard.php");
    exit;
}

// Fetch quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id");
$stmt->execute(['id' => $quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    echo "Quiz not found.";
    exit;
}

// Fetch all questions for this quiz
$stmt = $pdo->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE quiz_id = :id ORDER BY id ASC");
$stmt->execute(['id' => $quiz_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    die("This quiz has no questions yet.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #0f172a; --surface: #1e293b; --primary: #38bdf8; --primary-hover: #0ea5e9; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --bg-select: rgba(56, 189, 248, 0.1); }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        
        .navbar { background-color: var(--surface); border-bottom: 1px solid var(--border); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .navbar h1 { margin: 0; font-size: 1.25rem; font-weight: 600; }
        .btn-cancel { color: var(--text-muted); text-decoration: none; font-size: 0.875rem; border: 1px solid var(--border); padding: 0.5rem 1rem; border-radius: 6px; transition: all 0.2s; }
        .btn-cancel:hover { background-color: var(--surface); color: white; border-color: white;}

        .main-content { padding: 3rem 1rem; max-width: 800px; margin: 0 auto; width: 100%; box-sizing: border-box; flex-grow: 1; }
        
        .quiz-header { text-align: center; margin-bottom: 3rem; }
        .quiz-header h2 { font-size: 2.5rem; margin: 0 0 1rem 0; color: white; }
        .quiz-header p { color: var(--text-muted); font-size: 1.125rem; line-height: 1.6; }
        .question-count { display: inline-block; background-color: var(--surface); padding: 0.5rem 1rem; border-radius: 999px; font-size: 0.875rem; color: var(--primary); font-weight: 500; margin-top: 1rem; border: 1px solid var(--border); }

        .question-card { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .q-number { font-size: 0.875rem; color: var(--primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem; display: block; }
        .q-text { font-size: 1.25rem; font-weight: 500; margin: 0 0 1.5rem 0; line-height: 1.5; color: white; }
        
        .options-list { display: flex; flex-direction: column; gap: 0.75rem; }
        
        /* Custom Radio Buttons / Options styling */
        .option-label { display: flex; align-items: center; padding: 1rem 1.25rem; border: 2px solid var(--border); border-radius: 8px; cursor: pointer; transition: all 0.2s; background-color: var(--bg-color); }
        .option-label:hover { border-color: var(--primary); background-color: var(--bg-select); }
        
        /* Hidden default radio */
        .option-label input[type="radio"] { display: none; }
        
        /* Custom radio circle */
        .radio-circle { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--text-muted); margin-right: 1rem; position: relative; flex-shrink: 0; transition: all 0.2s; }
        
        /* Checked state styles */
        .option-label input[type="radio"]:checked + .radio-circle { border-color: var(--primary); }
        .option-label input[type="radio"]:checked + .radio-circle::after { content: ''; position: absolute; width: 10px; height: 10px; background-color: var(--primary); border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .option-label:has(input[type="radio"]:checked) { border-color: var(--primary); background-color: var(--bg-select); color: white; }
        
        .option-text { font-size: 1rem; line-height: 1.4; }

        .submit-section { text-align: center; margin-top: 3rem; margin-bottom: 2rem; }
        .btn-submit { background-color: var(--primary); color: #01111e; font-size: 1.125rem; font-weight: 600; padding: 1rem 3rem; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 14px 0 rgba(14, 165, 233, 0.39); }
        .btn-submit:hover { background-color: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14, 165, 233, 0.23); }
    </style>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <h1>QuizMaster</h1>
        <a href="dashboard.php" class="btn-cancel">Cancel Quiz</a>
    </nav>

    <main class="main-content">
        <div class="quiz-header">
            <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
            <?php if (!empty($quiz['description'])): ?>
                <p><?php echo htmlspecialchars($quiz['description']); ?></p>
            <?php endif; ?>
            <div class="question-count"><?php echo count($questions); ?> Questions</div>
        </div>

        <form action="result.php" method="POST">
            <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
            
            <?php foreach ($questions as $index => $q): ?>
                <div class="question-card">
                    <span class="q-number">Question <?php echo $index + 1; ?> of <?php echo count($questions); ?></span>
                    <h3 class="q-text"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></h3>
                    
                    <div class="options-list">
                        <!-- Option A -->
                        <label class="option-label">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="a" required>
                            <div class="radio-circle"></div>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_a']); ?></span>
                        </label>
                        
                        <!-- Option B -->
                        <label class="option-label">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="b" required>
                            <div class="radio-circle"></div>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_b']); ?></span>
                        </label>
                        
                        <!-- Option C -->
                        <label class="option-label">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="c" required>
                            <div class="radio-circle"></div>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_c']); ?></span>
                        </label>
                        
                        <!-- Option D -->
                        <label class="option-label">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="d" required>
                            <div class="radio-circle"></div>
                            <span class="option-text"><?php echo htmlspecialchars($q['option_d']); ?></span>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="submit-section">
                <button type="submit" class="btn-submit">Submit Quiz</button>
            </div>
        </form>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>

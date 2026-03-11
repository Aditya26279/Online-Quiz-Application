<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quiz_id'])) {
    header("Location: dashboard.php");
    exit;
}

$quiz_id = (int)$_POST['quiz_id'];
$user_id = $_SESSION['user_id'];

// Get all correct answers for this quiz
$stmt = $pdo->prepare("SELECT id, correct_option FROM questions WHERE quiz_id = :quiz_id");
$stmt->execute(['quiz_id' => $quiz_id]);
$correct_answers = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Gets an associative array: [question_id => correct_option]

$total_questions = count($correct_answers);
if ($total_questions === 0) {
    die("Error processing this quiz.");
}

$score = 0;
// Compare submitted answers with correct ones
foreach ($correct_answers as $q_id => $correct_opt) {
    if (isset($_POST["q_$q_id"])) {
        if ($_POST["q_$q_id"] === $correct_opt) {
            $score++;
        }
    }
}

// Save the result to user_responses
try {
    $stmt = $pdo->prepare("INSERT INTO user_responses (user_id, quiz_id, score, total_questions) VALUES (:uid, :qid, :score, :total)");
    $stmt->execute([
        'uid' => $user_id,
        'qid' => $quiz_id,
        'score' => $score,
        'total' => $total_questions
    ]);
} catch (PDOException $e) {
    die("Error saving result.");
}

// Calculate percentage
$percentage = ($total_questions > 0) ? round(($score / $total_questions) * 100) : 0;

// Determine feedback message
$feedback = "";
if ($percentage >= 90) { $feedback = "Excellent work!"; }
elseif ($percentage >= 70) { $feedback = "Good job!"; }
elseif ($percentage >= 50) { $feedback = "You passed, but there's room to grow."; }
else { $feedback = "Keep studying and try again!"; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #0f172a; --surface: #1e293b; --primary: #38bdf8; --primary-hover: #0ea5e9; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --success: #22c55e;}
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; position: relative; overflow: hidden;}
        
        .bg-glow { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(14,165,233,0.1) 0%, rgba(15,23,42,0) 70%); top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: -1; pointer-events: none;}
        
        .result-card { background-color: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 4rem 3rem; text-align: center; max-width: 500px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); z-index: 10; position: relative; }
        
        .result-title { font-size: 1.5rem; font-weight: 600; color: var(--text-muted); margin: 0 0 2rem 0; text-transform: uppercase; letter-spacing: 0.1em; }
        
        .score-circle { width: 160px; height: 160px; border-radius: 50%; border: 8px solid var(--primary); margin: 0 auto 2rem auto; display: flex; flex-direction: column; align-items: center; justify-content: center; background-color: var(--bg-color); position: relative; }
        .score-points { font-size: 3rem; font-weight: 800; line-height: 1; color: white; display: flex; align-items: baseline; gap: 4px; }
        .score-points span { font-size: 1.5rem; color: var(--text-muted); font-weight: 600; }
        .score-perc { font-size: 1.125rem; font-weight: 500; color: var(--primary); margin-top: 0.25rem; }
        
        .feedback { font-size: 1.5rem; font-weight: 700; margin: 0 0 2.5rem 0; color: white; line-height: 1.4; }
        
        .btn-home { display: inline-flex; align-items: center; justify-content: center; background-color: var(--primary); color: #01111e; padding: 1rem 2rem; border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 1.125rem; transition: all 0.3s; box-shadow: 0 4px 14px 0 rgba(14, 165, 233, 0.39); }
        .btn-home:hover { background-color: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14, 165, 233, 0.23); }

        /* Confetti pseudo-effect for high scores */
        <?php if ($percentage >= 70): ?>
        .score-circle { border-color: var(--success); box-shadow: 0 0 30px rgba(34, 197, 94, 0.2); }
        .score-perc { color: var(--success); }
        .bg-glow { background: radial-gradient(circle, rgba(34,197,94,0.1) 0%, rgba(15,23,42,0) 70%); }
        <?php endif; ?>
    </style>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="bg-glow"></div>
    <div class="result-card">
        <h1 class="result-title">Quiz Completed</h1>
        
        <div class="score-circle">
            <div class="score-points"><?php echo $score; ?><span>/<?php echo $total_questions; ?></span></div>
            <div class="score-perc"><?php echo $percentage; ?>%</div>
        </div>
        
        <div class="feedback"><?php echo htmlspecialchars($feedback); ?></div>
        
        <a href="dashboard.php" class="btn-home">Return to Dashboard</a>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>

<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's summary stats
$stats = [];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_responses WHERE user_id = :id");
    $stmt->execute(['id' => $user_id]);
    $stats['attempts'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT AVG(score/total_questions * 100) FROM user_responses WHERE user_id = :id AND total_questions > 0");
    $stmt->execute(['id' => $user_id]);
    $stats['avg_score'] = round($stmt->fetchColumn() ?: 0, 1);
} catch (PDOException $e) {
    // Handle error silently or log it
    $stats['attempts'] = 0;
    $stats['avg_score'] = 0;
}

// Fetch available quizzes with question counts
$quizzes = [];
try {
    $stmt = $pdo->query("
        SELECT q.id, q.title, q.description, 
               (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as q_count
        FROM quizzes q
        HAVING q_count > 0 -- Only show quizzes that have at least 1 question
        ORDER BY q.created_at DESC
    ");
    $quizzes = $stmt->fetchAll();
} catch (PDOException $e) {}

// Fetch past results for this user
$results = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.score, r.total_questions, r.taken_at, q.title 
        FROM user_responses r
        JOIN quizzes q ON r.quiz_id = q.id
        WHERE r.user_id = :uid
        ORDER BY r.taken_at DESC
        LIMIT 5
    ");
    $stmt->execute(['uid' => $user_id]);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Quiz App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --bg-color: #0f172a; --surface: #1e293b; --primary: #38bdf8; --primary-hover: #0ea5e9; --text-main: #f8fafc; --text-muted: #94a3b8; --border: #334155; --success: #22c55e;}
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* Top Navigation */
        .navbar { background-color: var(--surface); border-bottom: 1px solid var(--border); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; color: var(--primary); text-decoration: none; }
        .nav-links { display: flex; align-items: center; gap: 1.5rem; }
        .nav-item { color: var(--text-muted); text-decoration: none; font-weight: 500; transition: color 0.2s; }
        .nav-item:hover, .nav-item.active { color: var(--text-main); }
        .user-menu { display: flex; align-items: center; gap: 1rem; border-left: 1px solid var(--border); padding-left: 1.5rem; }
        .logout-btn { color: #fca5a5; text-decoration: none; font-size: 0.875rem; font-weight: 500; }
        .logout-btn:hover { text-decoration: underline; }

        .main-content { padding: 2.5rem 2rem; max-width: 1200px; margin: 0 auto; width: 100%; box-sizing: border-box; flex-grow: 1; }
        
        /* Greeting Area */
        .greeting { margin-bottom: 2rem; }
        .greeting h1 { margin: 0; font-size: 2.5rem; background: linear-gradient(to right, #38bdf8, #818cf8); -webkit-background-clip: text; color: transparent; font-weight: 800; }
        .greeting p { color: var(--text-muted); font-size: 1.125rem; margin-top: 0.5rem; }

        /* Stats Row */
        .stats-row { display: flex; gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .stat-value { font-size: 2.5rem; font-weight: 700; color: white; line-height: 1; margin-bottom: 0.5rem; }
        .stat-label { color: var(--text-muted); font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }

        /* Two columns layout for Quizzes and Results */
        .grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        @media (max-width: 900px) { .grid-layout { grid-template-columns: 1fr; } }
        
        .section-title { font-size: 1.25rem; font-weight: 600; margin: 0 0 1.5rem 0; color: white; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; }

        /* Quiz List */
        .quiz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .quiz-card { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .quiz-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); border-color: var(--primary); }
        .quiz-title { font-size: 1.25rem; font-weight: 600; margin: 0 0 0.5rem 0; color: white; }
        .quiz-desc { color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem; line-height: 1.5; flex-grow: 1; }
        .quiz-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.75rem; color: var(--text-muted); }
        .meta-tag { background-color: rgba(15,23,42,0.5); padding: 0.25rem 0.75rem; border-radius: 999px; }
        .btn-start { display: block; width: 100%; text-align: center; background-color: var(--primary); color: #01111e; padding: 0.75rem; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background-color 0.2s; box-sizing: border-box; }
        .btn-start:hover { background-color: var(--primary-hover); }

        /* Past Results List */
        .results-list { display: flex; flex-direction: column; gap: 1rem; }
        .result-item { background-color: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .res-info h4 { margin: 0 0 0.25rem 0; font-size: 1rem; color: white; }
        .res-date { font-size: 0.75rem; color: var(--text-muted); }
        .res-score { font-size: 1.25rem; font-weight: 700; color: var(--success); }
        .res-perc { font-size: 0.75rem; color: var(--text-muted); display: block; text-align: right; }
    </style>
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">QuizMaster</a>
        <div class="user-menu">
            <span style="font-weight: 500;">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="logout-btn">Log out</a>
        </div>
    </nav>

    <main class="main-content">
        <div class="greeting">
            <h1>Ready to learn?</h1>
            <p>Pick a quiz below and test your knowledge.</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['attempts']; ?></div>
                <div class="stat-label">Total Quizzes Taken</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['avg_score']; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>

        <div class="grid-layout">
            <div>
                <h2 class="section-title">Available Quizzes</h2>
                <?php if (empty($quizzes)): ?>
                    <div style="background-color: var(--surface); padding: 3rem; text-align: center; border-radius: 12px; color: var(--text-muted);">
                        No quizzes currently available. Please check back later!
                    </div>
                <?php else: ?>
                    <div class="quiz-grid">
                        <?php foreach($quizzes as $quiz): ?>
                            <div class="quiz-card">
                                <h3 class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                <p class="quiz-desc"><?php echo htmlspecialchars($quiz['description'] ?? 'No description provided.'); ?></p>
                                <div class="quiz-meta">
                                    <span class="meta-tag"><?php echo $quiz['q_count']; ?> Questions</span>
                                </div>
                                <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-start">Take Quiz</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h2 class="section-title">Recent Activity</h2>
                <?php if (empty($results)): ?>
                    <div style="color: var(--text-muted); text-align: center; padding: 2rem 0;">
                        You haven't taken any quizzes yet.
                    </div>
                <?php else: ?>
                    <div class="results-list">
                        <?php foreach($results as $res): ?>
                            <?php 
                                $percentage = ($res['total_questions'] > 0) ? round(($res['score'] / $res['total_questions']) * 100) : 0;
                            ?>
                            <div class="result-item">
                                <div class="res-info">
                                    <h4><?php echo htmlspecialchars($res['title']); ?></h4>
                                    <div class="res-date"><?php echo date('M d, Y', strtotime($res['taken_at'])); ?></div>
                                </div>
                                <div>
                                    <div class="res-score"><?php echo $res['score']; ?>/<?php echo $res['total_questions']; ?></div>
                                    <span class="res-perc"><?php echo $percentage; ?>%</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="../assets/js/main.js"></script>
</body>
</html>

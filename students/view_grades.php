<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db.php';

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
$lang = require_once "../lang/{$lang_code}.php";

$classroom_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
$student_id = $_SESSION['user_id'];

if (!$classroom_id) {
    header("Location: dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT c.class_name 
        FROM classrooms c
        JOIN classroom_students cs ON c.id = cs.classroom_id
        WHERE c.id = :classroom_id AND cs.student_id = :student_id
    ");
    $stmt->execute([':classroom_id' => $classroom_id, ':student_id' => $student_id]);
    $classroom = $stmt->fetch();

    if (!$classroom) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT assessment_type, score 
        FROM grades 
        WHERE classroom_id = :classroom_id AND student_id = :student_id
        ORDER BY id DESC
    ");
    $stmt->execute([':classroom_id' => $classroom_id, ':student_id' => $student_id]);
    $grades = $stmt->fetchAll();

    $total_score = 0;
    $grade_count = count($grades);
    $average_score = 0;

    if ($grade_count > 0) {
        foreach ($grades as $grade) {
            $total_score += $grade['score'];
        }
        $average_score = round($total_score / $grade_count, 2);
    }

} catch (PDOException $e) {
    $error = "Failed to load grades.";
    $grades = [];
    $average_score = 0;
    $grade_count = 0;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - BAAMS</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-border: #cccccc;
            --nav-bg: #2a9d8f;
            --table-header-bg: #f8f9fa;
            --table-border: #dee2e6;
            --highlight-bg: #e0f2f1;
            --highlight-text: #00695c;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --input-border: #444444;
            --nav-bg: #1b6b61;
            --table-header-bg: #2d2d2d;
            --table-border: #444444;
            --highlight-bg: #004d40;
            --highlight-text: #80cbc4;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            transition: background-color 0.3s, color 0.3s;
        }

        .navbar {
            background-color: var(--nav-bg);
            padding: 15px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a { color: white; text-decoration: none; margin-right: 15px; }

        .container { padding: 30px; max-width: 1000px; margin: 0 auto; }

        .controls { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .controls a, .controls button { text-decoration: none; padding: 5px 10px; background: var(--card-bg); color: var(--text-color); border: 1px solid var(--input-border); border-radius: 5px; cursor: pointer; font-size: 14px; }

        .card { background: var(--card-bg); padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }

        .summary-box { padding: 20px; text-align: center; border-radius: 5px; font-weight: bold; border: 1px solid var(--input-border); background-color: var(--highlight-bg); color: var(--highlight-text); border-color: var(--highlight-text); margin-bottom: 20px; }
        .summary-box div:first-child { font-size: 18px; margin-bottom: 5px; }
        .summary-box div:last-child { font-size: 32px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--table-border); }
        th { background-color: var(--table-header-bg); font-weight: bold; }

        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; text-transform: uppercase; background-color: var(--table-header-bg); color: var(--text-color); border: 1px solid var(--input-border); }

        .back-link { display: inline-block; margin-bottom: 15px; color: var(--text-color); text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="navbar">
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div>Student Panel</div>
</div>

<div class="container">
    <div class="controls">
        <div>
            <a href="?id=<?php echo $classroom_id; ?>&lang=en">EN</a>
            <a href="?id=<?php echo $classroom_id; ?>&lang=ku">KU</a>
        </div>
        <button id="theme-toggle">🌓 Theme</button>
    </div>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="card">
        <h2>Grades Report: <?php echo htmlspecialchars($classroom['class_name']); ?></h2>

        <?php if (!empty($error)): ?>
            <div style="color: red;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($grade_count > 0): ?>
            <div class="summary-box">
                <div>Overall Average</div>
                <div><?php echo $average_score; ?> / 100</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Grade History</h3>
        <?php if (count($grades) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Assessment Type</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td>
                                <span class="badge">
                                    <?php echo htmlspecialchars($grade['assessment_type']); ?>
                                </span>
                            </td>
                            <td style="font-weight: bold;"><?php echo htmlspecialchars($grade['score']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No grades have been assigned to you for this class yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme') || 'light';

    document.documentElement.setAttribute('data-theme', currentTheme);

    toggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        let newTheme = theme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
</script>

</body>
</html>
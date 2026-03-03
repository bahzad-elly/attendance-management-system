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
    $stmt = $pdo->prepare("SELECT c.class_name FROM classrooms c JOIN classroom_students cs ON c.id = cs.classroom_id WHERE c.id = :classroom_id AND cs.student_id = :student_id");
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
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_lessons FROM lessons WHERE classroom_id = :classroom_id");
    $stmt->execute([':classroom_id' => $classroom_id]);
    $total_lessons = $stmt->fetch()['total_lessons'];

    $stmt = $pdo->prepare("
        SELECT l.lesson_title, l.lesson_date, a.status 
        FROM lessons l 
        LEFT JOIN attendance a ON l.id = a.lesson_id AND a.student_id = :student_id
        WHERE l.classroom_id = :classroom_id
        ORDER BY l.lesson_date DESC, l.id DESC
    ");
    $stmt->execute([':classroom_id' => $classroom_id, ':student_id' => $student_id]);
    $attendance_records = $stmt->fetchAll();

    $present_count = 0;
    $absent_count = 0;
    $late_count = 0;

    foreach ($attendance_records as $record) {
        if ($record['status'] === 'present') $present_count++;
        if ($record['status'] === 'absent') $absent_count++;
        if ($record['status'] === 'late') $late_count++;
    }

} catch (PDOException $e) {
    $error = "Failed to load attendance records.";
    $attendance_records = [];
    $total_lessons = 0;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - BAAMS</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-border: #cccccc;
            --nav-bg: #2a9d8f;
            --table-header-bg: #f8f9fa;
            --table-border: #dee2e6;
            --present-bg: #d4edda;
            --present-text: #155724;
            --absent-bg: #f8d7da;
            --absent-text: #721c24;
            --late-bg: #fff3cd;
            --late-text: #856404;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --input-border: #444444;
            --nav-bg: #1b6b61;
            --table-header-bg: #2d2d2d;
            --table-border: #444444;
            --present-bg: #155724;
            --present-text: #d4edda;
            --absent-bg: #721c24;
            --absent-text: #f8d7da;
            --late-bg: #856404;
            --late-text: #fff3cd;
        }

        body { background-color: var(--bg-color); color: var(--text-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; transition: background-color 0.3s, color 0.3s; }
        .navbar { background-color: var(--nav-bg); padding: 15px 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-right: 15px; }
        .container { padding: 30px; max-width: 1000px; margin: 0 auto; }
        .controls { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .controls a, .controls button { text-decoration: none; padding: 5px 10px; background: var(--card-bg); color: var(--text-color); border: 1px solid var(--input-border); border-radius: 5px; cursor: pointer; font-size: 14px; }
        .card { background: var(--card-bg); padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .summary-boxes { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;}
        .summary-box { flex: 1; padding: 15px; text-align: center; border-radius: 5px; font-weight: bold; border: 1px solid var(--input-border); min-width: 120px;}
        .box-total { background-color: var(--table-header-bg); color: var(--text-color); border-color: var(--input-border); }
        .box-present { background-color: var(--present-bg); color: var(--present-text); border-color: var(--present-text); }
        .box-absent { background-color: var(--absent-bg); color: var(--absent-text); border-color: var(--absent-text); }
        .box-late { background-color: var(--late-bg); color: var(--late-text); border-color: var(--late-text); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--table-border); }
        th { background-color: var(--table-header-bg); font-weight: bold; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-present { background-color: var(--present-bg); color: var(--present-text); }
        .badge-absent { background-color: var(--absent-bg); color: var(--absent-text); }
        .badge-late { background-color: var(--late-bg); color: var(--late-text); }
        .badge-none { background-color: var(--table-border); color: var(--text-color); }
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
        <h2>Attendance Report: <?php echo htmlspecialchars($classroom['class_name']); ?></h2>

        <?php if (!empty($error)): ?>
            <div style="color: red;"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="summary-boxes">
            <div class="summary-box box-total">
                <div>Total Lessons</div>
                <div style="font-size: 24px;"><?php echo $total_lessons; ?></div>
            </div>
            <div class="summary-box box-present">
                <div>Attended (Present)</div>
                <div style="font-size: 24px;"><?php echo $present_count; ?></div>
            </div>
            <div class="summary-box box-absent">
                <div>Missed (Absent)</div>
                <div style="font-size: 24px;"><?php echo $absent_count; ?></div>
            </div>
            <div class="summary-box box-late">
                <div>Late</div>
                <div style="font-size: 24px;"><?php echo $late_count; ?></div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Lesson History</h3>
        <?php if (count($attendance_records) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Lesson Title</th>
                        <th>My Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($record['lesson_date']))); ?></td>
                            <td><?php echo htmlspecialchars($record['lesson_title']); ?></td>
                            <td>
                                <?php if ($record['status']): ?>
                                    <span class="badge badge-<?php echo htmlspecialchars($record['status']); ?>">
                                        <?php echo htmlspecialchars($record['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-none">Not Marked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No lessons have been created for this class yet.</p>
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
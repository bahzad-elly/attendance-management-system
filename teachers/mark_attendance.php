<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db.php';

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
$lang = require_once "../lang/{$lang_code}.php";

$lesson_id = isset($_GET['lesson_id']) ? filter_var($_GET['lesson_id'], FILTER_VALIDATE_INT) : 0;
$teacher_id = $_SESSION['user_id'];

if (!$lesson_id) {
    header("Location: dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT l.*, c.class_name FROM lessons l JOIN classrooms c ON l.classroom_id = c.id WHERE l.id = :lesson_id AND c.teacher_id = :teacher_id");
    $stmt->execute([':lesson_id' => $lesson_id, ':teacher_id' => $teacher_id]);
    $lesson = $stmt->fetch();

    if (!$lesson) {
        header("Location: dashboard.php");
        exit();
    }
    $classroom_id = $lesson['classroom_id'];
} catch (PDOException $e) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $attendance_data = $_POST['attendance'];

    try {
        $check_stmt = $pdo->prepare("SELECT id FROM attendance WHERE lesson_id = :lesson_id AND student_id = :student_id");
        $insert_stmt = $pdo->prepare("INSERT INTO attendance (lesson_id, student_id, status) VALUES (:lesson_id, :student_id, :status)");
        $update_stmt = $pdo->prepare("UPDATE attendance SET status = :status WHERE id = :id");

        foreach ($attendance_data as $student_id => $status) {
            $allowed_statuses = ['present', 'absent', 'late'];
            if (in_array($status, $allowed_statuses)) {
                $check_stmt->execute([':lesson_id' => $lesson_id, ':student_id' => $student_id]);
                $existing = $check_stmt->fetch();

                if ($existing) {
                    $update_stmt->execute([':status' => $status, ':id' => $existing['id']]);
                } else {
                    $insert_stmt->execute([':lesson_id' => $lesson_id, ':student_id' => $student_id, ':status' => $status]);
                }
            }
        }
        $success = "Attendance saved successfully for this lesson.";
    } catch (PDOException $e) {
        $error = "Failed to save attendance.";
    }
}

try {
    $stmt = $pdo->prepare("SELECT u.id, u.name FROM users u JOIN classroom_students cs ON u.id = cs.student_id WHERE cs.classroom_id = :classroom_id ORDER BY u.name ASC");
    $stmt->execute([':classroom_id' => $classroom_id]);
    $students = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE lesson_id = :lesson_id");
    $stmt->execute([':lesson_id' => $lesson_id]);
    $existing_attendance_raw = $stmt->fetchAll();
    
    $existing_attendance = [];
    foreach ($existing_attendance_raw as $record) {
        $existing_attendance[$record['student_id']] = $record['status'];
    }

} catch (PDOException $e) {
    $error = "Failed to load students.";
    $students = [];
    $existing_attendance = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - BAAMS</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-border: #cccccc;
            --btn-bg: #4361ee;
            --btn-hover: #3a53cc;
            --btn-text: #ffffff;
            --nav-bg: #4361ee;
            --table-header-bg: #f8f9fa;
            --table-border: #dee2e6;
            --present-color: #2a9d8f;
            --absent-color: #e63946;
            --late-color: #e9c46a;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --input-border: #444444;
            --btn-bg: #4361ee;
            --btn-hover: #5a75f0;
            --nav-bg: #2a3eb1;
            --table-header-bg: #2d2d2d;
            --table-border: #444444;
        }

        body { background-color: var(--bg-color); color: var(--text-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; transition: background-color 0.3s, color 0.3s; }
        .navbar { background-color: var(--nav-bg); padding: 15px 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-right: 15px; }
        .container { padding: 30px; max-width: 1000px; margin: 0 auto; }
        .controls { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .controls a, .controls button { text-decoration: none; padding: 5px 10px; background: var(--card-bg); color: var(--text-color); border: 1px solid var(--input-border); border-radius: 5px; cursor: pointer; font-size: 14px; }
        .card { background: var(--card-bg); padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--table-border); }
        th { background-color: var(--table-header-bg); font-weight: bold; }
        .radio-group { display: flex; gap: 15px; }
        .radio-group label { cursor: pointer; font-weight: bold; }
        .text-present { color: var(--present-color); }
        .text-absent { color: var(--absent-color); }
        .text-late { color: var(--late-color); }
        button.submit-btn { width: 100%; padding: 15px; background-color: var(--btn-bg); color: var(--btn-text); border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 20px; font-weight: bold; }
        button.submit-btn:hover { background-color: var(--btn-hover); }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
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
    <div>Teacher Panel</div>
</div>

<div class="container">
    <div class="controls">
        <div>
            <a href="?lesson_id=<?php echo $lesson_id; ?>&lang=en">EN</a>
            <a href="?lesson_id=<?php echo $lesson_id; ?>&lang=ku">KU</a>
        </div>
        <button id="theme-toggle">🌓 Theme</button>
    </div>

    <a href="manage_classroom.php?id=<?php echo $classroom_id; ?>" class="back-link">← Back to Classroom</a>

    <div class="card">
        <h2><?php echo htmlspecialchars($lesson['lesson_title']); ?> (<?php echo htmlspecialchars($lesson['lesson_date']); ?>)</h2>
        <p>Classroom: <?php echo htmlspecialchars($lesson['class_name']); ?></p>

        <?php if (!empty($success)): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>

        <?php if (count($students) > 0): ?>
            <form action="mark_attendance.php?lesson_id=<?php echo $lesson_id; ?>" method="POST">
                <table>
                    <thead><tr><th>Student Name</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php $status = isset($existing_attendance[$student['id']]) ? $existing_attendance[$student['id']] : ''; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td>
                                    <div class="radio-group">
                                        <label class="text-present"><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="present" <?php echo $status === 'present' ? 'checked' : ''; ?> required> Present</label>
                                        <label class="text-absent"><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="absent" <?php echo $status === 'absent' ? 'checked' : ''; ?> required> Absent</label>
                                        <label class="text-late"><input type="radio" name="attendance[<?php echo $student['id']; ?>]" value="late" <?php echo $status === 'late' ? 'checked' : ''; ?> required> Late</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="submit-btn">Save Attendance</button>
            </form>
        <?php else: ?>
            <p>No students enrolled.</p>
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
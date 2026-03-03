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

$classroom_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
$teacher_id = $_SESSION['user_id'];

if (!$classroom_id) {
    header("Location: dashboard.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = :id AND teacher_id = :teacher_id");
    $stmt->execute([':id' => $classroom_id, ':teacher_id' => $teacher_id]);
    $classroom = $stmt->fetch();

    if (!$classroom) {
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
        $student_id = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);
        if ($student_id) {
            try {
                $stmt = $pdo->prepare("INSERT INTO classroom_students (classroom_id, student_id) VALUES (:classroom_id, :student_id)");
                $stmt->execute([':classroom_id' => $classroom_id, ':student_id' => $student_id]);
                $success = "Student enrolled successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Student is already enrolled in this class.";
                } else {
                    $error = "Failed to add student.";
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'remove_student') {
        $student_id = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);
        if ($student_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM classroom_students WHERE classroom_id = :classroom_id AND student_id = :student_id");
                $stmt->execute([':classroom_id' => $classroom_id, ':student_id' => $student_id]);
                $success = "Student removed from class.";
            } catch (PDOException $e) {
                $error = "Failed to remove student.";
            }
        }
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email 
        FROM users u 
        JOIN classroom_students cs ON u.id = cs.student_id 
        WHERE cs.classroom_id = :classroom_id
        ORDER BY u.name ASC
    ");
    $stmt->execute([':classroom_id' => $classroom_id]);
    $enrolled_students = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT id, name, email 
        FROM users 
        WHERE role = 'student' 
        AND id NOT IN (SELECT student_id FROM classroom_students WHERE classroom_id = :classroom_id)
        ORDER BY name ASC
    ");
    $stmt->execute([':classroom_id' => $classroom_id]);
    $available_students = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Failed to load students.";
    $enrolled_students = [];
    $available_students = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage <?php echo htmlspecialchars($classroom['class_name']); ?> - BAAMS</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-border: #cccccc;
            --input-bg: #ffffff;
            --btn-bg: #4361ee;
            --btn-hover: #3a53cc;
            --btn-text: #ffffff;
            --btn-danger: #e63946;
            --btn-danger-hover: #d62828;
            --btn-action: #2a9d8f;
            --btn-action-hover: #21867a;
            --nav-bg: #4361ee;
            --table-header-bg: #f8f9fa;
            --table-border: #dee2e6;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --input-border: #444444;
            --input-bg: #2d2d2d;
            --btn-bg: #4361ee;
            --btn-hover: #5a75f0;
            --btn-action: #2a9d8f;
            --nav-bg: #2a3eb1;
            --table-header-bg: #2d2d2d;
            --table-border: #444444;
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

        .top-actions { display: flex; gap: 15px; margin-bottom: 20px; }
        .top-actions a { flex: 1; text-align: center; padding: 15px; background-color: var(--btn-action); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .top-actions a:hover { background-color: var(--btn-action-hover); }

        .form-group { display: flex; gap: 10px; align-items: center; margin-bottom: 15px; }
        select { flex: 1; padding: 10px; border: 1px solid var(--input-border); border-radius: 5px; background-color: var(--input-bg); color: var(--text-color); }
        button.submit-btn { padding: 10px 20px; background-color: var(--btn-bg); color: var(--btn-text); border: none; border-radius: 5px; cursor: pointer; }
        button.submit-btn:hover { background-color: var(--btn-hover); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--table-border); }
        th { background-color: var(--table-header-bg); font-weight: bold; }
        
        button.btn-remove { padding: 5px 10px; background-color: var(--btn-danger); color: white; border: none; border-radius: 3px; cursor: pointer; }
        button.btn-remove:hover { background-color: var(--btn-danger-hover); }

        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
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
            <a href="?id=<?php echo $classroom_id; ?>&lang=en">EN</a>
            <a href="?id=<?php echo $classroom_id; ?>&lang=ku">KU</a>
        </div>
        <button id="theme-toggle">🌓 Theme</button>
    </div>

    <div class="card">
        <h2>Classroom: <?php echo htmlspecialchars($classroom['class_name']); ?></h2>
        
        <div class="top-actions">
            <a href="mark_attendance.php?id=<?php echo $classroom_id; ?>">📋 Mark Attendance</a>
            <a href="assign_grades.php?id=<?php echo $classroom_id; ?>">📝 Assign Grades</a>
        </div>
    </div>

    <div class="card">
        <h3>Enroll New Student</h3>
        
        <?php if (!empty($success)): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="manage_classroom.php?id=<?php echo $classroom_id; ?>" method="POST">
            <input type="hidden" name="action" value="add_student">
            <div class="form-group">
                <select name="student_id" required>
                    <option value="" disabled selected>Select a student to enroll...</option>
                    <?php foreach ($available_students as $student): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['name'] . ' (' . $student['email'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="submit-btn">Add Student</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Enrolled Students (<?php echo count($enrolled_students); ?>)</h3>
        
        <?php if (count($enrolled_students) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enrolled_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <form action="manage_classroom.php?id=<?php echo $classroom_id; ?>" method="POST" onsubmit="return confirm('Remove this student from the class?');" style="margin:0;">
                                    <input type="hidden" name="action" value="remove_student">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No students enrolled yet.</p>
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
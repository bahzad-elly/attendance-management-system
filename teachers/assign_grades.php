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
    if (isset($_POST['action']) && $_POST['action'] === 'assign_grade') {
        $student_id = filter_var($_POST['student_id'], FILTER_VALIDATE_INT);
        $assessment_type = $_POST['assessment_type'];
        $score = filter_var($_POST['score'], FILTER_VALIDATE_FLOAT);

        $allowed_types = ['exam', 'quiz', 'assignment'];

        if ($student_id && in_array($assessment_type, $allowed_types) && $score !== false) {
            try {
                $stmt = $pdo->prepare("INSERT INTO grades (classroom_id, student_id, assessment_type, score) VALUES (:classroom_id, :student_id, :assessment_type, :score)");
                $stmt->execute([
                    ':classroom_id' => $classroom_id,
                    ':student_id' => $student_id,
                    ':assessment_type' => $assessment_type,
                    ':score' => $score
                ]);
                $success = "Grade assigned successfully.";
            } catch (PDOException $e) {
                $error = "Failed to assign grade.";
            }
        } else {
            $error = "Invalid input data.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_grade') {
        $grade_id = filter_var($_POST['grade_id'], FILTER_VALIDATE_INT);
        if ($grade_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM grades WHERE id = :id AND classroom_id = :classroom_id");
                $stmt->execute([':id' => $grade_id, ':classroom_id' => $classroom_id]);
                $success = "Grade deleted successfully.";
            } catch (PDOException $e) {
                $error = "Failed to delete grade.";
            }
        }
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name 
        FROM users u 
        JOIN classroom_students cs ON u.id = cs.student_id 
        WHERE cs.classroom_id = :classroom_id
        ORDER BY u.name ASC
    ");
    $stmt->execute([':classroom_id' => $classroom_id]);
    $students = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT g.id, g.assessment_type, g.score, u.name as student_name 
        FROM grades g
        JOIN users u ON g.student_id = u.id
        WHERE g.classroom_id = :classroom_id
        ORDER BY g.id DESC
    ");
    $stmt->execute([':classroom_id' => $classroom_id]);
    $grades = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Failed to load data.";
    $students = [];
    $grades = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Grades - BAAMS</title>
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

        .form-row { display: flex; gap: 15px; margin-bottom: 15px; align-items: end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid var(--input-border); border-radius: 5px; background-color: var(--input-bg); color: var(--text-color); box-sizing: border-box; }
        
        button.submit-btn { padding: 10px 20px; background-color: var(--btn-bg); color: var(--btn-text); border: none; border-radius: 5px; cursor: pointer; height: 40px; }
        button.submit-btn:hover { background-color: var(--btn-hover); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--table-border); }
        th { background-color: var(--table-header-bg); font-weight: bold; }

        button.btn-remove { padding: 5px 10px; background-color: var(--btn-danger); color: white; border: none; border-radius: 3px; cursor: pointer; }
        button.btn-remove:hover { background-color: var(--btn-danger-hover); }

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
            <a href="?id=<?php echo $classroom_id; ?>&lang=en">EN</a>
            <a href="?id=<?php echo $classroom_id; ?>&lang=ku">KU</a>
        </div>
        <button id="theme-toggle">🌓 Theme</button>
    </div>

    <a href="manage_classroom.php?id=<?php echo $classroom_id; ?>" class="back-link">← Back to Classroom</a>

    <div class="card">
        <h2>Assign Grades: <?php echo htmlspecialchars($classroom['class_name']); ?></h2>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (count($students) > 0): ?>
            <form action="assign_grades.php?id=<?php echo $classroom_id; ?>" method="POST">
                <input type="hidden" name="action" value="assign_grade">
                <div class="form-row">
                    <div class="form-group">
                        <label>Student</label>
                        <select name="student_id" required>
                            <option value="" disabled selected>Select a student...</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Assessment Type</label>
                        <select name="assessment_type" required>
                            <option value="assignment">Assignment</option>
                            <option value="quiz">Quiz</option>
                            <option value="exam">Exam</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Score</label>
                        <input type="number" step="0.01" min="0" name="score" placeholder="e.g. 85.50" required>
                    </div>

                    <button type="submit" class="submit-btn">Save Grade</button>
                </div>
            </form>
        <?php else: ?>
            <p>You must enroll students into this classroom before assigning grades.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Grade Records</h3>
        <?php if (count($grades) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($grade['assessment_type'])); ?></td>
                            <td><?php echo htmlspecialchars($grade['score']); ?></td>
                            <td>
                                <form action="assign_grades.php?id=<?php echo $classroom_id; ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this grade?');" style="margin:0;">
                                    <input type="hidden" name="action" value="delete_grade">
                                    <input type="hidden" name="grade_id" value="<?php echo $grade['id']; ?>">
                                    <button type="submit" class="btn-remove">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No grades have been assigned yet.</p>
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
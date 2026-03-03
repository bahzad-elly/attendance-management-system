<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/db.php';

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
$lang = require_once "../lang/{$lang_code}.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_class') {
        $class_name = htmlspecialchars(trim($_POST['class_name']));
        $teacher_id = filter_var($_POST['teacher_id'], FILTER_VALIDATE_INT);

        if (!empty($class_name) && $teacher_id) {
            try {
                $stmt = $pdo->prepare("INSERT INTO classrooms (teacher_id, class_name) VALUES (:teacher_id, :class_name)");
                $stmt->execute([
                    ':teacher_id' => $teacher_id,
                    ':class_name' => $class_name
                ]);
                $success = "Classroom created and assigned to teacher successfully.";
            } catch (PDOException $e) {
                $error = "System error. Could not create classroom.";
            }
        } else {
            $error = "Please fill in all fields.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_class') {
        $class_id = filter_var($_POST['class_id'], FILTER_VALIDATE_INT);
        if ($class_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM classrooms WHERE id = :id");
                $stmt->execute([':id' => $class_id]);
                $success = "Classroom deleted successfully.";
            } catch (PDOException $e) {
                $error = "Failed to delete classroom.";
            }
        }
    }
}

try {
    $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'teacher' ORDER BY name ASC");
    $teachers = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT c.id, c.class_name, c.created_at, u.name as teacher_name 
        FROM classrooms c
        JOIN users u ON c.teacher_id = u.id
        ORDER BY c.created_at DESC
    ");
    $classrooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $teachers = [];
    $classrooms = [];
    $error = "Failed to load data.";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms - BAAMS</title>
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
            --nav-bg: #2b2d42;
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
            --nav-bg: #1a1a1a;
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

        button.btn-delete { background-color: var(--btn-danger); color: white; padding: 5px 10px; border: none; border-radius: 3px; font-size: 14px; cursor: pointer; }
        button.btn-delete:hover { background-color: var(--btn-danger-hover); }

        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="navbar">
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="register.php">Create User</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="manage_classrooms.php">Manage Classrooms</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div>Admin Panel</div>
</div>

<div class="container">
    <div class="controls">
        <div>
            <a href="?lang=en">EN</a>
            <a href="?lang=ku">KU</a>
        </div>
        <button id="theme-toggle">🌓 Theme</button>
    </div>

    <div class="card">
        <h2>Assign Teacher to New Classroom</h2>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="manage_classrooms.php" method="POST">
            <input type="hidden" name="action" value="create_class">
            <div class="form-row">
                <div class="form-group">
                    <label>Classroom Name</label>
                    <input type="text" name="class_name" required>
                </div>
                <div class="form-group">
                    <label>Assign Teacher</label>
                    <select name="teacher_id" required>
                        <option value="" disabled selected>Select a teacher...</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Create & Assign</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>All Classrooms</h3>
        <?php if (count($classrooms) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Classroom Name</th>
                        <th>Assigned Teacher</th>
                        <th>Date Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classrooms as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td style="font-weight: bold;"><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($class['created_at']))); ?></td>
                            <td>
                                <form action="manage_classrooms.php" method="POST" onsubmit="return confirm('Delete this classroom? This will also delete all attendance and grades associated with it.');" style="margin:0;">
                                    <input type="hidden" name="action" value="delete_class">
                                    <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No classrooms exist yet.</p>
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
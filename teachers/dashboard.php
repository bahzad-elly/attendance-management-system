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

$teacher_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, class_name, created_at FROM classrooms WHERE teacher_id = :teacher_id ORDER BY created_at DESC");
    $stmt->execute([':teacher_id' => $teacher_id]);
    $classrooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $classrooms = [];
    $error = "Failed to load classrooms.";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - BAAMS</title>
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

        .navbar a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
        }

        .container {
            padding: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .controls a, .controls button {
            text-decoration: none;
            padding: 5px 10px;
            background: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--input-border);
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .create-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--btn-bg);
            color: var(--btn-text);
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .create-btn:hover { background-color: var(--btn-hover); }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--table-border);
        }

        th {
            background-color: var(--table-header-bg);
            font-weight: bold;
        }
        
        .manage-link {
            color: var(--btn-bg);
            text-decoration: none;
            font-weight: bold;
        }
        
        .manage-link:hover { text-decoration: underline; }

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
            <a href="?lang=en">EN</a>
            <a href="?lang=ku">KU</a>
        </div>
        <button id="theme-toggle">🌓 Theme</button>
    </div>

    <div class="card">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <p>Manage your classrooms, attendance, and student grades from here.</p>
    </div>

    <div class="card">
        <a href="create_classroom.php" class="create-btn">+ Create New Classroom</a>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (count($classrooms) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Classroom Name</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classrooms as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($class['created_at']))); ?></td>
                            <td>
                                <a href="manage_classroom.php?id=<?php echo $class['id']; ?>" class="manage-link">Open Classroom →</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You haven't created any classrooms yet.</p>
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
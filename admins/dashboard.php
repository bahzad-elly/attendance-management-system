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

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch()['total_users'];
} catch (PDOException $e) {
    $total_users = 0;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BAAMS</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-border: #cccccc;
            --btn-bg: #4361ee;
            --btn-hover: #3a53cc;
            --btn-text: #ffffff;
            --nav-bg: #2b2d42;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --input-border: #444444;
            --btn-bg: #4361ee;
            --btn-hover: #5a75f0;
            --nav-bg: #1a1a1a;
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

        .card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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

        .dashboard-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .dashboard-links a {
            padding: 15px 20px;
            background-color: var(--btn-bg);
            color: var(--btn-text);
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            flex: 1;
            font-weight: bold;
        }

        .dashboard-links a:hover {
            background-color: var(--btn-hover);
        }
    </style>
</head>
<body>

<div class="navbar">
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="register.php">Create User</a>
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
        <h2><?php echo isset($lang['welcome']) ? $lang['welcome'] : 'Welcome'; ?>, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <p>Total Registered Users: <strong><?php echo $total_users; ?></strong></p>
    </div>

    <div class="dashboard-links">
        <a href="register.php">➕ Create New User</a>
        <a href="manage_users.php">👥 Manage Users</a>
        <a href="manage_classrooms.php">🏫 Manage Classrooms</a>
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
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

$user_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$user_id || $user_id === $_SESSION['user_id']) {
    header("Location: manage_users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        header("Location: manage_users.php");
        exit();
    } catch (PDOException $e) {
        $error = "System error. Could not delete user.";
    }
}

try {
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: manage_users.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: manage_users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - BAAMS</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-border: #cccccc;
            --input-bg: #ffffff;
            --btn-danger: #e63946;
            --btn-danger-hover: #d62828;
            --btn-secondary: #6c757d;
            --btn-secondary-hover: #5a6268;
            --btn-text: #ffffff;
            --nav-bg: #2b2d42;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --input-border: #444444;
            --input-bg: #2d2d2d;
            --btn-danger: #e63946;
            --btn-danger-hover: #ff4d4d;
            --btn-secondary: #6c757d;
            --btn-secondary-hover: #868e96;
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
            background: var(--card-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: 50px auto;
            text-align: center;
        }

        h2 { margin-bottom: 20px; color: var(--btn-danger); }

        p { margin-bottom: 20px; font-size: 16px; }

        .btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        button.submit-btn {
            padding: 10px 20px;
            background-color: var(--btn-danger);
            color: var(--btn-text);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        button.submit-btn:hover { background-color: var(--btn-danger-hover); }

        a.cancel-btn {
            padding: 10px 20px;
            background-color: var(--btn-secondary);
            color: var(--btn-text);
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        a.cancel-btn:hover { background-color: var(--btn-secondary-hover); }

        .controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .controls a, .controls button {
            text-decoration: none;
            padding: 5px 10px;
            background: var(--input-bg);
            color: var(--text-color);
            border: 1px solid var(--input-border);
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div>Admin Panel</div>
</div>

<div class="container">
    <div class="controls">
        <div>
            <a href="?id=<?php echo $user_id; ?>&lang=en">EN</a>
            <a href="?id=<?php echo $user_id; ?>&lang=ku">KU</a>
        </div>
        <button id="theme-toggle">🌓 Theme</button>
    </div>

    <h2>Confirm Deletion</h2>
    <p>Are you sure you want to permanently delete <strong><?php echo htmlspecialchars($user['name']); ?></strong> (<?php echo htmlspecialchars($user['email']); ?>)?</p>
    
    <div class="btn-container">
        <form action="delete_user.php?id=<?php echo $user_id; ?>" method="POST" style="margin: 0;">
            <button type="submit" class="submit-btn">Yes, Delete</button>
        </form>
        <a href="manage_users.php" class="cancel-btn">Cancel</a>
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
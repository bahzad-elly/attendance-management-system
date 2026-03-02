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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    
    if ($delete_id && $delete_id !== $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $delete_id]);
            $success = "User deleted successfully.";
        } catch (PDOException $e) {
            $error = "Failed to delete user.";
        }
    } else {
        $error = "Invalid operation or attempt to delete your own account.";
    }
}

try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $error = "Failed to load users.";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - BAAMS</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #333333;
            --input-border: #cccccc;
            --btn-bg: #4361ee;
            --btn-hover: #3a53cc;
            --btn-text: #ffffff;
            --btn-danger: #e63946;
            --btn-danger-hover: #d62828;
            --btn-edit: #2a9d8f;
            --btn-edit-hover: #21867a;
            --nav-bg: #2b2d42;
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
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background-color: var(--btn-edit);
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }

        .btn-edit:hover { background-color: var(--btn-edit-hover); }

        .btn-delete {
            background-color: var(--btn-danger);
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-delete:hover { background-color: var(--btn-danger-hover); }

        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
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
        <h2>Manage Users</h2>

        <?php if (!empty($success)): ?>
            <div class="alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($user['created_at']))); ?></td>
                        <td class="action-btns">
                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-edit">Edit</a>
                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn-delete">Delete</a>
                            
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <form action="manage_users.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" style="margin:0;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
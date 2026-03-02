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
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $allowed_roles = ['admin', 'teacher', 'student'];
    if (!in_array($role, $allowed_roles)) {
        die("Invalid role selected.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)");
        
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => $hashed_password,
            ':role' => $role
        ]);

        $success = isset($lang['success_msg']) ? $lang['success_msg'] : "User created successfully.";

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = isset($lang['error_msg']) ? $lang['error_msg'] : "Email already exists.";
        } else {
            $error = "System error.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo $lang['direction']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - BAAMS</title>
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
            --nav-bg: #2b2d42;
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
        }

        h2 { text-align: center; margin-bottom: 20px; }

        .form-group { margin-bottom: 15px; }
        
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            background-color: var(--input-bg);
            color: var(--text-color);
            box-sizing: border-box;
        }

        button.submit-btn {
            width: 100%;
            padding: 10px;
            background-color: var(--btn-bg);
            color: var(--btn-text);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        button.submit-btn:hover { background-color: var(--btn-hover); }

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

        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<div class="navbar">
    <div>
        <a href="dashboard.php">Dashboard</a>
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

    <h2><?php echo isset($lang['register_title']) ? $lang['register_title'] : 'Create New User'; ?></h2>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label><?php echo isset($lang['full_name']) ? $lang['full_name'] : 'Full Name'; ?></label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label><?php echo isset($lang['email']) ? $lang['email'] : 'Email'; ?></label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label><?php echo isset($lang['password']) ? $lang['password'] : 'Password'; ?></label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label><?php echo isset($lang['role']) ? $lang['role'] : 'Role'; ?></label>
            <select name="role" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>

        <button type="submit" class="submit-btn"><?php echo isset($lang['btn_register']) ? $lang['btn_register'] : 'Create Account'; ?></button>
    </form>
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
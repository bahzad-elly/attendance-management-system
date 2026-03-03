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

if (!$user_id) {
    header("Location: manage_users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    $phone = htmlspecialchars(trim($_POST['phone']));
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $address = htmlspecialchars(trim($_POST['address']));

    $allowed_roles = ['admin', 'teacher', 'student'];
    if (!in_array($role, $allowed_roles)) {
        die("Invalid role selected.");
    }

    try {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, role = :role, phone = :phone, date_of_birth = :date_of_birth, address = :address, password_hash = :password_hash WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':role' => $role,
                ':phone' => $phone,
                ':date_of_birth' => $date_of_birth,
                ':address' => $address,
                ':password_hash' => $hashed_password,
                ':id' => $user_id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, role = :role, phone = :phone, date_of_birth = :date_of_birth, address = :address WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':role' => $role,
                ':phone' => $phone,
                ':date_of_birth' => $date_of_birth,
                ':address' => $address,
                ':id' => $user_id
            ]);
        }
        $success = "User updated successfully.";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Email already exists.";
        } else {
            $error = "System error.";
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT name, email, role, phone, date_of_birth, address FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: manage_users.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Failed to load user data.";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>" dir="<?php echo isset($lang['direction']) ? $lang['direction'] : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - BAAMS</title>
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

        body { background-color: var(--bg-color); color: var(--text-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; transition: background-color 0.3s, color 0.3s; }
        .navbar { background-color: var(--nav-bg); padding: 15px 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin-right: 15px; }
        .container { background: var(--card-bg); padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 500px; margin: 50px auto; }
        h2 { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid var(--input-border); border-radius: 5px; background-color: var(--input-bg); color: var(--text-color); box-sizing: border-box; font-family: inherit; }
        button.submit-btn { width: 100%; padding: 10px; background-color: var(--btn-bg); color: var(--btn-text); border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        button.submit-btn:hover { background-color: var(--btn-hover); }
        .controls { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .controls a, .controls button { text-decoration: none; padding: 5px 10px; background: var(--input-bg); color: var(--text-color); border: 1px solid var(--input-border); border-radius: 5px; cursor: pointer; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: var(--text-color); text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
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

    <h2>Edit User</h2>

    <?php if (!empty($success)): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>

    <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars((string)$user['phone']); ?>">
        </div>

        <div class="form-group">
            <label>Date of Birth</label>
            <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars((string)$user['date_of_birth']); ?>">
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" rows="3"><?php echo htmlspecialchars((string)$user['address']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>

        <div class="form-group">
            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password">
        </div>

        <button type="submit" class="submit-btn">Update User</button>
    </form>
    
    <a href="manage_users.php" class="back-link">← Back to Manage Users</a>
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
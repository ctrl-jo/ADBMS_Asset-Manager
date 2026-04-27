<?php
session_start();
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.password, u.full_name, u.role,
               d.department_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Changed to check for exact plain-text password match
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['department'] = $user['department_name'] ?? '';

        if ($user['role'] == 'Admin') {
            header("Location: admin-dashboard.php");
        } else {
            header("Location: faculty-dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Asset Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-header">
            <h1>Asset Management System</h1>
            <p>Secure System Access</p>
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 1rem; max-width: 400px; margin: 0 auto; text-align: left; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <?php if($error): ?><div style="color: red; margin-bottom: 1rem; text-align: center;"><?= $error ?></div><?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="example@dept.edu">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required value="">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 1rem;">Login</button>
            </form>
            
            
        </div>
    </div>
</body>
</html>
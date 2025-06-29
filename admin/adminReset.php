<?php
require_once('../config/db.php');
session_start();

$token = $_GET['token'] ?? '';
$message = '';
$showForm = false;

if ($token) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, reset_token_expires FROM admin_users WHERE reset_token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows) {
        $stmt->bind_result($admin_id, $expires);
        $stmt->fetch();
        if (strtotime($expires) > time()) {
            $showForm = true;
        } else {
            $message = 'Reset link has expired. Please request a new one.';
        }
    } else {
        $message = 'Invalid reset token.';
    }
    $stmt->close();
    $conn->close();
} else {
    $message = 'No reset token provided.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'], $_POST['password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    if (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $showForm = true;
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE reset_token=? AND reset_token_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows) {
            $stmt->bind_result($admin_id);
            $stmt->fetch();
            $stmt->close();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin_users SET password=?, reset_token=NULL, reset_token_expires=NULL WHERE id=?");
            $stmt->bind_param("si", $hash, $admin_id);
            $stmt->execute();
            $message = 'Password reset successful. <a href="adminLogin.php">Login</a>';
            $showForm = false;
        } else {
            $message = 'Invalid or expired token.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reset Password | Naija eHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/auth.css">
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .auth-container {
            max-width: 400px;
            margin: 4rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
            padding: 2.2rem 2rem 1.7rem 2rem;
        }
        .auth-form h2 {
            text-align: center;
            color: #1a6b54;
            margin-bottom: 1.5rem;
            font-size: 1.35rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        label {
            font-weight: 500;
            color: #1a6b54;
            margin-bottom: 0.3rem;
            display: block;
        }
        input[type="password"], input[type="text"], input[type="email"] {
            width: 100%;
            padding: 0.65rem 0.8rem;
            border: 1px solid #e1e1e1;
            border-radius: 6px;
            font-size: 1rem;
            background: #f9f9f9;
            margin-top: 0.2rem;
        }
        .btn-primary {
            background: #067c3c;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s;
        }
        .btn-primary:hover {
            background: #055d2b;
        }
        .alert {
            background: #e0f2ec;
            color: #067c3c;
            border-radius: 6px;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.98rem;
            border: 1px solid #b9e4d0;
        }
        .alert.error {
            background: #ffeaea;
            color: #d32f2f;
            border: 1px solid #f5b0b0;
        }
        @media (max-width: 500px) {
            .auth-container {
                max-width: 98vw;
                padding: 1.2rem 0.5rem 1.2rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <form class="auth-form" method="post" action="">
            <h2>Admin Reset Password</h2>
            <?php if ($message): ?>
                <div class="alert<?php if (stripos($message, 'success') === false) echo ' error'; ?>"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($showForm): ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" required minlength="8" placeholder="Enter new password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Reset Password</button>
            <?php endif; ?>
            <div style="margin-top:1rem;text-align:center;">
                <a href="adminLogin.php" style="color:#1a6b54;text-decoration:underline;">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>

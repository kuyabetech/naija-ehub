<?php
require_once('../config/db.php');
session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $message = "Please enter both email and password.";
    } else {
        $conn = getDbConnection();
        // Ensure admin_users table exists
        $conn->query("CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        // Check if 'role' column exists, if not, add it (for legacy DBs)
        $checkRole = $conn->query("SHOW COLUMNS FROM admin_users LIKE 'role'");
        if ($checkRole->num_rows == 0) {
            $conn->query("ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) DEFAULT 'admin' AFTER password");
        }
        $stmt = $conn->prepare("SELECT id, password, role FROM admin_users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($admin_id, $hash, $role);
        if ($stmt->fetch() && password_verify($password, $hash)) {
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['role'] = $role ?: 'admin';
            header('Location: admin.php');
            exit();
        } else {
            $message = "Invalid email or password.";
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
  <title>Admin Login | Naija eHub</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    body { background: #f7f7f7; font-family: 'Segoe UI', Arial, sans-serif; }
    .login-container {
      max-width: 400px; margin: 60px auto; background: #fff; border-radius: 8px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 2.5rem 2rem 2rem 2rem;
    }
    .login-container h2 { text-align: center; margin-bottom: 1.5rem; color: #067c3c; }
    form label { display: block; margin-bottom: 0.5rem; color: #444; font-weight: 500; }
    form input[type="email"], form input[type="password"] {
      width: 100%; padding: 0.7rem; border: 1px solid #ccc; border-radius: 5px;
      margin-bottom: 1.2rem; font-size: 1rem; background: #fafafa;
    }
    form button[type="submit"] {
      width: 100%; background: #067c3c; color: #fff; border: none; padding: 0.8rem 0;
      border-radius: 5px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;
    }
    form button[type="submit"]:hover { background: #055c2a; }
    .message {
      margin-top: 1.2rem; padding: 0.9rem 1rem; border-radius: 5px; background: #ffeaea;
      color: #d32f2f; border: 1px solid #f5c6cb; font-size: 1rem; text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Admin Login</h2>
     <?php if ($message): ?>
      <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
  
    <form method="post" action="">
      <label>Email Address</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <div style="margin-bottom:1rem;text-align:left;">
        <a href="adminForgot.php" style="color:#067c3c;text-decoration:underline;font-size:0.98rem;">Forgot password?</a>
      </div>
      <button type="submit">Login</button>
    </form>
    <div style="margin-top:1rem;text-align:center;">
      <a href="adminRegister.php" style="color:#067c3c;text-decoration:underline;font-size:0.98rem;">Create an account</a>
    </div>
  </div>
  <?php include __DIR__ . '/../includes/spinner.php'; ?>
</body>
</html>

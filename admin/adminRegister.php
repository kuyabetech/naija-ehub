<?php
require_once('../config/db.php');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$full_name || !$email || !$password || !$confirm) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters.";
    } else {
        $conn = getDbConnection();
        // Create admin_users table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO admin_users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $email, $hash);
            if ($stmt->execute()) {
                $message = "Admin registration successful. <a href='adminLogin.php'>Login here</a>.";
            } else {
                // Handle duplicate entry or other DB errors gracefully
                if ($conn->errno === 1062) {
                    $message = "Email already registered.";
                } else {
                    $message = "Registration failed. Please try again.";
                }
            }
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
  <title>Admin Registration | Naija eHub</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    body { background: #f7f7f7; font-family: 'Segoe UI', Arial, sans-serif; }
    .register-container {
      max-width: 400px; margin: 60px auto; background: #fff; border-radius: 8px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.07); padding: 2.5rem 2rem 2rem 2rem;
    }
    .register-container h2 { text-align: center; margin-bottom: 1.5rem; color: #067c3c; }
    form label { display: block; margin-bottom: 0.5rem; color: #444; font-weight: 500; }
    form input[type="text"], form input[type="email"], form input[type="password"] {
      width: 100%; padding: 0.7rem; border: 1px solid #ccc; border-radius: 5px;
      margin-bottom: 1.2rem; font-size: 1rem; background: #fafafa;
    }
    form button[type="submit"] {
      width: 100%; background: #067c3c; color: #fff; border: none; padding: 0.8rem 0;
      border-radius: 5px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;
    }
    form button[type="submit"]:hover { background: #055c2a; }
    .message {
      margin-top: 1.2rem; padding: 0.9rem 1rem; border-radius: 5px; background: #e6f9ed;
      color: #067c3c; border: 1px solid #b6e7c9; font-size: 1rem; text-align: center;
    }
    .message a { color: #067c3c; text-decoration: underline; }
  </style>
</head>
<body>
  <div class="register-container">
    <h2>Admin Registration</h2>
     <?php if ($message): ?>
      <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    <form method="post" action="">
      <label>Full Name</label>
      <input type="text" name="full_name" required>
      <label>Email Address</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <label>Confirm Password</label>
      <input type="password" name="confirm_password" required>
      <button type="submit">Register</button>
    </form>
    <div style="margin-top:1rem;text-align:center;">
      <a href="adminLogin.php" style="color:#067c3c;text-decoration:underline;font-size:0.98rem;">Already have an account? Login here</a>
    </div>
   
  </div>
</body>
</html>

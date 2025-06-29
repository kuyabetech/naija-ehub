<?php
session_start();
require_once('../config/db.php');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Database connection
  $conn = getDbConnection();

  $login = trim($_POST['loginEmail'] ?? '');
  $password = $_POST['loginPassword'] ?? '';

  if (empty($login) || empty($password)) {
    $error = 'Please fill in all fields.';
  } else {
    // Check if login is email or phone
    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
      $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    } else {
      $stmt = $conn->prepare("SELECT id, password FROM users WHERE phone = ?");
    }
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
      $stmt->bind_result($user_id, $hashed_password);
      $stmt->fetch();
      if (password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $user_id;
        header('Location: ../dashboard.php');
        exit();
      } else {
        $error = 'Invalid credentials.';
      }
    } else {
      $error = 'User not found.';
    }
    $stmt->close();
  }
  $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Naija eHub</title>
  <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
<!-- Similar header as register.html -->
<div class="auth-container">
  <div class="auth-card">
    <div class="logo">
      <img src="../assets/img/logo-icon.png" alt="Naija eHub Logo">
    </div>
    
    <h2>Welcome Back</h2>
    <?php if ($error): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form id="loginForm" method="POST" action="">
      <div class="form-group">
        <label for="loginEmail">Email or Phone</label>
        <input type="text" id="loginEmail" name="loginEmail" required>
      </div>
      <div class="form-group">
        <label for="loginPassword">Password</label>
        <input type="password" id="loginPassword" name="loginPassword" required>
        <a href="forgot-password.php" class="text-link">Forgot Password?</a>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
    </form>
    
    <div class="auth-footer">
      <p>New to Naija eHub? <a href="register.php">Create account</a></p>
    </div>
  </div>
</div>
  
  <script src="../js/auth.js"></script>
</body>
</html>
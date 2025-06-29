<?php
session_start();
require_once('../config/db.php');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim($_POST['fullName'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirmPassword'] ?? '';

  if (empty($fullName) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
    $error = 'Please fill in all fields.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
  } elseif (!preg_match('/^[0-9]{11}$/', $phone)) {
    $error = 'Invalid phone number format.';
  } elseif (strlen($password) < 8) {
    $error = 'Password must be at least 8 characters.';
  } elseif ($password !== $confirmPassword) {
    $error = 'Passwords do not match.';
  } else {
    $conn = getDbConnection();
    // Check if email or phone already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $error = 'Email or phone already registered.';
    } else {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $stmt->close();
      $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $fullName, $email, $phone, $hashed_password);
      if ($stmt->execute()) {
        $success = 'Registration successful! You can now <a href="login.php">login</a>.';
      } else {
        $error = 'Registration failed. Please try again.';
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | Naija eHub</title>
  <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
  <div class="auth-container">
    <div class="auth-card">
      <div class="logo">
        <img src="../assets/img/logo-icon.png" alt="Naija eHub Logo">
      </div>
      
      <h2>Create Account</h2>
      
      <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
      <?php elseif ($success): ?>
        <div class="success-message"><?php echo $success; ?></div>
      <?php endif; ?>
      
      <form id="registerForm" method="POST" action="">
        <div class="form-group">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" name="fullName" required>
        </div>
        
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" pattern="[0-9]{11}" required>
          <small>11-digit Nigerian number (e.g., 08012345678)</small>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" minlength="8" required>
          <small>Minimum 8 characters</small>
        </div>
        
        <div class="form-group">
          <label for="confirmPassword">Confirm Password</label>
          <input type="password" id="confirmPassword" name="confirmPassword" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Register</button>
      </form>
      
      <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Login</a></p>
      </div>
    </div>
  </div>
  
  <script src="../js/auth.js"></script>
</body>
</html>
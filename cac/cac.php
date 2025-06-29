<?php
session_start();
require_once('../config/db.php');

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header('Location: ../auth/login.php');
  exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CAC Services | Naija eHub</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
      <header class="header">
        <button class="toggle-sidebar" onclick="toggleSidebar()" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        <h2>CAC Services</h2>
        <div>
          <button class="btn" onclick="toggleAccessibilityPanel()" aria-label="Toggle Accessibility Options">Accessibility</button>
        </div>
      </header>
      <!-- Service Header -->
      <section class="service-header">
        <div class="header-content">
          <h2>CAC Services</h2>
          <p>Verify, update or retrieve your Corporate Affairs Commission registration</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-building"></i>
        </div>
      </section>
      
      <!-- Service Options -->
      <section class="service-options">
        <div class="option-card active" data-option="register">
          <i class="fas fa-file-signature"></i>
          <h4>CAC Registration</h4>
          <p>Register your business with the Corporate Affairs Commission</p>
          <button class="btn btn-outline" onclick="window.location.href='register_cac.php'">Start Registration</button>
        </div>
        
        <div class="option-card" data-option="verify">
          <i class="fas fa-search"></i>
          <h4>Verify CAC</h4>
          <p>Confirm your CAC registration details</p>
          <button class="btn btn-outline" onclick="window.location.href='verify_cac.php'">Start Verification</button>
        </div>
        
        <div class="option-card" data-option="update">
          <i class="fas fa-edit"></i>
          <h4>Update Details</h4>
          <p>Modify your registered CAC information</p>
          <button class="btn btn-outline" onclick="window.location.href='update_cac.php'">Update Details</button>
        </div>
        
        <div class="option-card" data-option="retrieve">
          <i class="fas fa-key"></i>
          <h4>Retrieve CAC</h4>
          <p>Recover lost CAC registration number</p>
          <button class="btn btn-outline" onclick="window.location.href='retrieve_cac.php'">Retrieve CAC</button>
        </div>
      </section>
      <?php include __DIR__ . '/../includes/spinner.php'; ?>
      <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
      <?php include __DIR__ . '/../includes/footer.php'; ?>
      <script src="../js/script.js"></script>
</body>
</html>

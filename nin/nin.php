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
  <title>NIN Services | Naija eHub</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-container">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
      <?php include __DIR__ . '/../includes/header.php'; ?>
      <!-- Service Header -->
      <section class="service-header">
        <div class="header-content">
          <h2>NIN Services</h2>
          <p>Verify, update or retrieve your National Identification Number</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-id-card"></i>
        </div>
      </section>
      
      <!-- Service Options -->
      <section class="service-options">
        <div class="option-card active" data-option="verify">
          <i class="fas fa-search"></i>
          <h4>Verify NIN</h4>
          <p>Confirm your NIN details</p>
          <button class="btn btn-outline" onclick="window.location.href='verify_nin.php'">Start Verification</button>
        </div>
        
        <div class="option-card" data-option="update">
          <i class="fas fa-edit"></i>
          <h4>Update Details</h4>
          <p>Modify your registered information</p>
          <button class="btn btn-outline" onclick="window.location.href='update_nin.php'">Update Details</button>
        </div>
        
        <div class="option-card" data-option="ipe">
          <i class="fas fa-bolt"></i>
          <h4>Instant IPE</h4>
          <p>Get instant IPE services for your NIN</p>
          <button class="btn btn-outline" onclick="window.location.href='instant_ipe.php'">Start IPE</button>
        </div>

        <div class="option-card" data-option="validation">
          <i class="fas fa-check-circle"></i>
          <h4>Instant NIN Validation</h4>
          <p>Validate your NIN instantly</p>
          <button class="btn btn-outline" onclick="window.location.href='instant_nin_validation.php'">Validate NIN</button>
        </div>

        <div class="option-card" data-option="personalization">
          <i class="fas fa-user-tag"></i>
          <h4>Personalization</h4>
          <p>Personalize your NIN profile and details</p>
          <button class="btn btn-outline" onclick="window.location.href='personalization.php'">Personalize</button>
        </div>
      </section>
      
      <script src="../js/script.js"></script>
      <?php include __DIR__ . '/../includes/spinner.php'; ?>
      <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
      <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
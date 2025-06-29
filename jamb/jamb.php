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
  <title>JAMB Services | Naija eHub</title>
  <link rel="stylesheet" href="../css/nin.css">
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
          <h2>JAMB Services</h2>
          <p>Register, check results, or correct your JAMB data</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-graduation-cap"></i>
        </div>
      </section>
      
      <!-- Service Options -->
      <section class="service-options">
        <div class="option-card active" data-option="register">
          <i class="fas fa-file-signature"></i>
          <h4>JAMB Registration</h4>
          <p>Register for the Joint Admissions and Matriculation Board exam</p>
          <button class="btn btn-outline" onclick="window.location.href='register_jamb.php'">Start Registration</button>
        </div>
        
        <div class="option-card" data-option="epin">
          <i class="fas fa-barcode"></i>
          <h4>e-PIN Purchase</h4>
          <p>Buy your JAMB e-PIN for registration and other services</p>
          <button class="btn btn-outline" onclick="window.location.href='purchase_jamb_epin.php'">Buy e-PIN</button>
        </div>
        
        <div class="option-card" data-option="result">
          <i class="fas fa-file-alt"></i>
          <h4>Check Result</h4>
          <p>View your JAMB examination result</p>
          <button class="btn btn-outline" onclick="window.location.href='check_jamb_result.php'">Check Result</button>
        </div>
        
        <div class="option-card" data-option="correction">
          <i class="fas fa-edit"></i>
          <h4>Correction of Data</h4>
          <p>Request correction of your JAMB registration data</p>
          <button class="btn btn-outline" onclick="window.location.href='jamb_correction.php'">Correct Data</button>
        </div>
      </section>
    </main>
  </div>
  <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../js/script.js"></script>
</body>
</html>
<?php include __DIR__ . '/../includes/spinner.php'; ?>

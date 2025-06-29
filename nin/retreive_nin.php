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
  <title>Retrieve BVN | Naija eHub</title>
  <link rel="stylesheet" href="../css/nin.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .switch-cards { display: flex; gap: 1rem; margin-bottom: 2rem; }
    .switch-card {
      flex: 1;
      padding: 1.5rem;
      border: 2px solid #ccc;
      border-radius: 8px;
      cursor: pointer;
      text-align: center;
      background: #f9f9f9;
      transition: border-color 0.2s, background 0.2s;
    }
    .switch-card.active {
      border-color: #007bff;
      background: #e6f0ff;
    }
    .bvn-form-section { display: none; }
    .bvn-form-section.active { display: block; }
    .bvn-instructions {
      background: #fffbe6;
      border: 1px solid #ffe58f;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 6px;
      font-size: 0.97rem;
    }
    .bvn-form label { display: block; margin-top: 1rem; }
    .bvn-form input, .bvn-form select {
      width: 100%; padding: 0.5rem; margin-top: 0.3rem; border-radius: 4px; border: 1px solid #ccc;
    }
    .bvn-form button { margin-top: 1.5rem; }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- ...existing code for sidebar and header... -->
     <?php include __DIR__ . '../includes/sidebar.php'; ?>
    <main class="main-content">
     <?php include __DIR__ . '../includes/header.php'; ?>
      <section class="service-header">
        <div class="header-content">
          <h2>Retrieve BVN</h2>
          <p>Choose your preferred BVN retrieval method</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-key"></i>
        </div>
      </section>

      <div class="switch-cards">
        <div class="switch-card active" id="centralRiskCard" onclick="showForm('centralRisk')">
          <i class="fas fa-database fa-2x"></i>
          <h4>Retrieve BVN by Central Risk</h4>
        </div>
        <div class="switch-card" id="phoneCard" onclick="showForm('phone')">
          <i class="fas fa-phone fa-2x"></i>
          <h4>Retrieve BVN by Phone No.</h4>
        </div>
      </div>

      <!-- Central Risk Form -->
      <div class="bvn-form-section active" id="centralRiskForm">
        <div class="bvn-instructions">
          <strong>Instruction</strong><br>
          This Service will cost you ₦1000<br><br>
          &#x2611; After Submitting your work<br>
          &#x2611; You will receive an email notification upon process and successful<br>
          &#x2611; Copy reference No from My Transactions/Transaction History on your dashboard or from email notification<br>
          &#x2611; Paste it on TRACKS from the Menu bar or click My Services Status from dashboard<br>
          &#x2611; Track order to get the result, always try to check the status and the remark<br>
          <b>NB:</b> The BVN retriever normally takes 15 minutes to 1 hour to be out. Thanks.
        </div>
        <form class="bvn-form" method="post" action="process_retrieve_bvn_central.php">
          <label>Agent Code
            <input type="text" name="agent_code" required>
          </label>
          <label>BMST Code
            <input type="text" name="bmst_code" required>
          </label>
          <label>Ticket ID
            <input type="text" name="ticket_id" required>
          </label>
          <label>Agent Name
            <input type="text" name="agent_name" required>
          </label>
          <label>Means of Identification
            <input type="text" name="means_of_id" required>
          </label>
          <button class="btn btn-outline" type="submit">Submit</button>
        </form>
      </div>

      <!-- Phone No. Form -->
      <div class="bvn-form-section" id="phoneForm">
        <div class="bvn-instructions">
          <strong>Instruction</strong><br>
          This Service will cost you ₦1000<br><br>
          &#x2611; After Submitting your work<br>
          &#x2611; You will receive an email notification upon process and successful<br>
          &#x2611; Copy reference No from My Transactions/Transaction History on your dashboard or from email notification<br>
          &#x2611; Paste it on TRACKS from the Menu bar or click My Services Status from dashboard<br>
          &#x2611; Track order to get the result, always try to check the status and the remark<br>
          <b>NB:</b> The BVN retriever normally takes 15 minutes to 1 hour to be out. Thanks.
        </div>
        <form class="bvn-form" method="post" action="process_retrieve_bvn_phone.php">
          <label>Full Name
            <input type="text" name="full_name" required>
          </label>
          <label>Date Of Birth
            <input type="date" name="dob" required>
          </label>
          <label>Phone
            <input type="tel" name="phone" required>
          </label>
          <button class="btn btn-outline" type="submit">Submit</button>
        </form>
      </div>
    </main>
  </div>
  <script>
    function showForm(type) {
      document.getElementById('centralRiskCard').classList.remove('active');
      document.getElementById('phoneCard').classList.remove('active');
      document.getElementById('centralRiskForm').classList.remove('active');
      document.getElementById('phoneForm').classList.remove('active');
      if (type === 'centralRisk') {
        document.getElementById('centralRiskCard').classList.add('active');
        document.getElementById('centralRiskForm').classList.add('active');
      } else {
        document.getElementById('phoneCard').classList.add('active');
        document.getElementById('phoneForm').classList.add('active');
      }
    }
  </script>
  <script src="../js/script.js"></script>
 <?php include __DIR__ . '../includes/spinner.php'; ?>
  <?php include __DIR__ . '../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '../includes/footer.php'; ?>
</body>
</html>

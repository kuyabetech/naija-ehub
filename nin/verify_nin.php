<?php
session_start();
require_once('../config/db.php');

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
  <title>Verify NIN | Naija eHub</title>
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .switch-cards { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .switch-card {
      flex: 1 1 150px;
      padding: 1.2rem;
      border: 2px solid #ccc;
      border-radius: 8px;
      cursor: pointer;
      text-align: center;
      background: #f9f9f9;
      transition: border-color 0.2s, background 0.2s;
      min-width: 150px;
    }
    .switch-card.active {
      border-color: #007bff;
      background: #e6f0ff;
    }
    .nin-form-section { display: none; }
    .nin-form-section.active { display: block; }
    .nin-form label { display: block; margin-top: 1rem; text-align: left; }
    .nin-form input, .nin-form select {
      width: 100%; padding: 0.5rem; margin-top: 0.3rem; border-radius: 4px; border: 1px solid #ccc;
    }
    .nin-form button { margin-top: 1.5rem; }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- ...existing code for sidebar and header... -->
     <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
      <section class="service-header">
        <div class="header-content">
          <h2>Verify NIN</h2>
          <p>Select a verification version</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-search"></i>
        </div>
      </section>

      <div class="switch-cards">
        <div class="switch-card active" id="ver1Card" onclick="showNinForm('ver1')">
          <i class="fas fa-id-card fa-2x"></i>
          <h4>Verification v1</h4>
        </div>
        <div class="switch-card" id="ver2Card" onclick="showNinForm('ver2')">
          <i class="fas fa-id-card fa-2x"></i>
          <h4>Verification v2</h4>
        </div>
        <div class="switch-card" id="ver3Card" onclick="showNinForm('ver3')">
          <i class="fas fa-id-card fa-2x"></i>
          <h4>Verification v3</h4>
        </div>
        <div class="switch-card" id="ver4Card" onclick="showNinForm('ver4')">
          <i class="fas fa-id-card fa-2x"></i>
          <h4>Verification v4</h4>
        </div>
        <div class="switch-card" id="ver5Card" onclick="showNinForm('ver5')">
          <i class="fas fa-id-card fa-2x"></i>
          <h4>Verification v5</h4>
        </div>
      </div>

      <!-- Verification Forms -->
      <div class="nin-form-section active" id="ver1Form">
        <form class="nin-form" method="post" action="../ajax/process_verify_nin_v1.php" id="ver1FormElem">
          <label>Verification Method
            <select name="method" id="ver1Method" required>
              <option value="nin">NIN</option>
              <option value="phone">Phone Number</option>
            </select>
          </label>
          <div id="ver1NinField">
            <label>NIN
              <input type="text" name="nin" id="ver1NinInput" required>
            </label>
          </div>
          <div id="ver1PhoneField" style="display:none;">
            <label>Phone Number
              <input type="tel" name="phone" id="ver1PhoneInput">
            </label>
          </div>
          <button class="btn btn-outline" type="submit">Verify</button>
        </form>
      </div>
      <div class="nin-form-section" id="ver2Form">
        <form class="nin-form" method="post" action="process_verify_nin_v2.php">
          <label>NIN
            <input type="text" name="nin" required>
          </label>
          <label>Date of Birth
            <input type="date" name="dob" required>
          </label>
          <button class="btn btn-outline" type="submit">Verify</button>
        </form>
      </div>
      <div class="nin-form-section" id="ver3Form">
        <form class="nin-form" method="post" action="process_verify_nin_v3.php">
          <label>First Name
            <input type="text" name="first_name" required>
          </label>
          <label>Last Name
            <input type="text" name="last_name" required>
          </label>
          <label>NIN
            <input type="text" name="nin" required>
          </label>
          <button class="btn btn-outline" type="submit">Verify</button>
        </form>
      </div>
      <div class="nin-form-section" id="ver4Form">
        <form class="nin-form" method="post" action="process_verify_nin_v4.php">
          <label>NIN
            <input type="text" name="nin" required>
          </label>
          <label>Phone Number
            <input type="tel" name="phone" required>
          </label>
          <button class="btn btn-outline" type="submit">Verify</button>
        </form>
      </div>
      <div class="nin-form-section" id="ver5Form">
        <form class="nin-form" method="post" action="process_verify_nin_v5.php">
          <label>NIN
            <input type="text" name="nin" required>
          </label>
          <label>Email
            <input type="email" name="email" required>
          </label>
          <button class="btn btn-outline" type="submit">Verify</button>
        </form>
      </div>
    </main>
  </div>
  <script>
    function showNinForm(version) {
      // Remove active from all cards and forms
      for (let i = 1; i <= 5; i++) {
        document.getElementById('ver' + i + 'Card').classList.remove('active');
        document.getElementById('ver' + i + 'Form').classList.remove('active');
      }
      // Activate selected
      document.getElementById(version + 'Card').classList.add('active');
      document.getElementById(version + 'Form').classList.add('active');
    }

    // Version 1: Toggle NIN/Phone input
    document.addEventListener('DOMContentLoaded', function() {
      var methodSelect = document.getElementById('ver1Method');
      var ninField = document.getElementById('ver1NinField');
      var phoneField = document.getElementById('ver1PhoneField');
      var ninInput = document.getElementById('ver1NinInput');
      var phoneInput = document.getElementById('ver1PhoneInput');
      methodSelect.addEventListener('change', function() {
        if (this.value === 'nin') {
          ninField.style.display = '';
          phoneField.style.display = 'none';
          ninInput.required = true;
          phoneInput.required = false;
        } else {
          ninField.style.display = 'none';
          phoneField.style.display = '';
          ninInput.required = false;
          phoneInput.required = true;
        }
      });
    });
  </script>
  <script src="/../js/script.js"></script>
   <?php include __DIR__ . '/../includes/spinner.php'; ?>
  <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
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
  <title>Update NIN | Naija eHub</title>
  <link rel="stylesheet" href="../css/nin.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .single-card {
      max-width: 500px;
      margin: 2rem auto;
      padding: 2rem;
      border: 2px solid #ccc;
      border-radius: 8px;
      background: #f9f9f9;
      text-align: center;
    }
    .bvn-instructions {
      background: #fffbe6;
      border: 1px solid #ffe58f;
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: 6px;
      font-size: 0.97rem;
    }
    .nin-form label { display: block; margin-top: 1rem; text-align: left; }
    .nin-form input, .nin-form select {
      width: 100%; padding: 0.5rem; margin-top: 0.3rem; border-radius: 4px; border: 1px solid #ccc;
    }
    .nin-form button { margin-top: 1.5rem; }
    .modification-price {
      margin: 1rem 0;
      font-weight: bold;
      color: #007bff;
    }
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
          <h2>Update NIN</h2>
          <p>Select the type of modification you want to perform</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-edit"></i>
        </div>
      </section>

      <div class="single-card">
        <form class="nin-form" method="post" action="process_update_nin.php" enctype="multipart/form-data">
          <label>Type of Modification
            <select name="modification_type" id="ninModificationType" onchange="showNinPriceAndForm()">
              <option value="">Select Modification</option>
              <option value="change_of_name">Change of Name</option>
              <option value="date_of_birth">Date of Birth</option>
              <option value="change_of_address">Change of Address</option>
              <option value="change_of_phone">Change of Phone Number</option>
              <option value="rearrangement_of_name">Rearrangement of Name</option>
            </select>
          </label>
          <div class="modification-price" id="ninPrice"></div>
          <div id="ninModificationForm"></div>
        </form>
      </div>
    </main>
  </div>
  <script>
    // Prices for each modification type
    const ninModificationPrices = {
      change_of_name: "₦2,000",
      date_of_birth: "₦2,500",
      change_of_address: "₦1,500",
      change_of_phone: "₦1,000",
      rearrangement_of_name: "₦2,000"
    };

    // Example form fields for each modification type
    function getNinModificationFields(type) {
      switch(type) {
        case "change_of_name":
          return `
            <label>Old Name <input type="text" name="old_name" required></label>
            <label>New Name <input type="text" name="new_name" required></label>
            <label>Supporting Document <input type="file" name="support_doc" accept=".jpg,.jpeg,.png,.pdf" required></label>
            <button class="btn btn-outline" type="submit">Submit</button>
          `;
        case "date_of_birth":
          return `
            <label>Current Date of Birth <input type="date" name="current_dob" required></label>
            <label>Correct Date of Birth <input type="date" name="correct_dob" required></label>
            <label>Supporting Document <input type="file" name="support_doc" accept=".jpg,.jpeg,.png,.pdf" required></label>
            <button class="btn btn-outline" type="submit">Submit</button>
          `;
        case "change_of_address":
          return `
            <label>Old Address <input type="text" name="old_address" required></label>
            <label>New Address <input type="text" name="new_address" required></label>
            <label>Supporting Document <input type="file" name="support_doc" accept=".jpg,.jpeg,.png,.pdf" required></label>
            <button class="btn btn-outline" type="submit">Submit</button>
          `;
        case "change_of_phone":
          return `
            <label>Old Phone Number <input type="tel" name="old_phone" required></label>
            <label>New Phone Number <input type="tel" name="new_phone" required></label>
            <button class="btn btn-outline" type="submit">Submit</button>
          `;
        case "rearrangement_of_name":
          return `
            <label>Current Name Arrangement <input type="text" name="current_arrangement" required></label>
            <label>Desired Name Arrangement <input type="text" name="desired_arrangement" required></label>
            <label>Supporting Document <input type="file" name="support_doc" accept=".jpg,.jpeg,.png,.pdf" required></label>
            <button class="btn btn-outline" type="submit">Submit</button>
          `;
        default:
          return "";
      }
    }

    function showNinPriceAndForm() {
      const select = document.getElementById('ninModificationType');
      const priceDiv = document.getElementById('ninPrice');
      const formDiv = document.getElementById('ninModificationForm');
      const value = select.value;
      if (ninModificationPrices[value]) {
        priceDiv.textContent = "Price: " + ninModificationPrices[value];
        formDiv.innerHTML = getNinModificationFields(value);
      } else {
        priceDiv.textContent = "";
        formDiv.innerHTML = "";
      }
    }

    // Show popup alert if NIN update was successful
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('success') === '1') {
        showSuccessPopup("Your NIN update request has been submitted. Our team will process it shortly.");
      }
    });

    function showSuccessPopup(message) {
      // Create popup
      let popup = document.createElement('div');
      popup.style.position = 'fixed';
      popup.style.top = '30px';
      popup.style.left = '50%';
      popup.style.transform = 'translateX(-50%)';
      popup.style.background = '#067c3c';
      popup.style.color = '#fff';
      popup.style.padding = '1rem 2.2rem';
      popup.style.borderRadius = '8px';
      popup.style.boxShadow = '0 4px 24px rgba(0,0,0,0.13)';
      popup.style.fontSize = '1.08rem';
      popup.style.zIndex = '99999';
      popup.style.textAlign = 'center';
      popup.innerHTML = `<span style="margin-right:0.7rem;"><i class="fas fa-check-circle"></i></span>${message}`;
      document.body.appendChild(popup);
      setTimeout(() => {
        popup.style.transition = 'opacity 0.4s';
        popup.style.opacity = '0';
        setTimeout(() => popup.remove(), 400);
      }, 3200);
    }
  </script>
  <script src="../js/script.js"></script>
 <?php include __DIR__ . '../includes/spinner.php'; ?>
  <?php include __DIR__ . '../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '../includes/footer.php'; ?>
</body>
</html>
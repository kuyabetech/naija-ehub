<?php
session_start();
require_once('../config/db.php');

// Monnify API credentials
define('MONNIFY_API_KEY', 'MK_TEST_GKHF7ZMEGJ');
define('MONNIFY_SECRET_KEY', '4EGGA2D90L3D88QGU3D6QXZBQN0FXFKM');
define('MONNIFY_BASE_URL', 'https://sandbox.monnify.com');

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, email, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $wallet_balance);
$stmt->fetch();
$stmt->close();
$conn->close();

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Monnify payment link
$monnify_contract_code = '3380031445';
$monnify_payment_link = MONNIFY_BASE_URL . '/checkout/' .
    '?amount=' . urlencode(2000) . // Default amount, updated dynamically
    '¤cy=NGN' .
    '&reference=' . urlencode("bvn_update_{$user_id}_" . time()) .
    '&customerName=' . urlencode($full_name ?: "User {$user_id}") .
    '&customerEmail=' . urlencode($email ?: "user{$user_id}@naijaehub.com") .
    '&paymentDescription=' . urlencode('BVN Update Fee') .
    '&contractCode=' . urlencode($monnify_contract_code);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Naija eHub - Update your BVN details via CAFE or Bank.">
  <title>Update BVN | Naija eHub</title>
  <link rel="stylesheet" href="../css/main.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
   <?php require_once '../includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <button class="toggle-sidebar" onclick="toggleSidebar()" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        <h2>Update BVN</h2>
        <div>
          <button class="btn" onclick="toggleAccessibilityPanel()" aria-label="Toggle Accessibility Options">Accessibility</button>
        </div>
      </header>

      <!-- Service Header -->
      <section class="service-header">
        <div class="header-content">
          <h2>Update BVN</h2>
          <p>Select your update method and type of modification</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-edit"></i>
        </div>
      </section>

      <!-- Switch Cards -->
      <div class="switch-cards">
        <div class="switch-card active" id="cafeCard" onclick="showUpdateForm('cafe')" role="button" tabindex="0" aria-label="CAFE BVN Update">
          <i class="fas fa-store"></i>
          <h4>CAFE BVN</h4>
          <p>Update via accredited cybercafe</p>
        </div>
        <div class="switch-card" id="bankCard" onclick="showUpdateForm('bank')" role="button" tabindex="0" aria-label="Bank BVN Update">
          <i class="fas fa-university"></i>
          <h4>Bank BVN</h4>
          <p>Update via your bank</p>
        </div>
      </div>

      <!-- CAFE BVN Form -->
      <div class="bvn-form-section active" id="cafeFormSection">
        <div class="bvn-instructions">
          <p>Submit your BVN update request through an accredited cybercafe. Ensure all details are accurate and upload required documents.</p>
        </div>
        <form class="bvn-form" id="cafeBvnForm" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_bvn">
          <input type="hidden" name="update_type" value="cafe">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <label for="bvnNumber">BVN Number</label>
          <input type="text" id="bvnNumberCafe" name="bvn" maxlength="11" pattern="\d{11}" placeholder="Enter 11-digit BVN" required aria-required="true">
          <label for="cafeModificationType">Type of Modification</label>
          <select name="modification_type" id="cafeModificationType" onchange="showPriceAndForm('cafe')" aria-label="Select Modification Type" required>
            <option value="">Select Modification</option>
            <option value="change_of_name">Change of Name</option>
            <option value="date_of_birth">Date of Birth</option>
            <option value="change_of_address">Change of Address</option>
            <option value="change_of_phone">Change of Phone Number</option>
            <option value="rearrangement_of_name">Rearrangement of Name</option>
          </select>
          <label for="paymentMethodCafe">Payment Method</label>
          <select name="payment_method" id="paymentMethodCafe" onchange="togglePaymentMethod('cafe')" aria-label="Select Payment Method" required>
            <option value="wallet">Wallet (Balance: ₦<?php echo number_format($wallet_balance, 2); ?>)</option>
            <option value="card">Card/USSD</option>
          </select>
          <div class="modification-price" id="cafePrice"></div>
          <div id="cafeModificationForm"></div>
          <div id="cafeCardPayment" style="display: none;">
            <form id="monnifyCafeForm" target="_blank" action="<?php echo htmlspecialchars($monnify_payment_link); ?>" method="get">
              <input type="hidden" id="monnifyCafeAmount" name="amount" value="2000">
              <button type="submit" class="btn btn-primary" aria-label="Pay with Card/USSD">Pay with Card/USSD</button>
            </form>
          </div>
          <div id="cafeWalletPayment">
            <button type="submit" class="btn btn-primary" aria-label="Submit CAFE BVN Update">Submit Request</button>
          </div>
          <div id="cafeBvnMsg" style="margin-top: 1rem;"></div>
        </form>
      </div>

      <!-- Bank BVN Form -->
      <div class="bvn-form-section" id="bankFormSection">
        <div class="bvn-instructions">
          <p>Submit your BVN update request through your bank. Provide accurate details and upload required documents.</p>
        </div>
        <form class="bvn-form" id="bankBvnForm" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_bvn">
          <input type="hidden" name="update_type" value="bank">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <label for="bvnNumberBank">BVN Number</label>
          <input type="text" id="bvnNumberBank" name="bvn" maxlength="11" pattern="\d{11}" placeholder="Enter 11-digit BVN" required aria-required="true">
          <label for="bankModificationType">Type of Modification</label>
          <select name="modification_type" id="bankModificationType" onchange="showPriceAndForm('bank')" aria-label="Select Modification Type" required>
            <option value="">Select Modification</option>
            <option value="change_of_name">Change of Name</option>
            <option value="date_of_birth">Date of Birth</option>
            <option value="change_of_address">Change of Address</option>
            <option value="change_of_phone">Change of Phone Number</option>
            <option value="rearrangement_of_name">Rearrangement of Name</option>
          </select>
          <label for="paymentMethodBank">Payment Method</label>
          <select name="payment_method" id="paymentMethodBank" onchange="togglePaymentMethod('bank')" aria-label="Select Payment Method" required>
            <option value="wallet">Wallet (Balance: ₦<?php echo number_format($wallet_balance, 2); ?>)</option>
            <option value="card">Card/USSD</option>
          </select>
          <div class="modification-price" id="bankPrice"></div>
          <div id="bankModificationForm"></div>
          <div id="bankCardPayment" style="display: none;">
            <form id="monnifyBankForm" target="_blank" action="<?php echo htmlspecialchars($monnify_payment_link); ?>" method="get">
              <input type="hidden" id="monnifyBankAmount" name="amount" value="2000">
              <button type="submit" class="btn btn-primary" aria-label="Pay with Card/USSD">Pay with Card/USSD</button>
            </form>
          </div>
          <div id="bankWalletPayment">
            <button type="submit" class="btn btn-primary" aria-label="Submit Bank BVN Update">Submit Request</button>
          </div>
          <div id="bankBvnMsg" style="margin-top: 1rem;"></div>
        </form>
      </div>

      <!-- Success Popup -->
      <div id="successPopup" class="popup" role="alert" aria-live="assertive">
        <span><i class="fas fa-check-circle"></i></span>
        <span id="popupMessage"></span>
        <button class="close-popup" onclick="closePopup()" aria-label="Close Success Popup">×</button>
      </div>
    </main>
  </div>

  <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <?php include __DIR__ . '/../includes/spinner.php'; ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Prices for each modification type
    const modificationPrices = {
      change_of_name: 2000,
      date_of_birth: 2500,
      change_of_address: 1500,
      change_of_phone: 1000,
      rearrangement_of_name: 2000
    };

    // Form fields for each modification type
    function getModificationFields(type) {
      switch (type) {
        case 'change_of_name':
          return `
            <label for="old_name">Old Name</label>
            <input type="text" name="old_name" id="old_name" required aria-required="true" placeholder="Enter old name">
            <label for="new_name">New Name</label>
            <input type="text" name="new_name" id="new_name" required aria-required="true" placeholder="Enter new name">
            <label for="support_doc">Supporting Document (JPG, PNG, PDF)</label>
            <input type="file" name="support_doc" id="support_doc" accept=".jpg,.jpeg,.png,.pdf" required aria-required="true">
          `;
        case 'date_of_birth':
          return `
            <label for="current_dob">Current Date of Birth</label>
            <input type="date" name="current_dob" id="current_dob" required aria-required="true">
            <label for="correct_dob">Correct Date of Birth</label>
            <input type="date" name="correct_dob" id="correct_dob" required aria-required="true">
            <label for="support_doc">Supporting Document (JPG, PNG, PDF)</label>
            <input type="file" name="support_doc" id="support_doc" accept=".jpg,.jpeg,.png,.pdf" required aria-required="true">
          `;
        case 'change_of_address':
          return `
            <label for="old_address">Old Address</label>
            <input type="text" name="old_address" id="old_address" required aria-required="true" placeholder="Enter old address">
            <label for="new_address">New Address</label>
            <input type="text" name="new_address" id="new_address" required aria-required="true" placeholder="Enter new address">
            <label for="support_doc">Supporting Document (JPG, PNG, PDF)</label>
            <input type="file" name="support_doc" id="support_doc" accept=".jpg,.jpeg,.png,.pdf" required aria-required="true">
          `;
        case 'change_of_phone':
          return `
            <label for="old_phone">Old Phone Number</label>
            <input type="tel" name="old_phone" id="old_phone" required aria-required="true" placeholder="Enter old phone number">
            <label for="new_phone">New Phone Number</label>
            <input type="tel" name="new_phone" id="new_phone" required aria-required="true" placeholder="Enter new phone number">
          `;
        case 'rearrangement_of_name':
          return `
            <label for="current_arrangement">Current Name Arrangement</label>
            <input type="text" name="current_arrangement" id="current_arrangement" required aria-required="true" placeholder="Enter current name arrangement">
            <label for="desired_arrangement">Desired Name Arrangement</label>
            <input type="text" name="desired_arrangement" id="desired_arrangement" required aria-required="true" placeholder="Enter desired name arrangement">
            <label for="support_doc">Supporting Document (JPG, PNG, PDF)</label>
            <input type="file" name="support_doc" id="support_doc" accept=".jpg,.jpeg,.png,.pdf" required aria-required="true">
          `;
        default:
          return '';
      }
    }

    function showUpdateForm(type) {
      $('#cafeCard').removeClass('active');
      $('#bankCard').removeClass('active');
      $('#cafeFormSection').removeClass('active');
      $('#bankFormSection').removeClass('active');
      if (type === 'cafe') {
        $('#cafeCard').addClass('active');
        $('#cafeFormSection').addClass('active');
      } else {
        $('#bankCard').addClass('active');
        $('#bankFormSection').addClass('active');
      }
      showPriceAndForm(type);
    }

    function showPriceAndForm(type) {
      const select = $(`#${type}ModificationType`);
      const priceDiv = $(`#${type}Price`);
      const formDiv = $(`#${type}ModificationForm`);
      const value = select.val();
      if (modificationPrices[value]) {
        priceDiv.text(`Price: ₦${modificationPrices[value].toLocaleString()}`);
        formDiv.html(getModificationFields(value));
        $(`#monnify${type.charAt(0).toUpperCase() + type.slice(1)}Amount`).val(modificationPrices[value]);
      } else {
        priceDiv.text('');
        formDiv.html('');
      }
    }

    function togglePaymentMethod(type) {
      const paymentMethod = $(`#paymentMethod${type.charAt(0).toUpperCase() + type.slice(1)}`).val();
      $(`#${type}CardPayment`).toggle(paymentMethod === 'card');
      $(`#${type}WalletPayment`).toggle(paymentMethod === 'wallet');
    }

    function showSuccessPopup(message) {
      $('#popupMessage').text(message);
      $('#successPopup').show();
      setTimeout(() => {
        $('#successPopup').css({ opacity: 0, transition: 'opacity 0.4s' });
        setTimeout(() => $('#successPopup').hide().css({ opacity: 1 }), 400);
      }, 3200);
    }

    function closePopup() {
      $('#successPopup').hide();
    }

    function toggleSidebar() {
      $('#sidebar').toggleClass('active');
    }

    $(document).ready(function() {
      // Handle form submissions
      $('#cafeBvnForm, #bankBvnForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const msg = form.find('[id$="BvnMsg"]');
        const formData = new FormData(this);
        msg.text('');

        // Client-side file validation
        const fileInput = form.find('input[type="file"]');
        if (fileInput.length && fileInput[0].files.length) {
          const file = fileInput[0].files[0];
          const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
          if (!validTypes.includes(file.type)) {
            msg.css('color', '#d32f2f').text('Invalid file type. Use JPG, PNG, or PDF.');
            return;
          }
          if (file.size > 5 * 1024 * 1024) {
            msg.css('color', '#d32f2f').text('File size exceeds 5MB limit.');
            return;
          }
        }

        fetch('../ajax/process_bvn_update.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          msg.css('color', data.success ? '#067c3c' : '#d32f2f');
          msg.text(data.message);
          if (data.success) {
            showSuccessPopup(data.message);
            form[0].reset();
            showPriceAndForm(form.find('input[name="update_type"]').val());
            setTimeout(() => location.reload(), 3200);
          }
        })
        .catch(() => {
          msg.css('color', '#d32f2f').text('Error submitting request. Please try again.');
        });
      });

      // Dynamic Monnify payment link
      $('#monnifyCafeForm, #monnifyBankForm').on('submit', function() {
        const amount = $(this).find('input[name="amount"]').val();
        const base = '<?php echo MONNIFY_BASE_URL . '/checkout/'; ?>';
        const params = [
          `amount=${encodeURIComponent(amount)}`,
          'currency=NGN',
          `reference=${encodeURIComponent('bvn_update_<?php echo $user_id; ?>_' + Date.now())}`,
          `customerName=${encodeURIComponent('<?php echo $full_name ?: "User {$user_id}"; ?>')}`,
          `customerEmail=${encodeURIComponent('<?php echo $email ?: "user{$user_id}@naijaehub.com"; ?>')}`,
          `paymentDescription=${encodeURIComponent('BVN Update Fee')}`,
          `contractCode=${encodeURIComponent('<?php echo $monnify_contract_code; ?>')}`
        ].join('&');
        this.action = base + '?' + params;
      });

      // Show success popup if redirected with success
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('success') === '1') {
        showSuccessPopup('Your BVN update request has been submitted. Our team will process it shortly.');
      }
    });
  </script>
</body>
</html>
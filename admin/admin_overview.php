<?php

session_start();
require_once('config/db.php');

// Monnify API credentials
define('MONNIFY_API_KEY', 'MK_TEST_GKHF7ZMEGJ');
define('MONNIFY_SECRET_KEY', '4EGGA2D90L3D88QGU3D6QXZBQN0FXFKM'); // Add your secret key here
define('MONNIFY_BASE_URL', 'https://sandbox.monnify.com');

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header('Location: auth/login.php');
  exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $wallet_balance);
$stmt->fetch();
$stmt->close();

// Fetch virtual account details or auto-generate if not assigned
$account_number = "Not assigned";
$bank_name = "Naija eHub MFB";
$account_name = $full_name;
$va_stmt = $conn->prepare("SELECT account_number, bank_name FROM virtual_accounts WHERE user_id = ?");
$va_stmt->bind_param("i", $user_id);
$va_stmt->execute();
$va_stmt->bind_result($account_number, $bank_name);
$va_stmt->fetch();
$va_stmt->close();

if ($account_number === null || $account_number === "Not assigned" || $account_number === "") {
    // --- Monnify API: Auto-generate virtual account number ---
    $monnify_api_key = 'MK_TEST_GKHF7ZMEGJ';
    $monnify_secret = '4EGGA2D90L3D88QGU3D6QXZBQN0FXFKM';
    $monnify_contract_code = '3380031445';

    // 1. Get Monnify access token using provided sample
    $ch = curl_init();
    $headers = array(
        'Content-Type:application/json',
        'Authorization: Basic ' . base64_encode($monnify_api_key . ":" . $monnify_secret)
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_URL, "https://sandbox.monnify.com/api/v1/auth/login");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($output, true);
    $access_token = isset($json['responseBody']['accessToken']) ? $json['responseBody']['accessToken'] : null;

    if ($access_token) {
        // 2. Create reserved account
        $payload = [
            "accountReference" => "user_" . $user_id,
            "accountName" => $full_name,
            "currencyCode" => "NGN",
            "contractCode" => $monnify_contract_code,
            "customerEmail" => "", // Optionally fetch user email
            "customerName" => $full_name,
            "getAllAvailableBanks" => true
        ];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.monnify.com/api/v2/bank-transfer/reserved-accounts",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $access_token",
                "Content-Type: application/json"
            ]
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if (!$err && $response) {
            $data = json_decode($response, true);
            if (
                isset($data['responseBody']['accounts'][0]['accountNumber']) &&
                isset($data['responseBody']['accounts'][0]['bankName'])
            ) {
                $account_number = $data['responseBody']['accounts'][0]['accountNumber'];
                $bank_name = $data['responseBody']['accounts'][0]['bankName'];
                // Save to DB for future use
                $save_stmt = $conn->prepare("INSERT INTO virtual_accounts (user_id, account_number, bank_name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE account_number=?, bank_name=?");
                $save_stmt->bind_param("issss", $user_id, $account_number, $bank_name, $account_number, $bank_name);
                $save_stmt->execute();
                $save_stmt->close();
            }
        }
    }
}

// --- Monnify Reserved Account: Core Feature (Auto-generate unique account, webhook-ready) ---
// (Code for fetching/creating reserved account is already included above)

// --- Fund Wallet via Card/USSD (Monnify Hosted Payment Page) ---
$monnify_payment_link = '';
if (!empty($monnify_contract_code)) {
    $monnify_payment_link = "https://sandbox.monnify.com/checkout/" .
        "?amount=1000" . // Default, can be dynamic via JS
        "&currency=NGN" .
        "&reference=" . urlencode("userfund_" . $user_id . "_" . time()) .
        "&customerName=" . urlencode($full_name) .
        "&customerEmail=" . urlencode('') . // Optionally fetch user email
        "&paymentDescription=" . urlencode("Wallet Funding") .
        "&contractCode=" . urlencode($monnify_contract_code);
}

// Fetch transactions
$transactions = [];
$txn_stmt = $conn->prepare("SELECT created_at, description, amount, status, reference FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$txn_stmt->bind_param("i", $user_id);
$txn_stmt->execute();
$txn_stmt->bind_result($created_at, $description, $amount, $status, $reference);
while ($txn_stmt->fetch()) {
    $transactions[] = [
        'created_at' => $created_at,
        'description' => $description,
        'amount' => $amount,
        'status' => $status,
        'reference' => $reference
    ];
}
$txn_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Wallet | Naija eHub</title>
  <link rel="stylesheet" href="css/wallet.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-container">
   <aside class="sidebar">
      <div class="logo">
      <img src="assets/img/logo-icon.png" alt="Logo">
      <h2>Naija eHub</h2>
      </div>
      
      <nav class="nav-menu">
      <a href="dashboard.php" class="active">
        <i class="fas fa-home"></i> Dashboard
      </a>
      <a href="wallet.php">
        <i class="fas fa-wallet"></i> My Wallet
      </a>
      <a href="transactions.php">
        <i class="fas fa-history"></i> Transactions
      </a>
      <a href="profile.php">
        <i class="fas fa-user-cog"></i> Profile
      </a>
      <a href="logout.php" id="logoutBtn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
      </nav>
      
      <div class="sidebar-footer">
      <button id="darkModeToggle" type="button">
        <i class="fas fa-moon"></i> Dark Mode
      </button>
      </div>
    </aside>
    
    <main class="main-content">
        <header class="main-header">
        <div class="header-left">
          <h3>Welcome back, <span id="userName"><?php echo htmlspecialchars($full_name); ?></span></h3>
          <p id="currentDate"></p>
        </div>
        
        <div class="header-right">
          <div class="notification-bell">
            <i class="fas fa-bell"></i>
            <?php
              // Fetch unread notifications count for the user
              $conn = getDbConnection();
              $notif_stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
              $notif_stmt->bind_param("i", $user_id);
              $notif_stmt->execute();
              $notif_stmt->bind_result($unread_count);
              $notif_stmt->fetch();
              $notif_stmt->close();
              $conn->close();
              if ($unread_count > 0) {
            echo '<span class="badge">' . $unread_count . '</span>';
              }
            ?>
          </div>
          <div id="notificationPopup" class="notification-popup" style="display:none;">
            <div class="popup-header">
              <strong>Notifications</strong>
              <button id="closePopup" style="float:right;">&times;</button>
            </div>
            <ul id="notificationList">
              <!-- Notifications will be loaded here -->
            </ul>
          </div>
          <div class="user-avatar">
            <button onclick="window.location.href='profile.php'" style="border: none; background: transparent; cursor: pointer;">
              <img src="assets/img/avatar-placeholder.jpeg" alt="User">
            </button>
          </div>
        </div>
      </header>
      <!-- Wallet Summary Section -->
      <section class="wallet-summary">
        <div class="balance-card">
          <div class="balance-info">
            <h4>Available Balance</h4>
            <h2 id="walletBalance">₦<?php echo number_format($wallet_balance ?? 0, 2); ?></h2>
          </div>
          <button class="btn btn-primary" id="fundWalletBtn">
            <i class="fas fa-plus"></i> Fund Wallet
          </button>
        </div>
        
        <div class="account-details">
          <h4>Virtual Account Details</h4>
          <div class="detail-item">
            <span>Account Number:</span>
            <strong id="virtualAccount"><?php echo htmlspecialchars($account_number); ?></strong>
            <button class="copy-btn" data-target="virtualAccount">
              <i class="fas fa-copy"></i>
            </button>
          </div>
          <div class="detail-item">
            <span>Bank Name:</span>
            <strong><?php echo htmlspecialchars($bank_name); ?></strong>
          </div>
          <div class="detail-item">
            <span>Account Name:</span>
            <strong id="accountName"><?php echo htmlspecialchars($account_name); ?></strong>
          </div>
        </div>
      </section>
      
      <!-- Transaction History Section -->
      <section class="transaction-section">
        <div class="section-header">
          <h3>Transaction History</h3>
          <div class="filter-controls">
        <select id="filterType">
          <option value="all">All Transactions</option>
          <option value="credit">Credits</option>
          <option value="debit">Debits</option>
          <option value="service">Service Payments</option>
        </select>
        <input type="date" id="filterDate">
        <button class="btn btn-outline" id="resetFilters">Reset</button>
          </div>
        </div>
        
        <div class="transaction-table">
          <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr>
            <th>Date/Time</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="transactionList">
          <?php
          // Pagination logic
          $perPage = 4;
          $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
          if ($page < 1) $page = 1;
          $offset = ($page - 1) * $perPage;

          // Get total transaction count
          $conn = getDbConnection();
          $count_stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
          $count_stmt->bind_param("i", $user_id);
          $count_stmt->execute();
          $count_stmt->bind_result($totalTxns);
          $count_stmt->fetch();
          $count_stmt->close();

          // Fetch paginated transactions
          $txn_stmt = $conn->prepare("SELECT created_at, description, amount, status, reference FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
          $txn_stmt->bind_param("iii", $user_id, $perPage, $offset);
          $txn_stmt->execute();
          $txn_stmt->bind_result($created_at, $description, $amount, $status, $reference);

          $txnCount = 0;
          while ($txn_stmt->fetch()):
          ?>
            <tr class="table-row">
          <td><?php echo htmlspecialchars($created_at); ?></td>
          <td><?php echo htmlspecialchars($description); ?></td>
          <td>₦<?php echo number_format($amount, 2); ?></td>
          <td>
            <?php
              if ($status === 'completed' || $status === 'success') echo '<span style="color:#067c3c;font-weight:bold;">Success</span>';
              elseif ($status === 'pending') echo '<span style="color:#ff9800;font-weight:bold;">Pending</span>';
              else echo '<span style="color:#d32f2f;font-weight:bold;">Failed</span>';
            ?>
          </td>
          <td>
            <button class="btn btn-outline view-receipt" data-reference="<?php echo htmlspecialchars($reference); ?>">Receipt</button>
          </td>
            </tr>
          <?php
            $txnCount++;
          endwhile;
          $txn_stmt->close();
          $conn->close();
          if ($txnCount === 0): ?>
            <tr>
          <td colspan="5" style="text-align:center;color:#888;">No transactions found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
          </table>
        </div>
        
        <div class="pagination">
          <?php
        $totalPages = ceil($totalTxns / $perPage);
        $prevDisabled = ($page <= 1) ? 'disabled' : '';
        $nextDisabled = ($page >= $totalPages) ? 'disabled' : '';
          ?>
          <form method="get" style="display:inline;">
        <button class="btn btn-outline" id="prevPage" name="page" value="<?php echo $page - 1; ?>" <?php echo $prevDisabled; ?>>
          <i class="fas fa-chevron-left"></i> Previous
        </button>
          </form>
          <span id="pageInfo">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
          <form method="get" style="display:inline;">
        <button class="btn btn-outline" id="nextPage" name="page" value="<?php echo $page + 1; ?>" <?php echo $nextDisabled; ?>>
          Next <i class="fas fa-chevron-right"></i>
        </button>
          </form>
        </div>
      </section>
    </main>
  </div>
  
  <!-- Fund Wallet Modal -->
  <div id="modalContainer"></div>
  
  <!-- Receipt Modal -->
  <div id="receiptModal" class="modal-overlay" style="display: none;">
    <div class="modal receipt">
      <div class="modal-header">
        <h3>Transaction Receipt</h3>
        <button class="close-modal">&times;</button>
      </div>
      <div class="modal-body" id="receiptContent">
        <!-- Receipt content will be inserted here -->
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" id="printReceipt">
          <i class="fas fa-print"></i> Print
        </button>
        <button class="btn btn-primary" id="downloadReceipt">
          <i class="fas fa-download"></i> Download PDF
        </button>
      </div>
    </div>
  </div>
  <script>
    // Download Receipt as PDF (using jsPDF)
    document.getElementById('downloadReceipt').addEventListener('click', function() {
      var doc = new window.jspdf.jsPDF();
      var content = document.getElementById('receiptContent').innerText;
      var lines = content.split('\n');
      var y = 20;
      doc.setFontSize(14);
      doc.text("Transaction Receipt", 15, y);
      y += 10;
      doc.setFontSize(11);
      lines.forEach(function(line) {
        doc.text(line, 15, y);
        y += 8;
      });
      doc.save('receipt.pdf');
    });
  </script>
  <!-- Add Fund Wallet Modal (Card/USSD/Manual) -->
  <div id="fundWalletModal" class="modal-overlay" style="display:none;">
    <div class="modal user-alert-modal small-modal fund-modal-center">
      <div class="modal-header" style="position:relative;">
        <h3>Fund Wallet</h3>
        <button class="close-modal" style="position:absolute;top:10px;right:14px;background:none;border:none;font-size:1.5rem;color:#d32f2f;cursor:pointer;">&times;</button>
      </div>
      <div class="modal-body">
        <label for="fundMethod" style="font-weight:600;">Select Funding Method:</label>
        <select id="fundMethod" style="width:100%;margin-bottom:1rem;padding:0.5rem 0.7rem;border-radius:5px;">
          <option value="bank">Bank Transfer</option>
          <option value="card">Card/USSD</option>
          <option value="manual">Manual Funding</option>
        </select>
        <div id="fundMethodBank" class="fund-method-section" style="display:block;">
          <strong>Bank Transfer:</strong>
          <p>Transfer to your unique account number above. Funds reflect instantly after payment.</p>
        </div>
        <div id="fundMethodCard" class="fund-method-section" style="display:none;">
          <strong>Card/USSD:</strong>
          <form id="monnifyCardForm" target="_blank" action="<?php echo htmlspecialchars($monnify_payment_link); ?>" method="get">
            <label for="fundAmount">Amount (₦):</label>
            <input type="number" id="fundAmount" name="amount" min="100" step="100" value="1000" required style="width:100%;margin-bottom:0.7rem;">
            <button type="submit" class="btn btn-primary" style="width:100%;">Pay with Card/USSD</button>
          </form>
          <div style="margin-top:0.7rem;">
            <strong>USSD Shortcode:</strong>
            <p>Dial <span style="font-weight:bold;">*000*0000#</span> and follow the prompts (Monnify supported banks).</p>
          </div>
        </div>
        <div id="fundMethodManual" class="fund-method-section" style="display:none;">
          <strong>Manual Funding:</strong>
          <div style="margin:0.7rem 0 1rem 0;padding:0.7rem;background:#f5f5f5;border-radius:7px;">
            <div><strong>Business Account Number:</strong> <span style="font-family:monospace;">9034095385</span></div>
            <div><strong>Account Name:</strong> ABDULRAHMAN SHABA YAKUBU</div>
            <div><strong>Bank Name:</strong> OPAY</div>
            <div><strong>Reference Code:</strong> <span id="manualRefCode" style="font-family:monospace;color:#067c3c;">NAIJA<?php echo strtoupper(substr(md5($user_id . date('Ymd')), 0, 6)); ?></span></div>
            <div style="font-size:0.97rem;color:#888;margin-top:0.5rem;">
              <em>Please include the reference code above in your bank transfer narration for faster approval.</em>
            </div>
          </div>
          <form id="manualFundForm" method="post" enctype="multipart/form-data" style="margin-top:0.7rem;display:grid;grid-template-columns:1fr 1fr;gap:0.7rem;align-items:end;">
            <div style="display:flex;flex-direction:column;grid-column:1/2;">
              <label for="manualAmount">Amount (₦):</label>
              <input type="number" id="manualAmount" name="manualAmount" min="100" step="100" required style="width:100%;">
            </div>
            <div style="display:flex;flex-direction:column;grid-column:2/3;">
              <label for="manualBank">Your Bank Name:</label>
              <input type="text" id="manualBank" name="manualBank" required style="width:100%;">
            </div>
            <div style="display:flex;flex-direction:column;grid-column:1/2;">
              <label for="manualSender">Sender Account Name/Number:</label>
              <input type="text" id="manualSender" name="manualSender" required style="width:100%;">
            </div>
            <div style="display:flex;flex-direction:column;grid-column:2/3;">
              <label for="manualProof">Upload Payment Proof (screenshot or PDF):</label>
              <input type="file" id="manualProof" name="manualProof" accept="image/*,application/pdf" required style="width:100%;">
            </div>
            <button type="submit" class="btn btn-outline" style="width:100%;grid-column:1/3;">Submit Manual Funding</button>
            <div id="manualFundMsg" style="margin-top:0.7rem;font-size:0.97rem;grid-column:1/3;"></div>
          </form>
          <div style="margin-top:1.2rem;">
            <strong>Manual Funding Status:</strong>
            <div id="manualFundStatusList" style="margin-top:0.5rem;">
              <!-- Status badges will be loaded here -->
            </div>
          </div>
          <div style="margin-top:1.2rem;">
            <strong>USSD Payment Option:</strong>
            <div style="margin:0.5rem 0 0.7rem 0;padding:0.6rem;background:#f9f9f9;border-radius:7px;">
              <div>Dial <span style="font-family:monospace;color:#067c3c;">*901*9034095385*AMOUNT#</span> (GTBank example)</div>
              <div style="font-size:0.97rem;color:#888;margin-top:0.3rem;">
                <em>After payment, upload your proof above and include your reference code in narration if possible.</em>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Copy Account Number
    document.querySelectorAll('.copy-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const targetId = btn.getAttribute('data-target');
        const text = document.getElementById(targetId).textContent;
        navigator.clipboard.writeText(text);
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 1200);
      });
    });

    // Show Receipt Modal
    document.querySelectorAll('.view-receipt').forEach(btn => {
      btn.addEventListener('click', function() {
        const ref = btn.getAttribute('data-reference');
        // For demo, just show static content. Replace with AJAX for real data.
        const row = btn.closest('.table-row');
        const cells = row.querySelectorAll('div');
        document.getElementById('receiptContent').innerHTML = `
          <p><strong>Date/Time:</strong> ${cells[0].textContent}</p>
          <p><strong>Description:</strong> ${cells[1].textContent}</p>
          <p><strong>Amount:</strong> ${cells[2].textContent}</p>
          <p><strong>Status:</strong> ${cells[3].textContent}</p>
          <p><strong>Reference:</strong> ${ref}</p>
        `;
        document.getElementById('receiptModal').style.display = 'block';
      });
    });

    // Close Receipt Modal
    document.querySelectorAll('.close-modal').forEach(btn => {
      btn.addEventListener('click', function() {
        document.getElementById('receiptModal').style.display = 'none';
      });
    });

    // Print Receipt
    document.getElementById('printReceipt').addEventListener('click', function() {
      const content = document.getElementById('receiptContent').innerHTML;
      const win = window.open('', '', 'width=600,height=400');
      win.document.write('<html><head><title>Print Receipt</title></head><body>' + content + '</body></html>');
      win.document.close();
      win.print();
    });

    // Download Receipt as PDF (using jsPDF)
    document.getElementById('downloadReceipt').addEventListener('click', function() {
      // Use jsPDF to generate PDF from receiptContent
      var doc = new window.jspdf.jsPDF();
      var content = document.getElementById('receiptContent').innerText;
      var lines = content.split('\n');
      var y = 20;
      doc.setFontSize(14);
      doc.text("Transaction Receipt", 15, y);
      y += 10;
      doc.setFontSize(11);
      lines.forEach(function(line) {
        doc.text(line, 15, y);
        y += 8;
      });
      doc.save('receipt.pdf');
    });

    // Filter functionality (client-side, for demo)
    document.getElementById('filterType').addEventListener('change', function() {
      filterTransactions();
    });
    document.getElementById('filterDate').addEventListener('change', function() {
      filterTransactions();
    });
    document.getElementById('resetFilters').addEventListener('click', function() {
      document.getElementById('filterType').value = 'all';
      document.getElementById('filterDate').value = '';
      filterTransactions();
    });

    function filterTransactions() {
      const type = document.getElementById('filterType').value;
      const date = document.getElementById('filterDate').value;
      document.querySelectorAll('#transactionList .table-row').forEach(row => {
        let show = true;
        if (type !== 'all') {
          const txnType = row.children[2].textContent.includes('-') ? 'debit' : 'credit';
          if (type !== txnType && !(type === 'service' && row.children[1].textContent.toLowerCase().includes('service'))) {
            show = false;
          }
        }
        if (date) {
          if (!row.children[0].textContent.startsWith(date)) show = false;
        }
        row.style.display = show ? '' : 'none';
      });
    }

    // Show Fund Wallet Modal (centered)
    document.getElementById('fundWalletBtn').addEventListener('click', function() {
      var modal = document.getElementById('fundWalletModal');
      modal.style.display = 'flex';
      modal.classList.add('active');
    });
    // Close Modal
    document.querySelectorAll('.close-modal').forEach(btn => {
      btn.addEventListener('click', function() {
        var modal = document.getElementById('fundWalletModal');
        modal.style.display = 'none';
        modal.classList.remove('active');
      });
    });
    // Optional: Close modal on outside click
    document.getElementById('fundWalletModal').addEventListener('click', function(e) {
      if (e.target === this) {
        this.style.display = 'none';
        this.classList.remove('active');
      }
    });
    // Dynamic Monnify Hosted Payment Link (Card/USSD)
    document.getElementById('monnifyCardForm').addEventListener('submit', function(e) {
      var amount = document.getElementById('fundAmount').value;
      var base = "<?php echo 'https://sandbox.monnify.com/checkout/'; ?>";
      var params = [
        "amount=" + encodeURIComponent(amount),
        "currency=NGN",
        "reference=" + encodeURIComponent("userfund_<?php echo $user_id; ?>_" + Date.now()),
        "customerName=" + encodeURIComponent("<?php echo $full_name; ?>"),
        "customerEmail=",
        "paymentDescription=" + encodeURIComponent("Wallet Funding"),
        "contractCode=" + encodeURIComponent("<?php echo $monnify_contract_code; ?>")
      ].join("&");
      this.action = base + "?" + params;
      // Allow normal submit (target="_blank")
    });

    // Fund method select logic
    document.getElementById('fundMethod').addEventListener('change', function() {
      var val = this.value;
      document.getElementById('fundMethodBank').style.display = (val === 'bank') ? 'block' : 'none';
      document.getElementById('fundMethodCard').style.display = (val === 'card') ? 'block' : 'none';
      document.getElementById('fundMethodManual').style.display = (val === 'manual') ? 'block' : 'none';
    });

    // Manual Fund AJAX
    document.getElementById('manualFundForm').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var msg = document.getElementById('manualFundMsg');
      msg.textContent = '';
      var formData = new FormData(form);
      formData.append('action', 'manual_fund');
      fetch('ajax/manual_fund.php', {
        method: 'POST',
        body: formData
      })
      .then(res => {
        // Fix: check for valid JSON response, even if status is 200
        return res.text().then(text => {
          try {
            return JSON.parse(text);
          } catch (e) {
            throw new Error('Invalid server response');
          }
        });
      })
      .then(data => {
        if (data.success) {
          msg.style.color = '#067c3c';
          msg.textContent = 'Manual funding request submitted. Awaiting admin approval.';
          form.reset();
        } else {
          msg.style.color = '#d32f2f';
          msg.textContent = data.message || 'Failed to submit manual funding.';
        }
      })
      .catch((err) => {
        msg.style.color = '#d32f2f';
        msg.textContent = 'Error submitting manual funding. Please check your internet connection or try again later.';
      });
    });

    // Load manual funding status badges (AJAX)
    function loadManualFundStatus() {
      fetch('ajax/get_manual_fund_status.php')
        .then(res => res.json())
        .then(data => {
          var list = document.getElementById('manualFundStatusList');
          if (!list) return;
          list.innerHTML = '';
          if (data && data.length) {
            data.forEach(function(req) {
              let badge = '';
              if (req.status === 'pending') badge = '<span class="badge-status badge-pending">⏳ Pending</span>';
              else if (req.status === 'approved') badge = '<span class="badge-status badge-approved">✅ Approved</span>';
              else if (req.status === 'rejected') badge = '<span class="badge-status badge-rejected">❌ Rejected</span>';
              list.innerHTML += `<div>
                <span>${req.amount ? '₦'+parseFloat(req.amount).toLocaleString() : ''}</span>
                ${badge}
                <span style="font-size:0.93em;color:#888;">${req.created_at}</span>
              </div>`;
            });
          } else {
            list.innerHTML = '<span style="color:#888;">No manual funding requests yet.</span>';
          }
        });
    }
    // Call on modal open
    document.getElementById('fundWalletBtn').addEventListener('click', function() {
      loadManualFundStatus();
    });

    // Real-time wallet balance update (poll every 10s)
    setInterval(function() {
      fetch('ajax/get_wallet_balance.php')
        .then(res => res.json())
        .then data => {
          if (data.balance !== undefined) {
            document.getElementById('walletBalance').textContent = "₦" + parseFloat(data.balance).toLocaleString(undefined, {minimumFractionDigits:2});
          }
        });
    }, 10000);
  </script>
  <!-- jsPDF CDN for PDF generation -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="../js/wallet.js"></script>
<?php include __DIR__ . '../includes/spinner.php'; ?>
</body>
</html>
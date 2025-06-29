<?php
session_start();
require_once('config/db.php');

// Monnify API credentials
define('MONNIFY_API_KEY', 'MK_TEST_GKHF7ZMEGJ');
define('MONNIFY_SECRET_KEY', '4EGGA2D90L3D88QGU3D6QXZBQN0FXFKM');
define('MONNIFY_BASE_URL', 'https://sandbox.monnify.com');

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
  header('Location: auth/login.php');
  exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, phone, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $phone, $wallet_balance);
$stmt->fetch();
$stmt->close();

// Fetch Monnify virtual account details
$virtual_account_number = "Not assigned";
$virtual_account_bank = "Not assigned";
$va_stmt = $conn->prepare("SELECT account_number, bank_name FROM virtual_accounts WHERE user_id = ?");
$va_stmt->bind_param("i", $user_id);
$va_stmt->execute();
$va_stmt->bind_result($virtual_account_number, $virtual_account_bank);
if (!$va_stmt->fetch()) {
    // If not assigned, create a new virtual account via Monnify API
    $va_stmt->close();

    function generateMonnifyAccount($user_id, $full_name, $email) {
        $apiKey = MONNIFY_API_KEY;
        $secretKey = MONNIFY_SECRET_KEY;
        $baseUrl = MONNIFY_BASE_URL;
        $contractCode = "YOUR_CONTRACT_CODE"; // Replace with your Monnify contract code

        // Authenticate to get access token
        $auth = base64_encode($apiKey . ':' . $secretKey);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v1/auth/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic $auth",
            "Content-Type: application/json"
        ]);
        $response = curl_exec($ch);
        $auth_data = json_decode($response, true);
        curl_close($ch);

        if (!isset($auth_data['responseBody']['accessToken'])) {
            return false;
        }
        $accessToken = $auth_data['responseBody']['accessToken'];

        // Create reserved account
        $accountReference = 'VA-' . $user_id . '-' . time();
        $payload = [
            "accountReference" => $accountReference,
            "accountName" => $full_name,
            "currencyCode" => "NGN",
            "contractCode" => $contractCode,
            "customerEmail" => $email,
            "customerName" => $full_name,
            "customerBVN" => "",
            "getAllAvailableBanks" => true
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/v2/bank-transfer/reserved-accounts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        $response = curl_exec($ch);
        $va_data = json_decode($response, true);
        curl_close($ch);

        if (isset($va_data['responseBody']['accounts'][0]['accountNumber'])) {
            return [
                'account_number' => $va_data['responseBody']['accounts'][0]['accountNumber'],
                'bank_name' => $va_data['responseBody']['accounts'][0]['bankName'],
                'reference' => $accountReference
            ];
        }
        return false;
    }

    $accountData = generateMonnifyAccount($user_id, $full_name, $email);
    if ($accountData) {
        $virtual_account_number = $accountData['account_number'];
        $virtual_account_bank = $accountData['bank_name'];
        $save_stmt = $conn->prepare("INSERT INTO virtual_accounts (user_id, account_number, bank_name, monnify_account_reference) VALUES (?, ?, ?, ?)");
        $save_stmt->bind_param("isss", $user_id, $virtual_account_number, $virtual_account_bank, $accountData['reference']);
        $save_stmt->execute();
        $save_stmt->close();
    }
} else {
    $va_stmt->close();
}

// Mock services data (replace with actual DB query if available)
$services = [
    ['title' => 'NIN Services', 'icon_class' => 'fas fa-id-card', 'description' => 'Verify or update your National Identification Number.', 'link' => 'services/nin.php', 'is_active' => true],
    ['title' => 'BVN Services', 'icon_class' => 'fas fa-fingerprint', 'description' => 'Retrieve or verify your Bank Verification Number.', 'link' => 'services/bvn.php', 'is_active' => true],
    ['title' => 'CAC Registration', 'icon_class' => 'fas fa-building', 'description' => 'Register your business with CAC.', 'link' => 'services/cac.php', 'is_active' => false],
    ['title' => 'JAMB Services', 'icon_class' => 'fas fa-graduation-cap', 'description' => 'Check results or update JAMB data.', 'link' => 'services/jamb.php', 'is_active' => true],
    ['title' => 'News Publishing', 'icon_class' => 'fas fa-newspaper', 'description' => 'Publish or manage news articles.', 'link' => 'services/news.php', 'is_active' => true]
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Naija eHub Dashboard - Manage your NIN, BVN, JAMB, CAC, and wallet services securely.">
  <title>Dashboard | Naija eHub</title>
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="css/main.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php' ?>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <button class="toggle-sidebar" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h2>Dashboard</h2>
        <div>
          <button class="btn" onclick="toggleAccessibilityPanel()">Accessibility</button>
        </div>
      </header>

      <!-- Accessibility Panel -->
      <div class="accessibility-panel" id="accessibility-panel">
        <h4>Accessibility Options</h4>
        <label>
          Font Size:
          <select onchange="adjustFontSize(this.value)">
            <option value="1">Normal</option>
            <option value="1.2">Large</option>
            <option value="1.4">Extra Large</option>
          </select>
        </label>
        <label>
          High Contrast:
          <input type="checkbox" onchange="toggleHighContrast(this.checked)">
        </label>
      </div>

      <!-- User Profile Summary -->
      <section class="wallet-card">
        <div class="wallet-info">
          <h4>Welcome, <?php echo htmlspecialchars($full_name); ?></h4>
          <p>Email: <?php echo htmlspecialchars($email); ?></p>
          <p>Phone: <?php echo htmlspecialchars($phone); ?></p>
          <h2 id="walletBalance">₦<?php echo number_format($wallet_balance ?? 0, 2); ?></h2>
          <p id="virtualAccount">
            Virtual Account: <?php echo htmlspecialchars($virtual_account_number); ?> (<?php echo htmlspecialchars($virtual_account_bank); ?>)
          </p>
        </div>
        <div>
          <button class="btn btn-primary" id="fundWalletBtn" onclick="openFundWalletModal()">Fund Wallet</button>
          <button class="btn btn-outline" onclick="window.location.href='profile.php'">Edit Profile</button>
        </div>
      </section>

      <!-- Quick Actions -->
      <section class="services-section">
        <h3>Quick Actions</h3>
        <div class="services-grid">
          <div class="service-card">
            <div class="service-icon"><i class="fas fa-id-card"></i></div>
            <h4>Verify NIN</h4>
            <p>Quickly verify your National Identification Number.</p>
            <a href="services/nin.php" class="btn">Verify Now</a>
          </div>
          <div class="service-card">
            <div class="service-icon"><i class="fas fa-fingerprint"></i></div>
            <h4>Check BVN</h4>
            <p>Retrieve or verify your Bank Verification Number.</p>
            <a href="bvn/bvn.php" class="btn">Check Now</a>
          </div>
          <div class="service-card">
            <div class="service-icon"><i class="fas fa-wallet"></i></div>
            <h4>Fund Wallet</h4>
            <p>Add funds to your Naija eHub wallet.</p>
            <button class="btn" onclick="openFundWalletModal()">Fund Now</button>
          </div>
        </div>
      </section>

      <!-- Services Section -->
      <section class="services-section">
        <h3>Available Services</h3>
        <div class="services-grid">
          <?php foreach ($services as $service): ?>
            <div class="service-card <?php echo !$service['is_active'] ? 'coming-soon' : ''; ?>" data-service="<?php echo strtolower($service['title']); ?>">
              <div class="service-status <?php echo $service['is_active'] ? '' : 'offline'; ?>" title="<?php echo $service['is_active'] ? 'Online' : 'Offline'; ?>"></div>
              <div class="service-icon">
                <i class="<?php echo $service['icon_class']; ?>"></i>
              </div>
              <h4><?php echo $service['title']; ?></h4>
              <p><?php echo $service['description']; ?></p>
              <a href="<?php echo $service['is_active'] ? $service['link'] : '#'; ?>" class="btn btn-outline" <?php echo !$service['is_active'] ? 'disabled' : ''; ?>>
                <?php echo $service['is_active'] ? 'Access Service' : 'Coming Soon'; ?>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Transaction History -->
      <section class="transactions-section">
        <h3>Transaction History</h3>
        <div class="transaction-filters">
          <select id="filterType" onchange="filterTransactions()">
            <option value="all">All Types</option>
            <option value="credit">Credit</option>
            <option value="debit">Debit</option>
          </select>
          <select id="filterStatus" onchange="filterTransactions()">
            <option value="all">All Status</option>
            <option value="completed">Success</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
          </select>
          <input type="date" id="filterDate" onchange="filterTransactions()">
          <button class="btn" onclick="exportTransactions()">Export as CSV</button>
        </div>
        <div style="overflow-x: auto;">
          <table class="transaction-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Reference</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody id="transactionTable">
              <?php
              $conn = getDbConnection();
              $txn_stmt = $conn->prepare("SELECT created_at, type, amount, status, reference, description FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
              $txn_stmt->bind_param("i", $user_id);
              $txn_stmt->execute();
              $txn_stmt->store_result();
              $txn_stmt->bind_result($date, $type, $amount, $status, $reference, $description);
              $hasTxn = false;
              while ($txn_stmt->fetch()):
                $hasTxn = true;
              ?>
              <tr data-type="<?php echo $type; ?>" data-status="<?php echo $status; ?>" data-date="<?php echo date('Y-m-d', strtotime($date)); ?>">
                <td><?php echo htmlspecialchars($date); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($type)); ?></td>
                <td>₦<?php echo number_format($amount, 2); ?></td>
                <td class="status-<?php echo $status; ?>">
                  <?php
                    if ($status === 'completed' || $status === 'success') echo 'Success';
                    elseif ($status === 'pending') echo 'Pending';
                    else echo 'Failed';
                  ?>
                </td>
                <td><?php echo htmlspecialchars($reference); ?></td>
                <td><?php echo htmlspecialchars($description); ?></td>
              </tr>
              <?php endwhile; $txn_stmt->close(); $conn->close(); ?>
              <?php if (!$hasTxn): ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 1rem; color: #666;">No transactions found.</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <!-- Fund Wallet Modal -->
  <div class="modal" id="fundWalletModal">
    <div class="modal-content">
      <span class="modal-close" onclick="closeFundWalletModal()">&times;</span>
      <h3>Fund Your Wallet</h3>
      <form id="fundWalletForm" onsubmit="fundWallet(event)">
        <input type="number" placeholder="Amount (NGN)" min="100" required>
        <input type="text" placeholder="Card Number" required>
        <input type="text" placeholder="Expiry Date (MM/YY)" required>
        <input type="text" placeholder="CVV" required>
        <button type="submit" class="btn">Fund Now</button>
      </form>
    </div>
  </div>

  <?php include __DIR__ . '../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '../includes/footer.php'; ?>
  <?php include __DIR__ . '../includes/spinner.php'; ?>

  <script>
    // Sidebar Toggle
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
    }

    // Accessibility
    function toggleAccessibilityPanel() {
      document.getElementById('accessibility-panel').classList.toggle('active');
    }

    function adjustFontSize(scale) {
      document.documentElement.style.setProperty('--font-size-base', `${scale}rem`);
      localStorage.setItem('font-size', scale);
    }

    function toggleHighContrast(isChecked) {
      document.body.setAttribute('data-contrast', isChecked ? 'high' : 'normal');
      localStorage.setItem('contrast', isChecked ? 'high' : 'normal');
    }

    // Load saved settings
    document.addEventListener('DOMContentLoaded', () => {
      const savedFontSize = localStorage.getItem('font-size') || '1';
      const savedContrast = localStorage.getItem('contrast') || 'normal';
      document.documentElement.style.setProperty('--font-size-base', `${savedFontSize}rem`);
      document.body.setAttribute('data-contrast', savedContrast);
      document.querySelector('input[type="checkbox"]').checked = savedContrast === 'high';
      updateWalletBalance();
    });

    // Fund Wallet Modal
    function openFundWalletModal() {
      document.getElementById('fundWalletModal').style.display = 'flex';
    }

    function closeFundWalletModal() {
      document.getElementById('fundWalletModal').style.display = 'none';
    }

    function fundWallet(event) {
      event.preventDefault();
      const amount = event.target.querySelector('input[type="number"]').value;
      alert(`Initiating payment of ₦${amount} via Monnify. (Mock payment)`);
      // Add Monnify payment API integration here
      closeFundWalletModal();
    }

    // Transaction Filtering
    function filterTransactions() {
      const typeFilter = document.getElementById('filterType').value;
      const statusFilter = document.getElementById('filterStatus').value;
      const dateFilter = document.getElementById('filterDate').value;
      const rows = document.querySelectorAll('#transactionTable tr');

      rows.forEach(row => {
        const type = row.getAttribute('data-type') || '';
        const status = row.getAttribute('data-status') || '';
        const date = row.getAttribute('data-date') || '';

        const typeMatch = typeFilter === 'all' || type === typeFilter;
        const statusMatch = statusFilter === 'all' || status === statusFilter;
        const dateMatch = !dateFilter || date === dateFilter;

        row.style.display = typeMatch && statusMatch && dateMatch ? '' : 'none';
      });
    }

    // Export Transactions as CSV
    function exportTransactions() {
      const rows = document.querySelectorAll('#transactionTable tr:not([style*="display: none"])');
      let csv = 'Date,Type,Amount,Status,Reference,Description\n';
      rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length) {
          const rowData = Array.from(cells).map(cell => `"${cell.textContent.trim().replace(/"/g, '""')}"`).join(',');
          csv += `${rowData}\n`;
        }
      });
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'transactions.csv';
      a.click();
      window.URL.revokeObjectURL(url);
    }

    // Update Wallet Balance (Mock AJAX)
    function updateWalletBalance() {
      // Replace with actual AJAX call to backend
      fetch('/api/wallet-balance.php?user_id=<?php echo $user_id; ?>')
        .then(response => response.json())
        .then(data => {
          document.getElementById('walletBalance').textContent = `₦${Number(data.balance).toFixed(2)}`;
        })
        .catch(error => console.error('Error updating wallet balance:', error));
    }

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', (e) => {
      e.preventDefault();
      window.location.href = "?logout=1";
    });
  </script>
</body>
</html>
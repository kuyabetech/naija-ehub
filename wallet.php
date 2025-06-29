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

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, email, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $wallet_balance);
$stmt->fetch();
$stmt->close();

// Fallback for empty full_name
$account_name = $full_name ?: "User {$user_id}";

// Fetch virtual account details or auto-generate
$account_number = "Not assigned";
$bank_name = "Naija eHub MFB";
$va_stmt = $conn->prepare("SELECT account_number, bank_name, monnify_account_reference FROM virtual_accounts WHERE user_id = ?");
$va_stmt->bind_param("i", $user_id);
$va_stmt->execute();
$va_stmt->bind_result($account_number, $bank_name, $monnify_account_reference);
$va_stmt->fetch();
$va_stmt->close();

if ($account_number === null || $account_number === "Not assigned" || $account_number === "") {
    $monnify_contract_code = '3380031445';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, MONNIFY_BASE_URL . '/api/v1/auth/login');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(MONNIFY_API_KEY . ':' . MONNIFY_SECRET_KEY)
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $json = json_decode($output, true);
        $access_token = $json['responseBody']['accessToken'] ?? null;
        if ($access_token) {
            $payload = [
                'accountReference' => "user_{$user_id}_" . time(),
                'accountName' => $account_name,
                'currencyCode' => 'NGN',
                'contractCode' => $monnify_contract_code,
                'customerEmail' => $email ?: "user{$user_id}@naijaehub.com",
                'customerName' => $account_name,
                'getAllAvailableBanks' => true
            ];
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => MONNIFY_BASE_URL . '/api/v2/bank-transfer/reserved-accounts',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer $access_token",
                    'Content-Type: application/json'
                ]
            ]);
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);

            if ($http_code === 200 && !$err) {
                $data = json_decode($response, true);
                if (isset($data['responseBody']['accounts'][0]['accountNumber']) && isset($data['responseBody']['accounts'][0]['bankName'])) {
                    $account_number = $data['responseBody']['accounts'][0]['accountNumber'];
                    $bank_name = $data['responseBody']['accounts'][0]['bankName'];
                    $monnify_account_reference = $data['responseBody']['accountReference'];
                    $save_stmt = $conn->prepare("INSERT INTO virtual_accounts (user_id, account_number, bank_name, monnify_account_reference) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE account_number = ?, bank_name = ?, monnify_account_reference = ?");
                    $save_stmt->bind_param("issssss", $user_id, $account_number, $bank_name, $monnify_account_reference, $account_number, $bank_name, $monnify_account_reference);
                    $save_stmt->execute();
                    $save_stmt->close();
                }
            }
        }
    }
}

// Monnify payment link for card/USSD
$monnify_payment_link = MONNIFY_BASE_URL . '/checkout/' .
    '?amount=1000' .
    '&currency=NGN' .
    '&reference=' . urlencode("userfund_{$user_id}_" . time()) .
    '&customerName=' . urlencode($account_name) .
    '&customerEmail=' . urlencode($email ?: "user{$user_id}@naijaehub.com") .
    '&paymentDescription=' . urlencode('Wallet Funding') .
    '&contractCode=' . urlencode($monnify_contract_code);

// Pagination logic
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// Get total transaction count
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_stmt->bind_result($totalTxns);
$count_stmt->fetch();
$count_stmt->close();

$totalPages = ceil($totalTxns / $perPage);

// Fetch paginated transactions
$transactions = [];
$txn_stmt = $conn->prepare("SELECT id, type, amount, description, status, reference, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$txn_stmt->bind_param("iii", $user_id, $perPage, $offset);
$txn_stmt->execute();
$result = $txn_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$txn_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Naija eHub Wallet - Manage your virtual account and fund your wallet.">
  <title>My Wallet | Naija eHub</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <style>
    :root {
      --primary-color: #067c3c;
      --primary-hover: #045a2b;
      --secondary-color: #0e9e4a;
      --text-color: #1a1a1a;
      --bg-light: #f8f9fa;
      --bg-white: #ffffff;
      --text-light: #e6f4ea;
      --font-size-base: 1rem;
      --shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    [data-theme="dark"] {
      --primary-color: #0e9e4a;
      --primary-hover: #0cc558;
      --text-color: #e0e0e0;
      --bg-light: #1c2526;
      --bg-white: #2d2d2d;
      --text-light: #b0b0b0;
    }
    [data-contrast="high"] {
      --text-color: #000000;
      --bg-light: #ffffff;
      --bg-white: #ffffff;
      --text-light: #000000;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg-light);
      color: var(--text-color);
      font-size: var(--font-size-base);
      line-height: 1.6;
      margin: 0;
    }
    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 250px;
      background: var(--bg-white);
      box-shadow: var(--shadow);
      padding: 2rem;
      position: fixed;
      height: 100%;
      overflow-y: auto;
      transition: transform 0.3s ease;
    }
    .sidebar.hidden {
      transform: translateX(-100%);
    }
    .sidebar h2 {
      font-size: 1.5rem;
      color: var(--primary-color);
      margin-bottom: 2rem;
    }
    .sidebar ul {
      list-style: none;
      padding: 0;
    }
    .sidebar li {
      margin-bottom: 1rem;
    }
    .sidebar a {
      color: var(--text-color);
      text-decoration: none;
      font-size: 1rem;
      display: flex;
      align-items: center;
      padding: 0.8rem;
      border-radius: 8px;
      transition: background 0.3s, color 0.3s;
    }
    .sidebar a:hover, .sidebar a.active {
      background: var(--primary-color);
      color: #fff;
    }
    .sidebar a i {
      margin-right: 0.5rem;
    }
    .main-content {
      flex: 1;
      padding: 2rem;
      margin-left: 250px;
      background: var(--bg-light);
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--bg-white);
      padding: 1rem 2rem;
      border-radius: 10px;
      box-shadow: var(--shadow);
      margin-bottom: 2rem;
    }
    .wallet-summary {
      background: var(--bg-white);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 2rem;
      margin-bottom: 2rem;
      animation: fadeIn 0.5s ease-in;
    }
    .balance-card {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--primary-color);
      color: #fff;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .balance-info h4 {
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }
    .balance-info h2 {
      font-size: 2rem;
    }
    .account-details {
      display: grid;
      gap: 1rem;
    }
    .detail-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .detail-item span {
      font-weight: 600;
    }
    .copy-btn {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--primary-color);
      font-size: 1rem;
    }
    .copy-btn:hover {
      color: var(--primary-hover);
    }
    .transaction-section {
      background: var(--bg-white);
      border-radius: 12px;
      box-shadow: var(--shadow);
      padding: 2rem;
    }
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    .section-header h3 {
      font-size: 1.8rem;
      color: var(--primary-color);
    }
    .filter-controls {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .filter-controls select, .filter-controls input {
      padding: 0.8rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 0.95rem;
      background: var(--bg-light);
      color: var(--text-color);
    }
    .btn {
      background: var(--primary-color);
      color: #fff;
      border: none;
      padding: 0.8rem 1.5rem;
      border-radius: 25px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s, transform 0.2s;
    }
    .btn:hover {
      background: var(--primary-hover);
      transform: scale(1.05);
    }
    .btn-outline {
      background: transparent;
      border: 1px solid var(--primary-color);
      color: var(--primary-color);
    }
    .btn-outline:hover {
      background: var(--primary-color);
      color: #fff;
    }
    .transaction-table {
      overflow-x: auto;
    }
    .transaction-table table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95rem;
    }
    .transaction-table th, .transaction-table td {
      padding: 1rem;
      border-bottom: 1px solid #eee;
      text-align: left;
    }
    .transaction-table th {
      background: var(--bg-light);
      font-weight: 600;
    }
    .transaction-table .status-success {
      color: #067c3c;
      font-weight: bold;
    }
    .transaction-table .status-pending {
      color: #ff9800;
      font-weight: bold;
    }
    .transaction-table .status-failed {
      color: #d32f2f;
      font-weight: bold;
    }
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 1rem;
      margin-top: 1.5rem;
    }
    .pagination a {
      text-decoration: none;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
      transition: background 0.3s;
    }
    .pagination a:hover {
      background: var(--primary-color);
      color: #fff;
    }
    .pagination a.disabled {
      color: #666;
      border-color: #ddd;
      pointer-events: none;
    }
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    .modal {
      background: var(--bg-white);
      border-radius: 12px;
      padding: 2rem;
      max-width: 500px;
      width: 90%;
      box-shadow: var(--shadow);
      position: relative;
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }
    .modal-header h3 {
      font-size: 1.5rem;
      color: var(--primary-color);
    }
    .close-modal {
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
    }
    .modal-body {
      font-size: 0.95rem;
    }
    .modal-footer {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 1rem;
    }
    .fund-method-section {
      margin-bottom: 1rem;
    }
    .badge-status {
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.9rem;
    }
    .badge-pending {
      background: #ff9800;
      color: #fff;
    }
    .badge-approved {
      background: #067c3c;
      color: #fff;
    }
    .badge-rejected {
      background: #d32f2f;
      color: #fff;
    }
    .toggle-sidebar {
      display: none;
      background: var(--primary-color);
      color: #fff;
      border: none;
      padding: 0.8rem;
      border-radius: 8px;
      cursor: pointer;
    }
    @media (max-width: 900px) {
      .sidebar {
        transform: translateX(-100%);
        z-index: 1000;
      }
      .sidebar.active {
        transform: translateX(0);
      }
      .main-content {
        margin-left: 0;
      }
      .toggle-sidebar {
        display: block;
      }
      .balance-card {
        flex-direction: column;
        gap: 1rem;
      }
      .filter-controls {
        flex-direction: column;
      }
    }
    @media (max-width: 600px) {
      .transaction-table th, .transaction-table td {
        padding: 0.5rem;
        font-size: 0.85rem;
      }
      .modal {
        width: 95%;
      }
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <h2>Naija eHub</h2>
      <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="services/nin.php"><i class="fas fa-id-card"></i> NIN Services</a></li>
        <li><a href="services/bvn.php"><i class="fas fa-fingerprint"></i> BVN Services</a></li>
        <li><a href="services/cac.php"><i class="fas fa-building"></i> CAC Registration</a></li>
        <li><a href="services/jamb.php"><i class="fas fa-graduation-cap"></i> JAMB Services</a></li>
        <li><a href="services/news.php"><i class="fas fa-newspaper"></i> News Publishing</a></li>
        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="wallet.php" class="active"><i class="fas fa-wallet"></i> Wallet</a></li>
        <li><a href="?logout=1" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <button class="toggle-sidebar" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h2>My Wallet</h2>
        <div>
          <button class="btn" onclick="toggleAccessibilityPanel()">Accessibility</button>
        </div>
      </header>

      <!-- Wallet Summary Section -->
      <section class="wallet-summary">
        <div class="balance-card">
          <div class="balance-info">
            <h4>Available Balance</h4>
            <h2 id="walletBalance">₦<?php echo number_format($wallet_balance ?? 0, 2); ?></h2>
          </div>
          <button class="btn btn-primary" id="fundWalletBtn" aria-label="Fund Wallet">
            <i class="fas fa-plus"></i> Fund Wallet
          </button>
        </div>
        <div class="account-details">
          <h4>Virtual Account Details</h4>
          <div class="detail-item">
            <span>Account Number:</span>
            <strong id="virtualAccount"><?php echo htmlspecialchars($account_number); ?></strong>
            <button class="copy-btn" data-target="virtualAccount" aria-label="Copy Account Number">
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
            <select id="filterType" onchange="filterTransactions()" aria-label="Filter by Transaction Type">
              <option value="all">All Transactions</option>
              <option value="credit">Credits</option>
              <option value="debit">Debits</option>
            </select>
            <select id="filterStatus" onchange="filterTransactions()" aria-label="Filter by Status">
              <option value="all">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="completed">Completed</option>
              <option value="success">Success</option>
              <option value="failed">Failed</option>
            </select>
            <input type="date" id="filterDateStart" onchange="filterTransactions()" aria-label="Start Date">
            <input type="date" id="filterDateEnd" onchange="filterTransactions()" aria-label="End Date">
            <button class="btn btn-outline" id="resetFilters" onclick="resetFilters()" aria-label="Reset Filters">Reset</button>
            <button class="btn btn-primary" onclick="exportTransactions()" aria-label="Export Transactions">Export as CSV</button>
          </div>
        </div>

        <div class="transaction-table">
          <table id="userTransactionsTable" style="width: 100%;">
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
              <?php if (empty($transactions)): ?>
                <tr>
                  <td colspan="5" style="text-align: center; color: #666;">No transactions found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($transactions as $txn): ?>
                  <tr data-type="<?php echo htmlspecialchars($txn['type']); ?>" data-status="<?php echo htmlspecialchars($txn['status']); ?>" data-date="<?php echo date('Y-m-d', strtotime($txn['created_at'])); ?>">
                    <td><?php echo htmlspecialchars($txn['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($txn['description']); ?></td>
                    <td>₦<?php echo number_format($txn['amount'], 2); ?></td>
                    <td class="status-<?php echo htmlspecialchars($txn['status']); ?>">
                      <?php
                        if ($txn['status'] === 'completed' || $txn['status'] === 'success') echo 'Success';
                        elseif ($txn['status'] === 'pending') echo 'Pending';
                        else echo 'Failed';
                      ?>
                    </td>
                    <td>
                      <button class="btn btn-outline view-receipt" data-reference="<?php echo htmlspecialchars($txn['reference']); ?>" data-details='<?php echo json_encode($txn); ?>' aria-label="View Receipt">Receipt</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="pagination">
          <a href="?page=<?php echo $page - 1; ?>" class="btn btn-outline <?php echo $page <= 1 ? 'disabled' : ''; ?>" aria-label="Previous Page">
            <i class="fas fa-chevron-left"></i> Previous
          </a>
          <span id="pageInfo">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
          <a href="?page=<?php echo $page + 1; ?>" class="btn btn-outline <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" aria-label="Next Page">
            Next <i class="fas fa-chevron-right"></i>
          </a>
        </div>
      </section>
    </main>
  </div>

  <!-- Fund Wallet Modal -->
  <div id="fundWalletModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Fund Wallet</h3>
        <button class="close-modal" onclick="closeFundModal()" aria-label="Close Fund Wallet Modal">×</button>
      </div>
      <div class="modal-body">
        <label for="fundMethod">Select Funding Method:</label>
        <select id="fundMethod" aria-label="Select Funding Method">
          <option value="bank">Bank Transfer</option>
          <option value="card">Card/USSD</option>
          <option value="manual">Manual Funding</option>
        </select>
        <div id="fundMethodBank" class="fund-method-section">
          <strong>Bank Transfer:</strong>
          <p>Transfer to your unique account number above. Funds reflect instantly after payment.</p>
        </div>
        <div id="fundMethodCard" class="fund-method-section" style="display: none;">
          <strong>Card/USSD:</strong>
          <form id="monnifyCardForm" target="_blank" action="<?php echo htmlspecialchars($monnify_payment_link); ?>" method="get">
            <label for="fundAmount">Amount (₦):</label>
            <input type="number" id="fundAmount" name="amount" min="100" step="100" value="1000" required aria-required="true">
            <button type="submit" class="btn btn-primary">Pay with Card/USSD</button>
          </form>
          <div>
            <strong>USSD Shortcode:</strong>
            <p>Dial <span style="font-weight: bold;">*000*0000#</span> and follow the prompts (Monnify supported banks).</p>
          </div>
        </div>
        <div id="fundMethodManual" class="fund-method-section" style="display: none;">
          <strong>Manual Funding:</strong>
          <div class="funding-details">
            <div><strong>Business Account Number:</strong> <span>9034095385</span></div>
            <div><strong>Account Name:</strong> ABDULRAHMAN SHABA YAKUBU</div>
            <div><strong>Bank Name:</strong> OPAY</div>
            <div><strong>Reference Code:</strong> <span id="manualRefCode"><?php echo 'NAIJA' . strtoupper(substr(md5($user_id . date('Ymd')), 0, 6)); ?></span></div>
            <div class="funding-note">
              <em>Please include the reference code above in your bank transfer narration for faster approval.</em>
            </div>
          </div>
          <form id="manualFundForm" method="post" enctype="multipart/form-data">
            <div class="form-row">
              <div class="form-group">
                <label for="manualAmount">Amount (₦):</label>
                <input type="number" id="manualAmount" name="manualAmount" min="100" step="100" required aria-required="true">
              </div>
              <div class="form-group">
                <label for="manualBank">Your Bank Name:</label>
                <input type="text" id="manualBank" name="manualBank" required aria-required="true">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="manualSender">Sender Account Name/Number:</label>
                <input type="text" id="manualSender" name="manualSender" required aria-required="true">
              </div>
              <div class="form-group">
                <label for="manualProof">Upload Payment Proof (screenshot or PDF):</label>
                <input type="file" id="manualProof" name="manualProof" accept="image/*,application/pdf" required aria-required="true">
              </div>
            </div>
            <button type="submit" class="btn btn-outline">Submit Manual Funding</button>
            <div id="manualFundMsg"></div>
          </form>
          <div class="funding-status">
            <strong>Manual Funding Status:</strong>
            <div id="manualFundStatusList"></div>
          </div>
          <div>
            <strong>USSD Payment Option:</strong>
            <div class="funding-details">
              <div>Dial <span>*901*9034095385*AMOUNT#</span> (GTBank example)</div>
              <div class="funding-note">
                <em>After payment, upload your proof above and include your reference code in narration if possible.</em>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Receipt Modal -->
  <div id="receiptModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Transaction Receipt</h3>
        <button class="close-modal" onclick="closeReceiptModal()" aria-label="Close Receipt Modal">×</button>
      </div>
      <div class="modal-body" id="receiptContent"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" id="printReceipt" aria-label="Print Receipt">
          <i class="fas fa-print"></i> Print
        </button>
        <button class="btn btn-primary" id="downloadReceipt" aria-label="Download Receipt as PDF">
          <i class="fas fa-download"></i> Download PDF
        </button>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '/includes/footer.php'; ?>
  <?php include __DIR__ . '/includes/spinner.php'; ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script>
    $(document).ready(function() {
      const table = $('#userTransactionsTable').DataTable({
        pageLength: <?php echo $perPage; ?>,
        ordering: true,
        order: [[0, 'desc']],
        searching: false,
        lengthChange: false,
        info: false,
        paging: false
      });

      // Sidebar Toggle
      function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
      }

      // Copy Account Number
      $('.copy-btn').on('click', function() {
        const targetId = $(this).data('target');
        const text = $(`#${targetId}`).text();
        navigator.clipboard.writeText(text);
        $(this).html('<i class="fas fa-check"></i>');
        setTimeout(() => $(this).html('<i class="fas fa-copy"></i>'), 1200);
      });

      // Transaction Filtering
      function filterTransactions() {
        const type = $('#filterType').val();
        const status = $('#filterStatus').val();
        const dateStart = $('#filterDateStart').val();
        const dateEnd = $('#filterDateEnd').val();

        table.rows().every(function() {
          const row = this.node();
          const rowData = $(row).data();
          const rowDate = rowData.date;
          const typeMatch = type === 'all' || rowData.type === type;
          const statusMatch = status === 'all' || rowData.status === status;
          const dateMatch = (!dateStart || rowDate >= dateStart) && (!dateEnd || rowDate <= dateEnd);
          $(row).toggle(typeMatch && statusMatch && dateMatch);
        });
      }

      // Reset Filters
      function resetFilters() {
        $('#filterType').val('all');
        $('#filterStatus').val('all');
        $('#filterDateStart').val('');
        $('#filterDateEnd').val('');
        table.rows().every(function() {
          $(this.node()).show();
        });
      }

      // Export Transactions
      function exportTransactions() {
        const rows = $('#transactionList tr:visible');
        let csv = 'Date,Description,Amount,Status,Reference\n';
        rows.each(function() {
          const cells = $(this).find('td');
          if (cells.length) {
            const rowData = [
              $(cells[0]).text(),
              `"${$(cells[1]).text().replace(/"/g, '""')}"`,
              $(cells[2]).text(),
              $(cells[3]).text(),
              $(cells[4]).find('button').data('reference')
            ].join(',');
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

      // Receipt Modal
      $('.view-receipt').on('click', function() {
        const details = $(this).data('details');
        $('#receiptContent').html(`
          <p><strong>Reference:</strong> ${details.reference}</p>
          <p><strong>Date:</strong> ${details.created_at}</p>
          <p><strong>Description:</strong> ${details.description}</p>
          <p><strong>Amount:</strong> ₦${Number(details.amount).toFixed(2)}</p>
          <p><strong>Status:</strong> ${details.status.charAt(0).toUpperCase() + details.status.slice(1)}</p>
        `);
        $('#receiptModal').show();
      });

      function closeReceiptModal() {
        $('#receiptModal').hide();
      }

      // Fund Wallet Modal
      $('#fundWalletBtn').on('click', function() {
        $('#fundWalletModal').show();
        loadManualFundStatus();
      });

      function closeFundModal() {
        $('#fundWalletModal').hide();
      }

      // Fund Method Selection
      $('#fundMethod').on('change', function() {
        $('.fund-method-section').hide();
        $(`#fundMethod${this.value.charAt(0).toUpperCase() + this.value.slice(1)}`).show();
      });

      // Dynamic Monnify Payment Link
      $('#monnifyCardForm').on('submit', function(e) {
        const amount = $('#fundAmount').val();
        const base = '<?php echo MONNIFY_BASE_URL . '/checkout/'; ?>';
        const params = [
          `amount=${encodeURIComponent(amount)}`,
          'currency=NGN',
          `reference=${encodeURIComponent('userfund_<?php echo $user_id; ?>_' + Date.now())}`,
          `customerName=${encodeURIComponent('<?php echo $account_name; ?>')}`,
          `customerEmail=${encodeURIComponent('<?php echo $email ?: "user{$user_id}@naijaehub.com"; ?>')}`,
          `paymentDescription=${encodeURIComponent('Wallet Funding')}`,
          `contractCode=${encodeURIComponent('<?php echo $monnify_contract_code; ?>')}`
        ].join('&');
        this.action = base + '?' + params;
      });

      // Manual Funding
      $('#manualFundForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'manual_fund');
        const msg = $('#manualFundMsg');
        msg.text('');
        fetch('ajax/manual_fund.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          msg.css('color', data.success ? '#067c3c' : '#d32f2f');
          msg.text(data.message);
          if (data.success) {
            this.reset();
            loadManualFundStatus();
          }
        })
        .catch(() => {
          msg.css('color', '#d32f2f');
          msg.text('Error submitting request. Please try again.');
        });
      });

      // Load Manual Funding Status
      function loadManualFundStatus() {
        fetch('ajax/get_manual_fund_status.php')
          .then(res => res.json())
          .then(data => {
            const list = $('#manualFundStatusList');
            list.empty();
            if (data && data.length) {
              data.forEach(req => {
                const badge = req.status === 'pending' ? '<span class="badge-status badge-pending">⏳ Pending</span>' :
                              req.status === 'approved' ? '<span class="badge-status badge-approved">✅ Approved</span>' :
                              '<span class="badge-status badge-rejected">❌ Rejected</span>';
                list.append(`
                  <div>
                    <span>₦${parseFloat(req.amount).toLocaleString()}</span>
                    ${badge}
                    <span style="font-size: 0.93em; color: #888;">${req.created_at}</span>
                  </div>
                `);
              });
            } else {
              list.html('<span style="color: #888;">No manual funding requests yet.</span>');
            }
          });
      }

      // Print Receipt
      $('#printReceipt').on('click', function() {
        const content = $('#receiptContent').html();
        const win = window.open('', '', 'width=600,height=400');
        win.document.write(`<html><head><title>Print Receipt</title></head><body>${content}</body></html>`);
        win.document.close();
        win.print();
      });

      // Download Receipt as PDF
      $('#downloadReceipt').on('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const content = $('#receiptContent').text().split('\n');
        let y = 20;
        doc.setFontSize(14);
        doc.text('Transaction Receipt', 15, y);
        y += 10;
        doc.setFontSize(11);
        content.forEach(line => {
          if (line.trim()) {
            doc.text(line, 15, y);
            y += 8;
          }
        });
        doc.save('receipt.pdf');
      });

      // Real-time Wallet Balance Update
      setInterval(() => {
        fetch('ajax/get_wallet_balance.php')
          .then(res => res.json())
          .then(data => {
            if (data.balance !== undefined) {
              $('#walletBalance').text(`₦${parseFloat(data.balance).toLocaleString(undefined, { minimumFractionDigits: 2 })}`);
            }
          });
      }, 10000);

      // Close Modals on Outside Click
      $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
          closeReceiptModal();
          closeFundModal();
        }
      });
    });
  </script>
</body>
</html>
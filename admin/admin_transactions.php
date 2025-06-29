<?php
require_once('../config/db.php');
// Start a secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Optionally, set secure session cookie parameters here
}


// --- FIX: Use admin_id session for admin authentication ---
if (!isset($_SESSION['admin_id'])) {
    header('Location: adminLogin.php');
    exit();
}

$conn = getDbConnection();

// Fetch transactions with pagination and filtering
$transactions = [];
$whereClauses = [];
$params = [];
$types = '';

// Build filter conditions
if (!empty($_GET['type'])) {
    $whereClauses[] = "t.type = ?";
    $params[] = $_GET['type'];
    $types .= 's';
}

if (!empty($_GET['status'])) {
    $whereClauses[] = "t.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

if (!empty($_GET['date'])) {
    $whereClauses[] = "DATE(t.created_at) = ?";
    $params[] = $_GET['date'];
    $types .= 's';
}

$where = $whereClauses ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM transactions t $where";
$countStmt = $conn->prepare($countSql);

if ($whereClauses) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalTransactions = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Pagination
$perPage = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalPages = ceil($totalTransactions / $perPage);
$offset = ($page - 1) * $perPage;

// Fetch transactions
$sql = "SELECT t.id, u.full_name, t.type, t.amount, t.description, t.created_at, t.status, t.reference
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.id
        $where
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

if ($whereClauses) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

$stmt->close();

// Fetch manual funding requests
$manualFundRequests = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'manual_fund_requests'");

if ($tableCheck && $tableCheck->num_rows > 0) {
    $manualSql = "SELECT m.id, u.full_name, m.amount, m.bank, m.sender, m.proof, m.status, m.created_at
                 FROM manual_fund_requests m
                 LEFT JOIN users u ON m.user_id = u.id
                 ORDER BY m.created_at DESC";
    
    $manualResult = $conn->query($manualSql);
    
    while ($row = $manualResult->fetch_assoc()) {
        $manualFundRequests[] = $row;
    }
}

$notifications = [];
$res = $conn->query("SELECT message, created_at, is_read FROM admin_notifications WHERE admin_id = {$_SESSION['admin_id']} ORDER BY created_at DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}

// Only close the connection here, after all queries are done
$conn->close();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transaction History | Naija eHub</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <style>
    /* ...existing code... */
    @media (max-width: 992px) {
      .sidebar {
        position: fixed;
        left: -260px;
        top: 0;
        width: 240px;
        height: 100vh;
        z-index: 200;
        background: #fff;
        transition: left 0.3s;
        box-shadow: 2px 0 12px rgba(0,0,0,0.08);
      }
      .sidebar.active {
        left: 0;
      }
      .main-content {
        margin-left: 0 !important;
      }
      .admin-tabs {
        flex-wrap: wrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      .admin-tabs button {
        flex: 0 0 auto;
        min-width: 120px;
      }
      /* Responsive tables: horizontal scroll */
      .table-responsive {
        overflow-x: auto;
        width: 100%;
      }
      table.display {
        min-width: 600px;
      }
      /* Only scroll tables in sections that have tables */
      #usersSection .table-responsive,
      #transactionsSection .table-responsive,
      #servicesSection .table-responsive,
      #reportsSection .table-responsive,
      #manualFundingSection .table-responsive,
      #apiKeysSection .table-responsive {
        overflow-x: auto !important;
        width: 100%;
        display: block;
      }
      #usersSection table.display,
      #transactionsSection table.display,
      #servicesSection table.display,
      #reportsSection table.display,
      #manualFundingSection table.display,
      #apiKeysSection table.display {
        min-width: 600px;
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
      }
    }
    @media (max-width: 600px) {
      .admin-tabs {
        gap: 0.5rem !important;
        font-size: 0.95rem;
      }
      .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.7rem !important;
      }
      .table-responsive {
        padding: 0.5rem !important;
      }
      .modal.user-alert-modal,
      .modal.user-alert-modal.small-modal {
        width: 98vw !important;
        min-width: unset !important;
        max-width: 99vw !important;
        left: 1vw !important;
        right: 1vw !important;
        padding: 0.5rem !important;
      }
      .modal-header h3 {
        font-size: 1.1rem !important;
      }
      /* Responsive tables: font and cell size */
      table.display th,
      table.display td {
        font-size: 0.92rem;
        padding: 0.45rem 0.3rem;
        white-space: nowrap;
      }
      table.display {
        min-width: 420px;
      }
      #usersSection .table-responsive,
      #transactionsSection .table-responsive,
      #servicesSection .table-responsive,
      #reportsSection .table-responsive,
      #manualFundingSection .table-responsive,
      #apiKeysSection .table-responsive {
        padding: 0.5rem !important;
        overflow-x: auto !important;
        width: 100%;
        display: block;
      }
      #usersSection table.display,
      #transactionsSection table.display,
      #servicesSection table.display,
      #reportsSection table.display,
      #manualFundingSection table.display,
      #apiKeysSection table.display {
        min-width: 420px;
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
      }
      #usersSection table.display th,
      #usersSection table.display td,
      #transactionsSection table.display th,
      #transactionsSection table.display td,
      #servicesSection table.display th,
      #servicesSection table.display td,
      #reportsSection table.display th,
      #reportsSection table.display td,
      #manualFundingSection table.display th,
      #manualFundingSection table.display td,
      #apiKeysSection table.display th,
      #apiKeysSection table.display td {
        font-size: 0.92rem;
        padding: 0.45rem 0.3rem;
        white-space: nowrap;
      }
    }
    .table-responsive {
      overflow-x: auto;
      width: 100%;
    }
    /* API Keys table: make sure it scrolls on mobile */
    #apiKeysTable {
      min-width: 420px;
    }
    /* Modal overlay: full screen on mobile */
    .modal-overlay {
      align-items: center;
      justify-content: center;
      display: none;
      position: fixed;
      z-index: 9999;
      left: 0; top: 0; width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.18);
    }
    .modal-overlay.active, .modal-overlay.show {
      display: flex !important;
    }
    /* Manual Fund Modal Custom Styles */
.manual-fund-modal {
  width: 200px !important;
  min-width: 200px !important;
  max-width: 220px !important;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 6px 24px rgba(0,0,0,0.18);
  padding: 1rem 0.7rem 0.7rem 0.7rem;
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.manual-fund-modal .modal-header {
  border-bottom: 1px solid #e1e1e1;
  padding-bottom: 0.5rem;
  margin-bottom: 0.7rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.manual-fund-modal .modal-header h3 {
  font-size: 1.05rem;
  margin: 0;
  color: #1a6b54;
}
.manual-fund-modal .close-modal {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #d32f2f;
  cursor: pointer;
  margin-left: 0.5rem;
}
.manual-fund-modal .modal-body {
  font-size: 0.97rem;
  color: #222;
  margin-bottom: 0.7rem;
}
.manual-fund-modal textarea {
  width: 100%;
  min-height: 40px;
  border-radius: 5px;
  border: 1px solid #e1e1e1;
  padding: 0.4rem 0.5rem;
  font-size: 0.97rem;
  margin-top: 0.3rem;
  margin-bottom: 0.5rem;
  resize: vertical;
}
.manual-fund-modal .modal-footer {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}
.manual-fund-modal .btn {
  font-size: 0.95rem;
  padding: 0.4rem 0.7rem;
}
@media (max-width: 400px) {
  .manual-fund-modal {
    width: 98vw !important;
    min-width: unset !important;
    max-width: 99vw !important;
    padding: 0.7rem 0.2rem 0.7rem 0.2rem;
  }
}
.manual-fund-modal-overlay {
  display: none;
  position: fixed !important;
  top: 0; left: 0; right: 0; bottom: 0;
  width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.18);
  z-index: 9999;
  align-items: center;
  justify-content: center;
}
.manual-fund-modal-overlay[style*="display: flex"], 
.manual-fund-modal-overlay.active, 
.manual-fund-modal-overlay.show {
  display: flex !important;
}
  </style>
</head>
<body>
  <div class="dashboard-container admin">
    <!-- Admin Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="../assets/img/logo-icon.png" alt="Logo">
        <h2>Naija eHub <span>Admin</span></h2>
        <button id="sidebarCloseBtn" style="display:none;position:absolute;top:18px;right:18px;background:none;border:none;font-size:1.5rem;color:#067c3c;z-index:102;cursor:pointer;">
          &times;
        </button>
      </div>
      
      <nav class="nav-menu">
        <a href="admin.php" class="active" data-section="overview">
          <i class="fas fa-tachometer-alt"></i> Overview
        </a>
        <a href="admin_users.php" data-section="users">
          <i class="fas fa-users"></i> User Management
        </a>
        <a href="admin_transactions.php" data-section="transactions">
          <i class="fas fa-exchange-alt"></i> Transactions
        </a>
        <a href="service_management.php" data-section="services">
          <i class="fas fa-cogs"></i> Service Management
        </a>
        <a href="admin_reports.php" data-section="reports">
          <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a href="api_key.php" data-section="apiKeys">
          <i class="fas fa-key"></i> API KEYS
        </a>
        <a href="admin_setting.php" data-section="settings">
          <i class="fas fa-cog"></i> System Settings
        </a>
      </nav>
      
      <div class="sidebar-footer">
        <button id="adminLogoutBtn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </button>
      </div>
    </aside>
    
    <main class="main-content">
          <header class="main-header" style="background:#fff;box-shadow:0 2px 12px rgba(0,0,0,0.06);padding:0.7rem 2.2vw 0.7rem 2vw;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;">
        <div class="header-left" style="display:flex;align-items:center;gap:1.2rem;">
          <button class="mobile-menu-btn" id="mobileMenuBtn" style="background:none;border:none;font-size:1.5rem;color:#067c3c;cursor:pointer;">
            <i class="fas fa-bars"></i>
          </button>
          <h3 id="adminSectionTitle" style="font-size:1.25rem;color:#1a6b54;font-weight:600;margin:0;">Admin Dashboard</h3>
        </div>
        <div class="header-right" style="display:flex;align-items:center;gap:1.5rem;">
          <!-- Notification Bell with dropdown -->
          <div class="notification-bell" style="position:relative;cursor:pointer;">
            <i class="fas fa-bell" style="font-size:1.45rem;color:#1a6b54;"></i>
            <?php
              $unreadCount = 0;
              foreach ($notifications as $n) {
                if (!$n['is_read']) $unreadCount++;
              }
            ?>
            <span class="badge" style="background:#d32f2f;color:#fff;font-size:0.85rem;position:absolute;top:-7px;right:-7px;min-width:20px;height:20px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:600;">
              <?php echo $unreadCount; ?>
            </span>
            <div id="notificationDropdown" style="display:none;position:absolute;right:0;top:36px;min-width:270px;max-width:340px;background:#fff;border-radius:10px;box-shadow:0 4px 18px rgba(0,0,0,0.13);z-index:1001;padding:0.7rem 0;">
              <?php if (count($notifications)): ?>
                <?php foreach ($notifications as $n): ?>
                  <div style="padding:0.7rem 1rem;border-bottom:1px solid #f0f0f0;<?php if (!$n['is_read']) echo 'background:#e0f2ec;'; ?>">
                    <div style="font-size:0.98rem;color:#222;line-height:1.4;"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div style="font-size:0.85rem;color:#888;margin-top:0.2rem;"><?php echo date('M d, Y H:i', strtotime($n['created_at'])); ?></div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div style="padding:1rem;text-align:center;color:#888;">No notifications</div>
              <?php endif; ?>
            </div>
          </div>
          <style>
            
          </style>
          <div class="admin-avatar" style="display:flex;align-items:center;gap:0.7rem;">
            <img src="assets/img/admin-avatar.png" alt="Admin" style="width:38px;height:38px;border-radius:50%;object-fit:cover;box-shadow:0 2px 8px rgba(6,124,60,0.09);">
            <span style="font-weight:600;color:#1a6b54;font-size:1.05rem;">Admin User</span>
          </div>
        </div>
      </header>
    <!-- Transaction Management Section -->
    <?php
    // Pagination for transactions
    $transactionPage = isset($_GET['transaction_page']) ? max(1, intval($_GET['transaction_page'])) : 1;
    $transactionPerPage = 20;
    $transactionTotal = count($transactions);
    $transactionTotalPages = max(1, ceil($transactionTotal / $transactionPerPage));
    $transactionStart = ($transactionPage - 1) * $transactionPerPage;
    $transactionsPage = array_slice($transactions, $transactionStart, $transactionPerPage);
    ?>
    <section class="admin-section" id="transactionsSection">
        <div class="section-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.2rem; margin-bottom: 1.5rem;">
            <h4 style="font-size: 1.2rem; color: #1a6b54; margin: 0;">Transactions</h4>
            <div class="section-actions" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <select id="transactionTypeFilter" style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #e1e1e1;">
                    <option value="">All Types</option>
                    <option value="credit">Credits</option>
                    <option value="debit">Debits</option>
                </select>
                <select id="transactionStatusFilter" style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #e1e1e1;">
                    <option value="">All Statuses</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
                <input type="date" id="transactionDateFilter" style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #e1e1e1;">
            </div>
        </div>
        <div class="table-responsive" style="background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);padding:1.2rem;overflow-x:auto;">
            <table id="transactionsTable" class="display" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:0.7rem;">ID</th>
                        <th style="padding:0.7rem;">User</th>
                        <th style="padding:0.7rem;">Type</th>
                        <th style="padding:0.7rem;">Amount</th>
                        <th style="padding:0.7rem;">Description</th>
                        <th style="padding:0.7rem;">Date</th>
                        <th style="padding:0.7rem;">Status</th>
                        <th style="padding:0.7rem;">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactionsPage as $t): ?>
                    <tr>
                        <td><?php echo $t['id']; ?></td>
                        <td><?php echo htmlspecialchars($t['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($t['type']); ?></td>
                        <td>₦<?php echo number_format($t['amount'],2); ?></td>
                        <td><?php echo htmlspecialchars($t['description']); ?></td>
                        <td><?php echo $t['created_at']; ?></td>
                        <td><?php echo htmlspecialchars($t['status']); ?></td>
                        <td><?php echo htmlspecialchars($t['reference']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Pagination controls -->
            <?php if ($transactionTotalPages > 1): ?>
                <nav style="margin-top:1.2rem;display:flex;justify-content:center;">
                    <ul class="pagination" style="display:flex;gap:0.3rem;list-style:none;padding:0;">
                        <?php if ($transactionPage > 1): ?>
                            <li>
                                <a href="?transaction_page=<?php echo $transactionPage-1; ?>" class="btn btn-outline btn-sm">&laquo; Prev</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $transactionTotalPages; $i++): ?>
                            <li>
                                <a href="?transaction_page=<?php echo $i; ?>" class="btn btn-sm<?php if ($i == $transactionPage) echo ' btn-primary'; else echo ' btn-outline'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($transactionPage < $transactionTotalPages): ?>
                            <li>
                                <a href="?transaction_page=<?php echo $transactionPage+1; ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>
    <?php
    // Pagination for manual funding requests
    $manualFundPage = isset($_GET['manual_fund_page']) ? max(1, intval($_GET['manual_fund_page'])) : 1;
    $manualFundPerPage = 10;
    $manualFundTotal = count($manualFundRequests);
    $manualFundTotalPages = max(1, ceil($manualFundTotal / $manualFundPerPage));
    $manualFundStart = ($manualFundPage - 1) * $manualFundPerPage;
    $manualFundRequestsPage = array_slice($manualFundRequests, $manualFundStart, $manualFundPerPage);
    ?>

    <!-- Add after the Settings Section or as a new section -->
    <section class="admin-section" id="manualFundingSection">
        <div class="section-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.2rem; margin-bottom: 1.5rem;">
            <h4 style="font-size: 1.2rem; color: #1a6b54; margin: 0;">Manual Wallet Funding Requests</h4>
            <div class="section-actions" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <button class="btn btn-outline" id="refreshManualFundBtn">Refresh</button>
            </div>
        </div>
        <div class="table-responsive" style="background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);padding:1.2rem;overflow-x:auto;">
            <table id="manualFundTable" class="display" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:0.7rem;">ID</th>
                        <th style="padding:0.7rem;">User</th>
                        <th style="padding:0.7rem;">Amount</th>
                        <th style="padding:0.7rem;">Bank</th>
                        <th style="padding:0.7rem;">Sender</th>
                        <th style="padding:0.7rem;">Proof</th>
                        <th style="padding:0.7rem;">Status</th>
                        <th style="padding:0.7rem;">Date</th>
                        <th style="padding:0.7rem;">Action</th>
                    </tr>
                </thead>
                <tbody id="manualFundTableBody">
                    <?php if (count($manualFundRequestsPage)): ?>
                        <?php foreach ($manualFundRequestsPage as $req): ?>
                            <tr>
                                <td><?php echo $req['id']; ?></td>
                                <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                                <td>₦<?php echo number_format($req['amount'],2); ?></td>
                                <td><?php echo htmlspecialchars($req['bank']); ?></td>
                                <td><?php echo htmlspecialchars($req['sender']); ?></td>
                                <td>
                                    <?php if ($req['proof']): ?>
                                        <a href="../<?php echo htmlspecialchars($req['proof']); ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        if ($req['status'] === 'pending') echo '<span style="color:#b8860b;">⏳ Pending</span>';
                                        elseif ($req['status'] === 'approved') echo '<span style="color:#067c3c;">✅ Approved</span>';
                                        elseif ($req['status'] === 'rejected') echo '<span style="color:#d32f2f;">❌ Rejected</span>';
                                        else echo htmlspecialchars($req['status']);
                                    ?>
                                </td>
                                <td><?php echo $req['created_at']; ?></td>
                                <td>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <button class="btn btn-success btn-sm approve-manual-fund" data-id="<?php echo $req['id']; ?>">Approve</button>
                                        <button class="btn btn-danger btn-sm reject-manual-fund" data-id="<?php echo $req['id']; ?>">Reject</button>
                                    <?php else: ?>
                                        <span style="color:#888;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center;color:#888;">No manual funding requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Pagination controls -->
            <?php if ($manualFundTotalPages > 1): ?>
                <nav style="margin-top:1.2rem;display:flex;justify-content:center;">
                    <ul class="pagination" style="display:flex;gap:0.3rem;list-style:none;padding:0;">
                        <?php if ($manualFundPage > 1): ?>
                            <li><a href="?manual_fund_page=<?php echo $manualFundPage-1; ?>" class="btn btn-outline btn-sm">&laquo; Prev</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $manualFundTotalPages; $i++): ?>
                            <li>
                                <a href="?manual_fund_page=<?php echo $i; ?>" class="btn btn-sm<?php if ($i == $manualFundPage) echo ' btn-primary'; else echo ' btn-outline'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($manualFundPage < $manualFundTotalPages): ?>
                            <li><a href="?manual_fund_page=<?php echo $manualFundPage+1; ?>" class="btn btn-outline btn-sm">Next &raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>

<!-- Manual Fund Approve/Reject Modal -->
<div id="manualFundModal" class="modal-overlay manual-fund-modal-overlay" style="display:none;">
  <div class="modal user-alert-modal small-modal manual-fund-modal">
    <div class="modal-header">
      <h3 style="font-size:1.05rem;">Manual Funding Request</h3>
      <button class="close-modal">&times;</button>
    </div>
    <div class="modal-body" id="manualFundModalContent">
      <!-- Loaded dynamically -->
    </div>
    <div class="modal-footer" id="manualFundModalFooter">
      <!-- Loaded dynamically -->
    </div>
  </div>
</div>
</main>
</div>

<!-- JavaScript Libraries -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/admin.js"></script>
<script>
  
      // Responsive sidebar toggle for mobile
      $('#mobileMenuBtn').on('click', function() {
        $('.sidebar').addClass('active');
        $('#sidebarCloseBtn').show();
      });
      $('#sidebarCloseBtn').on('click', function() {
        $('.sidebar').removeClass('active');
        $(this).hide();
      });

      // Hide close icon on desktop
      function handleSidebarCloseBtn() {
        if (window.innerWidth <= 992) {
          $('#sidebarCloseBtn').show();
        } else {
          $('#sidebarCloseBtn').hide();
        }
      }
      window.addEventListener('resize', handleSidebarCloseBtn);
      document.addEventListener('DOMContentLoaded', handleSidebarCloseBtn);

// Enhanced transaction filtering
$(document).ready(function() {
    // Initialize DataTable for transactions
    $('#transactionsTable').DataTable({
        dom: '<"top"lf>rt<"bottom"ip>',
        pageLength: 20,
        responsive: true
    });

    // Apply filters
    function applyTransactionFilters() {
        const type = $('#transactionTypeFilter').val();
        const status = $('#transactionStatusFilter').val();
        const date = $('#transactionDateFilter').val();
        
        let queryParams = [];
        if (type) queryParams.push(`type=${encodeURIComponent(type)}`);
        if (status) queryParams.push(`status=${encodeURIComponent(status)}`);
        if (date) queryParams.push(`date=${encodeURIComponent(date)}`);
        
        window.location.href = `admin_transactions.php?${queryParams.join('&')}`;
    }

    // Filter event listeners
    $('#transactionTypeFilter, #transactionStatusFilter, #transactionDateFilter').change(applyTransactionFilters);

    // Manual funding request handling
    function loadManualFundRequests() {
        $.ajax({
            url: '../ajax/admin_manual_fund_requests.php',
            data: { action: 'get_requests' },
            dataType: 'json',
            beforeSend: function() {
                $('#manualFundTableBody').html('<tr><td colspan="9" class="text-center">Loading...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    renderManualFundRequests(response.requests);
                } else {
                    showAlert(response.message || 'Failed to load requests', 'error');
                }
            },
            error: function() {
                showAlert('Error loading requests', 'error');
            }
        });
    }

    function renderManualFundRequests(requests) {
        const $tbody = $('#manualFundTableBody');
        $tbody.empty();
        
        if (requests.length === 0) {
            $tbody.html('<tr><td colspan="9" class="text-center">No pending requests</td></tr>');
            return;
        }

        requests.forEach(req => {
            const statusBadge = req.status === 'pending' ? 
                '<span class="badge badge-warning">Pending</span>' :
                req.status === 'approved' ? 
                '<span class="badge badge-success">Approved</span>' :
                '<span class="badge badge-danger">Rejected</span>';
            
            const actionButtons = req.status === 'pending' ? 
                `<button class="btn btn-sm btn-success approve-btn" data-id="${req.id}">Approve</button>
                 <button class="btn btn-sm btn-danger reject-btn" data-id="${req.id}">Reject</button>` :
                '';
            
            const proofLink = req.proof ? 
                `<a href="../${req.proof}" target="_blank" class="btn btn-sm btn-info">View Proof</a>` : 
                'N/A';
            
            $tbody.append(`
                <tr>
                    <td>${req.id}</td>
                    <td>${req.full_name || 'Unknown'}</td>
                    <td>₦${parseFloat(req.amount).toLocaleString('en-NG')}</td>
                    <td>${req.bank || 'N/A'}</td>
                    <td>${req.sender || 'N/A'}</td>
                    <td>${proofLink}</td>
                    <td>${statusBadge}</td>
                    <td>${new Date(req.created_at).toLocaleString()}</td>
                    <td>${actionButtons}</td>
                </tr>
            `);
        });
    }

    // Handle approve/reject actions
    $(document).on('click', '.approve-btn, .reject-btn', function() {
        const requestId = $(this).data('id');
        const action = $(this).hasClass('approve-btn') ? 'approve' : 'reject';
        
        // Show modal with form
        $('#manualFundModalContent').html(`
            <div class="form-group">
                <label>Are you sure you want to ${action} this request?</label>
                <div class="form-text mb-3">Amount: ₦${$(this).closest('tr').find('td:eq(2)').text()}</div>
            </div>
            <div class="form-group">
                <label for="adminNote">Admin Note</label>
                <textarea id="adminNote" class="form-control" rows="3"></textarea>
            </div>
        `);
        
        $('#manualFundModalFooter').html(`
            <button type="button" class="btn btn-secondary close-modal">Cancel</button>
            <button type="button" class="btn btn-${action === 'approve' ? 'success' : 'danger'}" 
                    id="confirmAction" data-action="${action}" data-id="${requestId}">
                ${action === 'approve' ? 'Approve' : 'Reject'}
            </button>
        `);
        
        $('#manualFundModal').show();
    });

    // Confirm action
    $(document).on('click', '#confirmAction', function() {
        const requestId = $(this).data('id');
        const action = $(this).data('action');
        const note = $('#adminNote').val();
        
        $.ajax({
            url: '../ajax/admin_manual_fund_requests.php',
            method: 'POST',
            data: {
                action: action,
                id: requestId,
                note: note,
                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
            },
            dataType: 'json',
            beforeSend: function() {
                $('#confirmAction').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
            },
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    loadManualFundRequests();
                    $('#manualFundModal').hide();
                } else {
                    showAlert(response.message || 'Action failed', 'error');
                }
            },
            error: function() {
                showAlert('Network error', 'error');
            },
            complete: function() {
                $('#confirmAction').prop('disabled', false).text(action === 'approve' ? 'Approve' : 'Reject');
            }
        });
    });

    // Initial load
    // loadManualFundRequests();
    // $('#refreshManualFundBtn').click(loadManualFundRequests);
});

// Helper function to show alerts
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('#alertsContainer').html(alertHtml);
    $('.alert').alert();
}

// Approve/Reject modal logic for PHP-rendered buttons:
$(document).on('click', '.approve-manual-fund, .reject-manual-fund', function() {
    var id = $(this).data('id');
    var action = $(this).hasClass('approve-manual-fund') ? 'approve' : 'reject';
    $('#manualFundModalContent').html(
      '<div style="margin-bottom:1rem;">Are you sure you want to <strong>' + action + '</strong> this manual funding request?</div>' +
      '<label for="adminNote">Admin Note (optional):</label>' +
      '<textarea id="adminNote" style="width:100%;min-height:60px;"></textarea>'
    );
    $('#manualFundModalFooter').html(
      '<button class="btn btn-outline close-modal">Cancel</button>' +
      '<button class="btn btn-primary" id="confirmManualFundAction" data-id="' + id + '" data-action="' + action + '">' + action.charAt(0).toUpperCase()+action.slice(1) + '</button>'
    );
    $('#manualFundModal').css('display', 'flex');
});

// Confirm Approve/Reject
$(document).on('click', '#confirmManualFundAction', function() {
    var id = $(this).data('id');
    var action = $(this).data('action');
    var note = $('#adminNote').val();
    $.ajax({
        url: '../ajax/admin_manual_fund_requests.php',
        method: 'POST',
        data: {
            id: id,
            action: action,
            note: note,
            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert('Request updated.');
                $('#manualFundModal').hide();
                location.reload();
            } else {
                alert(res.message || 'Failed to update request.');
            }
        },
        error: function(xhr, status, error) {
            alert('Network or server error: ' + error);
        }
    });
});

// Close modal
$(document).on('click', '#manualFundModal .close-modal', function() {
    $('#manualFundModal').hide();
});
$('#manualFundModal').on('click', function(e) {
    if (e.target === this) $(this).hide();
});
</script>
<?php include __DIR__ . '/../includes/spinner.php'; ?>
</body>
</html>
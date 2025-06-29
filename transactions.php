<?php
require_once('config/db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get DB connection
$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $wallet_balance);
$stmt->fetch();
$stmt->close();

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
    <meta name="description" content="Naija eHub Transaction History - View, filter, and manage your transaction records.">
    <title>Transaction History | Naija eHub</title>
    <link rel="stylesheet" href="css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <main class="main-content">
            <header class="header">
        <button class="toggle-sidebar" onclick="toggleSidebar()" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        <h2>Transactions History</h2>
        <div>
          <button class="btn" onclick="toggleAccessibilityPanel()" aria-label="Toggle Accessibility Options">Accessibility</button>
        </div>
      </header>
            <section class="service-header">
                <div class="header-content">
                    <p>View, filter, and manage your transaction records</p>
                </div>
                <div class="service-icon">
                    <i class="fas fa-history"></i>
                </div>
            </section>

            <section class="transaction-section">
                <div class="filter-controls">
                    <input type="text" id="searchTransactions" placeholder="Search by description or reference">
                    <select id="filterType">
                        <option value="all">All Transactions</option>
                        <option value="credit">Credits</option>
                        <option value="debit">Debits</option>
                    </select>
                    <select id="filterStatus">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                    </select>
                    <input type="date" id="filterDateStart" placeholder="Start Date">
                    <input type="date" id="filterDateEnd" placeholder="End Date">
                    <button class="btn btn-outline" id="applyFilters">Apply Filters</button>
                    <button class="btn btn-outline" id="resetFilters">Reset</button>
                    <select id="bulkAction">
                        <option value="">Bulk Actions</option>
                        <option value="export_csv">Export Selected as CSV</option>
                        <option value="export_pdf">Export Selected as PDF</option>
                    </select>
                    <button class="btn btn-primary" id="applyBulkAction">Apply</button>
                </div>

                <div class="transaction-table">
                    <table id="userTransactionsTable" style="width: 100%;">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
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
                                    <td colspan="6" class="no-data">No transactions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr data-type="<?php echo htmlspecialchars($txn['type']); ?>" 
                                        data-status="<?php echo htmlspecialchars($txn['status']); ?>" 
                                        data-date="<?php echo date('Y-m-d', strtotime($txn['created_at'])); ?>"
                                        data-id="<?php echo htmlspecialchars($txn['id']); ?>">
                                        <td><input type="checkbox" class="select-transaction" data-id="<?php echo htmlspecialchars($txn['id']); ?>"></td>
                                        <td><?php echo htmlspecialchars($txn['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($txn['description']); ?></td>
                                        <td>₦<?php echo number_format($txn['amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($txn['status']); ?>" 
                                                  title="<?php echo ucfirst($txn['status']); ?>">
                                                <?php
                                                    if ($txn['status'] === 'completed' || $txn['status'] === 'success') echo 'Success';
                                                    elseif ($txn['status'] === 'pending') echo 'Pending';
                                                    else echo 'Failed';
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline view-receipt" 
                                                    data-reference="<?php echo htmlspecialchars($txn['reference']); ?>" 
                                                    data-details='<?php echo json_encode($txn); ?>'>Receipt</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="?page=<?php echo $page - 1; ?>" class="btn btn-outline <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                    <span id="pageInfo">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <a href="?page=<?php echo $page + 1; ?>" class="btn btn-outline <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <div class="back-to-dashboard">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </section>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div class="modal" id="receiptModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeReceiptModal()">×</span>
            <h3>Transaction Receipt</h3>
            <div class="modal-body">
                <p><strong>Transaction ID:</strong> <span id="receiptId"></span></p>
                <p><strong>Reference:</strong> <span id="receiptReference"></span></p>
                <p><strong>Date:</strong> <span id="receiptDate"></span></p>
                <p><strong>Type:</strong> <span id="receiptType"></span></p>
                <p><strong>Description:</strong> <span id="receiptDescription"></span></p>
                <p><strong>Amount:</strong> <span id="receiptAmount"></span></p>
                <p><strong>Status:</strong> <span id="receiptStatus"></span></p>
                <button class="btn btn-primary" id="printReceipt">Print Receipt</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/whatsapp-chat.php'; ?>
    <?php include __DIR__ . '/includes/footer.php'; ?>
    <?php include __DIR__ . '/includes/spinner.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>

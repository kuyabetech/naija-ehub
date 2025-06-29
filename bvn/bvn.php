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

// Pagination logic for BVN requests
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// Get total BVN request count
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM bvn_requests WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_stmt->bind_result($totalRequests);
$count_stmt->fetch();
$count_stmt->close();

$totalPages = ceil($totalRequests / $perPage);

// Fetch paginated BVN requests
$requests = [];
$req_stmt = $conn->prepare("SELECT id, request_type, bvn, amount, status, monnify_reference, details, created_at FROM bvn_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$req_stmt->bind_param("iii", $user_id, $perPage, $offset);
$req_stmt->execute();
$result = $req_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$req_stmt->close();
$conn->close();

// Monnify payment link for BVN services (e.g., verification fee)
$monnify_contract_code = '3380031445';
$monnify_payment_link = MONNIFY_BASE_URL . '/checkout/' .
    '?amount=500' .
    '¤cy=NGN' .
    '&reference=' . urlencode("bvn_{$user_id}_" . time()) .
    '&customerName=' . urlencode($full_name ?: "User {$user_id}") .
    '&customerEmail=' . urlencode($email ?: "user{$user_id}@naijaehub.com") .
    '&paymentDescription=' . urlencode('BVN Service Fee') .
    '&contractCode=' . urlencode($monnify_contract_code);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Naija eHub BVN Services - Verify, update, or retrieve your Bank Verification Number.">
  <title>BVN Services | Naija eHub</title>
  <link rel="stylesheet" href="../css/main.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  
</head>
<body>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <?php require_once '../includes/sidebar.php' ?>
    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <button class="toggle-sidebar" onclick="toggleSidebar()" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        <h2>BVN Services</h2>
        <div>
          <button class="btn" onclick="toggleAccessibilityPanel()" aria-label="Toggle Accessibility Options">Accessibility</button>
        </div>
      </header>

      <!-- Service Header -->
      <section class="service-header">
        <div class="header-content">
          <h2>BVN Services</h2>
          <p>Verify, update, or retrieve your Bank Verification Number</p>
        </div>
        <div class="service-icon">
          <i class="fas fa-fingerprint"></i>
        </div>
      </section>

      <!-- Service Options -->
      <section class="service-options">
        <div class="option-card active" data-option="verify" role="button" tabindex="0" aria-label="Verify BVN Service">
          <i class="fas fa-search"></i>
          <h4>Verify BVN</h4>
          <p>Confirm your BVN details (₦500 fee)</p>
          <button class="btn btn-outline" onclick="openBvnModal('verify')" aria-label="Start BVN Verification">Start Verification</button>
        </div>
        <div class="option-card" data-option="update" role="button" tabindex="0" aria-label="Update BVN Details Service">
          <i class="fas fa-edit"></i>
          <h4>Update Details</h4>
          <p>Modify your registered information</p>
          <button class="btn btn-outline" onclick="openBvnModal('update')" aria-label="Update BVN Details"><a href="update_bvn.php">Update Details</a></button>
        </div>
        <div class="option-card" data-option="retrieve" role="button" tabindex="0" aria-label="Retrieve BVN Service">
          <i class="fas fa-key"></i>
          <h4>Retrieve BVN</h4>
          <p>Recover lost BVN number (₦500 fee)</p>
          <button class="btn btn-outline" onclick="openBvnModal('retrieve')" aria-label="Retrieve BVN">Retrieve BVN</button>
        </div>
      </section>

      <!-- BVN Request History -->
      <section class="request-section">
        <div class="section-header">
          <h3>BVN Request History</h3>
          <div class="filter-controls">
            <select id="filterType" onchange="filterRequests()" aria-label="Filter by Request Type">
              <option value="all">All Requests</option>
              <option value="verify">Verify</option>
              <option value="update">Update</option>
              <option value="retrieve">Retrieve</option>
            </select>
            <select id="filterStatus" onchange="filterRequests()" aria-label="Filter by Status">
              <option value="all">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="completed">Completed</option>
              <option value="failed">Failed</option>
            </select>
            <input type="date" id="filterDateStart" onchange="filterRequests()" aria-label="Start Date">
            <input type="date" id="filterDateEnd" onchange="filterRequests()" aria-label="End Date">
            <button class="btn btn-outline" id="resetFilters" onclick="resetFilters()" aria-label="Reset Filters">Reset</button>
            <button class="btn btn-primary" onclick="exportRequests()" aria-label="Export Requests">Export as CSV</button>
          </div>
        </div>
        <div class="request-table">
          <table id="bvnRequestsTable" style="width: 100%;">
            <thead>
              <tr>
                <th>Date/Time</th>
                <th>Type</th>
                <th>BVN</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="requestList">
              <?php if (empty($requests)): ?>
                <tr>
                  <td colspan="6" style="text-align: center; color: #666;">No BVN requests found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($requests as $req): ?>
                  <tr data-type="<?php echo htmlspecialchars($req['request_type']); ?>" data-status="<?php echo htmlspecialchars($req['status']); ?>" data-date="<?php echo date('Y-m-d', strtotime($req['created_at'])); ?>">
                    <td><?php echo htmlspecialchars($req['created_at']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($req['request_type'])); ?></td>
                    <td><?php echo htmlspecialchars($req['bvn'] ?: 'N/A'); ?></td>
                    <td>₦<?php echo number_format($req['amount'], 2); ?></td>
                    <td class="status-<?php echo htmlspecialchars($req['status']); ?>">
                      <?php echo ucfirst($req['status']); ?>
                    </td>
                    <td>
                      <button class="btn btn-outline view-details" data-details='<?php echo json_encode($req); ?>' aria-label="View Request Details">Details</button>
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

  <!-- BVN Service Modal -->
  <div id="bvnModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modalTitle">BVN Service</h3>
        <button class="close-modal" onclick="closeBvnModal()" aria-label="Close BVN Service Modal">×</button>
      </div>
      <div class="modal-body">
        <form id="bvnForm">
          <input type="hidden" id="requestType" name="request_type">
          <div class="form-group">
            <label for="bvnNumber">BVN Number (optional for Retrieve):</label>
            <input type="text" id="bvnNumber" name="bvn" maxlength="11" pattern="\d{11}" placeholder="Enter 11-digit BVN">
          </div>
          <div class="form-group" id="detailsField" style="display: none;">
            <label for="bvnDetails">Details (e.g., new name or address for Update):</label>
            <textarea id="bvnDetails" name="details" rows="4" placeholder="Enter details"></textarea>
          </div>
          <div class="form-group" id="paymentField">
            <label for="paymentMethod">Payment Method (₦500 fee):</label>
            <select id="paymentMethod" name="payment_method" aria-label="Select Payment Method">
              <option value="wallet">Wallet (Balance: ₦<?php echo number_format($wallet_balance, 2); ?>)</option>
              <option value="card">Card/USSD</option>
            </select>
          </div>
          <div id="cardPayment" style="display: none;">
            <form id="monnifyBvnForm" target="_blank" action="<?php echo htmlspecialchars($monnify_payment_link); ?>" method="get">
              <input type="hidden" name="amount" value="500">
              <button type="submit" class="btn btn-primary">Pay with Card/USSD</button>
            </form>
          </div>
          <div id="walletPayment">
            <button type="submit" class="btn btn-primary">Submit Request</button>
          </div>
          <div id="bvnMsg"></div>
        </form>
      </div>
    </div>
  </div>

  <!-- Request Details Modal -->
  <div id="detailsModal" class="modal-overlay">
    <div class="modal">
      <div class="modal-header">
        <h3>Request Details</h3>
        <button class="close-modal" onclick="closeDetailsModal()" aria-label="Close Request Details Modal">×</button>
      </div>
      <div class="modal-body" id="detailsContent"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" id="printDetails" aria-label="Print Request Details">
          <i class="fas fa-print"></i> Print
        </button>
        <button class="btn btn-primary" id="downloadDetails" aria-label="Download Request Details as PDF">
          <i class="fas fa-download"></i> Download PDF
        </button>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <?php include __DIR__ . '/../includes/spinner.php'; ?>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script>
    $(document).ready(function() {
      const table = $('#bvnRequestsTable').DataTable({
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
        $('#sidebar').toggleClass('active');
      }

      // BVN Service Modal
      function openBvnModal(type) {
        $('#modalTitle').text(type.charAt(0).toUpperCase() + type.slice(1) + ' BVN');
        $('#requestType').val(type);
        $('#bvnNumber').prop('required', type !== 'retrieve');
        $('#detailsField').toggle(type === 'update');
        $('#paymentField').toggle(type === 'verify' || type === 'retrieve');
        $('#bvnModal').show();
      }

      function closeBvnModal() {
        $('#bvnModal').hide();
        $('#bvnForm')[0].reset();
        $('#bvnMsg').text('');
        $('#cardPayment').hide();
        $('#walletPayment').show();
      }

      // Request Details Modal
      $('.view-details').on('click', function() {
        const details = $(this).data('details');
        $('#detailsContent').html(`
          <p><strong>Request Type:</strong> ${details.request_type.charAt(0).toUpperCase() + details.request_type.slice(1)}</p>
          <p><strong>BVN:</strong> ${details.bvn || 'N/A'}</p>
          <p><strong>Amount:</strong> ₦${Number(details.amount).toFixed(2)}</p>
          <p><strong>Status:</strong> ${details.status.charAt(0).toUpperCase() + details.status.slice(1)}</p>
          <p><strong>Date:</strong> ${details.created_at}</p>
          <p><strong>Details:</strong> ${details.details || 'N/A'}</p>
          <p><strong>Monnify Reference:</strong> ${details.monnify_reference || 'N/A'}</p>
        `);
        $('#detailsModal').show();
      });

      function closeDetailsModal() {
        $('#detailsModal').hide();
      }

      // Payment Method Toggle
      $('#paymentMethod').on('change', function() {
        $('#cardPayment').toggle(this.value === 'card');
        $('#walletPayment').toggle(this.value === 'wallet');
      });

      // BVN Form Submission
      $('#bvnForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'bvn_request');
        const msg = $('#bvnMsg');
        msg.text('');
        fetch('../ajax/bvn_request.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          msg.css('color', data.success ? '#067c3c' : '#d32f2f');
          msg.text(data.message);
          if (data.success) {
            this.reset();
            setTimeout(() => location.reload(), 2000);
          }
        })
        .catch(() => {
          msg.css('color', '#d32f2f');
          msg.text('Error submitting request. Please try again.');
        });
      });

      // Request Filtering
      function filterRequests() {
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

      // Export Requests
      function exportRequests() {
        const rows = $('#requestList tr:visible');
        let csv = 'Date,Type,BVN,Amount,Status,Monnify Reference\n';
        rows.each(function() {
          const cells = $(this).find('td');
          if (cells.length) {
            const rowData = [
              $(cells[0]).text(),
              $(cells[1]).text(),
              $(cells[2]).text(),
              $(cells[3]).text(),
              $(cells[4]).text(),
              $(cells[5]).find('button').data('details').monnify_reference || 'N/A'
            ].join(',');
            csv += `${rowData}\n`;
          }
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'bvn_requests.csv';
        a.click();
        window.URL.revokeObjectURL(url);
      }

      // Print Details
      $('#printDetails').on('click', function() {
        const content = $('#detailsContent').html();
        const win = window.open('', '', 'width=600,height=400');
        win.document.write(`<html><head><title>Print Request Details</title></head><body>${content}</body></html>`);
        win.document.close();
        win.print();
      });

      // Download Details as PDF
      $('#downloadDetails').on('click', function() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const content = $('#detailsContent').text().split('\n');
        let y = 20;
        doc.setFontSize(14);
        doc.text('BVN Request Details', 15, y);
        y += 10;
        doc.setFontSize(11);
        content.forEach(line => {
          if (line.trim()) {
            doc.text(line, 15, y);
            y += 8;
          }
        });
        doc.save('bvn_request.pdf');
      });

      // Close Modals on Outside Click
      $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal-overlay')) {
          closeBvnModal();
          closeDetailsModal();
        }
      });
    });
  </script>
</body>
</html>
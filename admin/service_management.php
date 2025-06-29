<?php
session_start();
require_once('../config/db.php');

// Simple admin authentication check (customize as needed)
if (!isset($_SESSION['admin_id'])) {
  header('Location: ../admin/adminLogin.php');
  exit();
}

$conn = getDbConnection();

// Fetch all services
$services = [];
$res = $conn->query("SELECT id, title, description, is_active, category, fee FROM services ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) {
    $services[] = $row;
}

// Fetch all BVN update requests
$bvnRequests = [];
$res = $conn->query("SELECT r.id, u.full_name, r.modification_type, r.details, r.support_doc, r.status, r.created_at 
    FROM bvn_update_requests r 
    LEFT JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC");
while ($row = $res->fetch_assoc()) {
    $row['details'] = json_decode($row['details'], true);
    $bvnRequests[] = $row;
}
$notifications = [];
$res = $conn->query("SELECT message, created_at, is_read FROM admin_notifications WHERE admin_id = {$_SESSION['admin_id']} ORDER BY created_at DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}

// Only close the connection here, after all queries are done
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Service Management | Naija eHub Admin</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <style>
    stDetailModal .modal-header h3 {
        font-size: 1.1rem;
        color: #1a6b54;
        margin: 0;
      }
.content-header{
  text-align: center;
}
      @keyframes popupAlert {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
      }
      #bvnRequestDetailModal .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        margin-bottom: 1rem;
      }
      #bvnRequestDetailModal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(34,34,34,0.18);
        z-index: 9999;
        align-items: center;
        justify-content: center;
      }
      #bvnRequestDetailModal.active {
        display: flex;
      }
      #bvnRequestDetailModal .user-alert-modal {
        max-width: 420px;
        width: 96%;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        padding: 1.5rem 1.2rem;
        position: relative;
        margin: 0 auto;
        animation: popupAlert 0.18s cubic-bezier(.4,2,.6,1) both;
        display: flex;
        flex-direction: column;
        top: 0;
        left: 0;
        transform: none;
      }
      #bvnRequestDetailModal .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eee;
        margin-bottom: 1rem;
      }
      #bvnRequeestDetailModal .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #d32f2f;
        cursor: pointer;
      }
      #bvnRequestDetailModal .modal-body {
        padding: 0.5rem 0;
      }
      #bvnRequestDetailModal .modal-footer {
        margin-top: 1.2rem;
        text-align: right;
      }
    .service-switch-tabs { display:flex; gap:1.2rem; margin-bottom:1.2rem; }
    .service-tab-btn {
      background: #f5f5f5;
      border: none;
      padding: 0.7rem 1.4rem;
      border-radius: 5px 5px 0 0;
      font-size: 1rem;
      color: #067c3c;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.18s;
    }
    .service-tab-btn.active, .service-tab-btn:focus {
      background: #067c3c;
      color: #fff;
      outline: none;
    }
    .table-responsive { background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.07); padding:1.2rem; overflow-x:auto; }
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
        <a href="#" class="active" data-section="overview">
          <i class="fas fa-tachometer-alt"></i> Overview
        </a>
        <a href="#" data-section="users">
          <i class="fas fa-users"></i> User Management
        </a>
        <a href="#" data-section="transactions">
          <i class="fas fa-exchange-alt"></i> Transactions
        </a>
        <a href="service_management.php" data-section="services">
          <i class="fas fa-cogs"></i> Service Management
        </a>
        <a href="#" data-section="reports">
          <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a href="#" data-section="settings">
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
            <img src="../assets/img/admin-avatar.png" alt="Admin" style="width:38px;height:38px;border-radius:50%;object-fit:cover;box-shadow:0 2px 8px rgba(6,124,60,0.09);">
            <span style="font-weight:600;color:#1a6b54;font-size:1.05rem;">Admin User</span>
          </div>
        </div>
      </header>
      <div class="content-wrapper">
        <div class="content-header">
          <h2>Service Management</h2>
          <p>Manage services and BVN update requests.</p>
        </div>
        
        <!-- Service Management Section -->
        <section id="serviceManagementSection">
          <!-- Content will be loaded here -->
            <div class="container" style="max-width:1200px;margin:2rem auto;">
    <div class="service-switch-tabs">
      <button class="service-tab-btn active" data-service="servicesTableWrap">Services</button>
      <button class="service-tab-btn" data-service="bvnRequestsSubSection">BVN Update Requests</button>
    </div>
    <div id="servicesTableWrap">
      <div class="table-responsive">
        <table id="servicesTable" class="display" style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:#f5f5f5;">
              <th>ID</th>
              <th>Title</th>
              <th>Description</th>
              <th>Category</th>
              <th>Fee</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($services as $s): ?>
            <tr>
              <td><?php echo $s['id']; ?></td>
              <td><?php echo htmlspecialchars($s['title']); ?></td>
              <td><?php echo htmlspecialchars($s['description']); ?></td>
              <td><?php echo htmlspecialchars($s['category']); ?></td>
              <td>₦<?php echo number_format($s['fee'],2); ?></td>
              <td>
                <?php echo $s['is_active'] ? '<span style="color:#067c3c;">Active</span>' : '<span style="color:#d32f2f;">Inactive</span>'; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div id="bvnRequestsSubSection" style="display:none;">
      <div class="section-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.2rem; margin-bottom: 1.5rem;">
        <h4 style="font-size: 1.1rem; color: #1a6b54; margin: 0;">BVN Update Requests</h4>
        <div class="section-actions" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
          <select id="bvnTypeFilter" style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #e1e1e1;">
            <option value="">All Types</option>
            <option value="change_of_name">Change of Name</option>
            <option value="date_of_birth">Date of Birth</option>
            <option value="change_of_address">Change of Address</option>
            <option value="change_of_phone">Change of Phone Number</option>
            <option value="rearrangement_of_name">Rearrangement of Name</option>
          </select>
          <select id="bvnStatusFilter" style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #e1e1e1;">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
          </select>
          <input type="date" id="bvnDateFilter" style="padding: 0.5rem 1rem; border-radius: 5px; border: 1px solid #e1e1e1;">
        </div>
      </div>
      <div class="table-responsive">
        <table id="bvnRequestsTable" class="display" style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="background:#f5f5f5;">
              <th>ID</th>
              <th>User</th>
              <th>Type</th>
              <th>Details</th>
              <th>Document</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bvnRequests as $req): ?>
            <tr data-type="<?php echo htmlspecialchars($req['modification_type']); ?>" data-status="<?php echo htmlspecialchars($req['status']); ?>" data-date="<?php echo substr($req['created_at'],0,10); ?>">
              <td><?php echo $req['id']; ?></td>
              <td><?php echo htmlspecialchars($req['full_name']); ?></td>
              <td><?php echo htmlspecialchars($req['modification_type']); ?></td>
              <td>
                <?php foreach ($req['details'] as $k => $v) echo "<strong>$k:</strong> " . htmlspecialchars($v) . "<br>"; ?>
              </td>
              <td>
                <?php if ($req['support_doc']): ?>
                  <a href="../<?php echo htmlspecialchars($req['support_doc']); ?>" target="_blank">View</a>
                <?php else: ?>
                  N/A
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($req['status']); ?></td>
              <td><?php echo $req['created_at']; ?></td>
              <td>
                <button class="btn btn-outline btn-sm view-bvn-request" data-id="<?php echo $req['id']; ?>">View</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- BVN Request Detail Modal -->
    <div id="bvnRequestDetailModal" class="modal-overlay" style="display: none;">
      <div class="modal user-alert-modal">
        <div class="modal-header">
          <h3>BVN Update Request Details</h3>
          <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body" id="bvnRequestDetailContent">
          <!-- Content loaded dynamically -->
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary close-modal">OK</button>
        </div>
      </div>
    </div>
   
  </div>
 
        </section>
      </div>
    </main>
  <!-- Spinner Overlay -->
  <div id="globalSpinner" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:99999;background:rgba(255,255,255,0.65);align-items:center;justify-content:center;">
    <div style="display:flex;flex-direction:column;align-items:center;">
      <div class="spinner" style="border:6px solid #e0e0e0;border-top:6px solid #067c3c;border-radius:50%;width:48px;height:48px;animation:spin 1s linear infinite;"></div>
      <div style="margin-top:1rem;color:#067c3c;font-weight:600;">Loading...</div>
    </div>
  </div>
  <style>
    @keyframes spin {
      0% { transform: rotate(0deg);}
      100% { transform: rotate(360deg);}
    }
  </style>
  <!-- script links -->
  <script src="../js/admin.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script>
    // Show spinner on page load and AJAX
    document.addEventListener('DOMContentLoaded', function() {
      var spinner = document.getElementById('globalSpinner');
      spinner.style.display = 'flex';
      window.addEventListener('load', function() {
        spinner.style.display = 'none';
      });
    });
    if (window.jQuery) {
      $(document).ajaxStart(function(){ $('#globalSpinner').show(); });
      $(document).ajaxStop(function(){ $('#globalSpinner').hide(); });
    }

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


    $(document).ready(function() {
      $('#servicesTable').DataTable({ "ordering": false });
      var bvnTable = $('#bvnRequestsTable').DataTable({ "ordering": false });

      // Service switch tabs logic
      $('.service-tab-btn').on('click', function() {
        $('.service-tab-btn').removeClass('active');
        $(this).addClass('active');
        if ($(this).data('service') === 'servicesTableWrap') {
          $('#servicesTableWrap').show();
          $('#bvnRequestsSubSection').hide();
        } else {
          $('#servicesTableWrap').hide();
          $('#bvnRequestsSubSection').show();
        }
      });

      // BVN Requests Filter Functionality
      $('#bvnTypeFilter, #bvnStatusFilter, #bvnDateFilter').on('change', function() {
        var type = $('#bvnTypeFilter').val();
        var status = $('#bvnStatusFilter').val();
        var date = $('#bvnDateFilter').val();
        bvnTable.rows().every(function() {
          var $row = $(this.node());
          let show = true;
          if (type && $row.attr('data-type') !== type) show = false;
          if (status && $row.attr('data-status') !== status) show = false;
          if (date && $row.attr('data-date') !== date) show = false;
          $row.toggle(show);
        });
      });

      // BVN Request Details Popup
      var bvnRequests = <?php echo json_encode($bvnRequests); ?>;
      $(document).on('click', '.view-bvn-request', function(e) {
        e.preventDefault();
        const reqId = $(this).data('id');
        const req = bvnRequests.find(r => r.id == reqId);
        let html = '';
        if (req) {
          html += `<div style="margin-bottom:0.7rem;"><strong>User:</strong> ${req.full_name}</div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Type:</strong> ${req.modification_type}</div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Status:</strong> <span id="bvn-status-span">${req.status}</span></div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Date:</strong> ${req.created_at}</div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Details:</strong><br>`;
          for (const k in req.details) {
            html += `<strong>${k}:</strong> ${req.details[k]}<br>`;
          }
          html += `</div>`;
          if (req.support_doc) {
            html += `<div style="margin-bottom:0.7rem;"><strong>Document:</strong> <a href="../${req.support_doc}" target="_blank">View</a></div>`;
          }
          // Status update buttons
          html += `<div style="margin-top:1.2rem;">
            <button class="btn btn-success btn-sm update-bvn-status" data-id="${req.id}" data-status="completed" style="margin-right:0.5rem;">Mark as Completed</button>
            <button class="btn btn-danger btn-sm update-bvn-status" data-id="${req.id}" data-status="rejected">Reject</button>
          </div>`;
        } else {
          html = '<div>Request not found.</div>';
        }
        $('#bvnRequestDetailContent').html(html);
        $('#bvnRequestDetailModal').addClass('active');
      });

      // Handle status update button click
      $(document).on('click', '.update-bvn-status', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var status = $(this).data('status');
        if (!confirm('Are you sure you want to update the status to "' + status + '"?')) return;
        $.post('service_management.php', { action: 'update_bvn_status', id: id, status: status }, function(res) {
          if (res.success) {
            $('#bvn-status-span').text(status);
            alert('Status updated!');
            // Optionally update the table row status without reload
            $(`#bvnRequestsTable tr`).each(function() {
              if ($(this).find('.view-bvn-request').data('id') == id) {
                $(this).find('td').eq(5).text(status);
              }
            });
          } else {
            alert('Failed to update status.');
          }
        }, 'json');
      });

      // Ensure modal closes on close button or outside click
      $('#bvnRequestDetailModal').on('click', '.close-modal', function(e) {
        e.preventDefault();
        $('#bvnRequestDetailModal').removeClass('active');
      });
      $('#bvnRequestDetailModal').on('mousedown', function(e) {
        if (e.target === this) {
          $('#bvnRequestDetailModal').removeClass('active');
        }
      });
    });
  </script>
</body>
</html>

<?php
// Handle AJAX request for updating BVN request status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bvn_status') {
    require_once('../config/db.php');
    $conn = getDbConnection();
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $allowed = ['pending', 'completed', 'rejected'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE bvn_update_requests SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => $ok]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
?>

<?php
session_start();
require_once('../config/db.php');

// --- Restrict access to only admin users ---
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  // Optionally, destroy session for extra security
  session_destroy();
  header('Location: adminLogin.php');
  exit();
}

$conn = getDbConnection();

// --- Reports Section Backend (example: total transactions per service) ---
$serviceReports = [];
$res = $conn->query("SELECT service_type, COUNT(*) as total FROM transactions GROUP BY service_type");
while ($row = $res->fetch_assoc()) {
    $serviceReports[] = $row;
}
$notifications = [];
$res = $conn->query("SELECT message, created_at, is_read FROM admin_notifications WHERE admin_id = {$_SESSION['admin_id']} ORDER BY created_at DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}



// Only close the connection here, after all queries are done
$conn->close();
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Naija eHub</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
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

<!-- Reports Section -->
      <section class="admin-section" id="reportsSection">
        <div class="section-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1.2rem; margin-bottom: 1.5rem;">
          <h4 style="font-size: 1.2rem; color: #1a6b54; margin: 0;">Reports</h4>
        </div>
        <div class="table-responsive" style="background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.07);padding:1.2rem;overflow-x:auto;">
          <table id="reportsTable" class="display" style="width:100%;border-collapse:collapse;">
            <thead>
              <tr style="background:#f5f5f5;">
                <th style="padding:0.7rem;">Service Type</th>
                <th style="padding:0.7rem;">Total Transactions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($serviceReports as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['service_type']); ?></td>
                <td><?php echo $r['total']; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      </main>
      </div>


<!-- JavaScript Libraries -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/admin.js"></script>
<script></script>
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

        // Admin logout button
        $('#adminLogoutBtn').on('click', function() {
          $.post('admin.php', {action: 'logout'}, function(res) {
            if (res.success) {
              window.location.href = 'login.php';
            } else {
              alert(res.message || 'Failed to logout.');
            }
          }, 'json');
        });
  </script>
</body>
</html>
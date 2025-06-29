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
$notifications = [];
$res = $conn->query("SELECT message, created_at, is_read FROM admin_notifications WHERE admin_id = {$_SESSION['admin_id']} ORDER BY created_at DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    $notifications[] = $row;
}


// --- API Keys Section Backend ---
$apiKeys = [];
if ($conn->query("SHOW TABLES LIKE 'system_settings'")->num_rows) {
    $res = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'api_key_%'");
    while ($row = $res->fetch_assoc()) {
        $apiKeys[$row['setting_key']] = $row['setting_value'];
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Naija eHub</title>
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
    /* ...existing code... */
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
      <header class="main-header">
        <div class="header-left">
          <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
          </button>
          <h3 id="adminSectionTitle">Admin Dashboard</h3>
        </div>
        
        <div class="header-right">
          <div class="notification-bell">
            <i class="fas fa-bell"></i>
            <span class="badge">5</span>
          </div>
          <div class="admin-avatar">
            <img src="assets/img/admin-avatar.png" alt="Admin">
            <span>Admin User</span>
          </div>
        </div>
      </header>


<!-- JavaScript Libraries -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/admin.js"></script>
<script>
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
    // Fill dashboard stats from PHP variables
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('totalUsers').textContent = "<?php echo $totalUsers; ?>";
      document.getElementById('userChange').textContent = "<?php echo $userChange; ?>";
      document.getElementById('totalTransactions').textContent = "<?php echo $totalTransactions; ?>";
      document.getElementById('transactionVolume').textContent = "<?php echo number_format($transactionVolume, 2); ?>";
      document.getElementById('totalRevenue').textContent = "<?php echo number_format($totalRevenue, 2); ?>";
      document.getElementById('revenueChange').textContent = "<?php echo number_format($revenueChange, 2); ?>";
      document.getElementById('ninVerifications').textContent = "<?php echo $ninVerifications; ?>";
      // Recent activity
      var activity = <?php echo json_encode($recentActivity); ?>;
      var activityList = document.getElementById('recentActivity');
      if (activityList && activity.length) {
        // Show the most recent as "Current Activity"
        var current = activity[0];
        if (current) {
          var div = document.createElement('div');
          div.className = 'activity-item';
          div.innerHTML = "<strong>Current:</strong> <strong>" + (current.full_name || 'System') + "</strong> " +
            current.action + " <span class='activity-date'>" + current.created_at + "</span>";
          div.style.background = "#e0f2ec";
          div.style.borderRadius = "6px";
          div.style.padding = "0.5rem 0.7rem";
          div.style.marginBottom = "0.7rem";
          activityList.appendChild(div);
        }
        // Show the rest as "Recent Activity"
        for (var i = 1; i < activity.length; i++) {
          var act = activity[i];
          var div = document.createElement('div');
          div.className = 'activity-item';
          div.innerHTML = "<strong>" + (act.full_name || 'System') + "</strong> " +
            act.action + " <span class='activity-date'>" + act.created_at + "</span>";
          activityList.appendChild(div);
        }
      } else if (activityList) {
        activityList.innerHTML = "<div class='activity-item'>No recent activity.</div>";
      }
    });

    // Admin Section Tabs Logic (make all sections work responsively)
    document.addEventListener('DOMContentLoaded', function() {
      var tabBtns = document.querySelectorAll('.admin-tab-btn');
      var sections = document.querySelectorAll('.admin-section');
      tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
          tabBtns.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          sections.forEach(s => s.classList.remove('active'));
          var sec = document.getElementById(btn.getAttribute('data-section'));
          if (sec) sec.classList.add('active');
        });
      });
      // Show first tab by default
      tabBtns[0].click();
    });

    // User Management Table: Add User Modal
    document.addEventListener('DOMContentLoaded', function() {
      // Add User Modal logic (centered alert style)
      var addUserBtn = document.getElementById('addUserBtn');
      var addUserModal = document.getElementById('addUserModal');
      var closeBtns = addUserModal.querySelectorAll('.close-modal');
      if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
          addUserModal.classList.add('active');
        });
      }
      closeBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
          addUserModal.classList.remove('active');
        });
      });
      addUserModal.addEventListener('click', function(e) {
        if (e.target === addUserModal) addUserModal.classList.remove('active');
      });

      // Add Service Modal logic (centered alert style)
      var addServiceBtn = document.getElementById('addServiceBtn');
      var addServiceModal = document.getElementById('addServiceModal');
      if (addServiceBtn && addServiceModal) {
        var closeServiceBtns = addServiceModal.querySelectorAll('.close-modal');
        addServiceBtn.addEventListener('click', function() {
          addServiceModal.classList.add('active');
        });
        closeServiceBtns.forEach(function(btn) {
          btn.addEventListener('click', function() {
            addServiceModal.classList.remove('active');
          });
        });
        addServiceModal.addEventListener('click', function(e) {
          if (e.target === addServiceModal) addServiceModal.classList.remove('active');
        });
      }
    });

    // Settings Modal logic
    document.addEventListener('DOMContentLoaded', function() {
      var editSettingsBtn = document.getElementById('editSettingsBtn');
      var editSettingsModal = document.getElementById('editSettingsModal');
      var closeEditBtns = editSettingsModal.querySelectorAll('.close-modal');
      var settingsFields = document.getElementById('settingsFields');
      var settingsData = <?php echo json_encode($settings); ?>;
      if (editSettingsBtn && editSettingsModal) {
        editSettingsBtn.addEventListener('click', function() {
          // Populate fields
          settingsFields.innerHTML = '';
          Object.keys(settingsData).forEach(function(key) {
            var val = settingsData[key];
            settingsFields.innerHTML += `
              <div class="form-group">
                <label for="setting_${key}">${key}</label>
                <input type="text" id="setting_${key}" name="${key}" value="${val}">
              </div>
            `;
          });
          editSettingsModal.classList.add('active');
        });
        closeEditBtns.forEach(function(btn) {
          btn.addEventListener('click', function() {
            editSettingsModal.classList.remove('active');
          });
        });
        editSettingsModal.addEventListener('click', function(e) {
          if (e.target === editSettingsModal) editSettingsModal.classList.remove('active');
        });
      }
      // Refresh settings
      var refreshSettingsBtn = document.getElementById('refreshSettingsBtn');
      if (refreshSettingsBtn) {
        refreshSettingsBtn.addEventListener('click', function() {
          location.reload();
        });
      }
    });

    // Add User AJAX
    $(document).ready(function() {
      $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        var data = {
          action: 'add_user',
          full_name: $('#newUserName').val(),
          email: $('#newUserEmail').val(),
          phone: $('#newUserPhone').val(),
          role: $('#newUserRole').val(),
          password: $('#newUserPassword').val()
        };
        $.post('admin.php', data, function(res) {
          if (res.success) {
            alert(res.message);
            location.reload();
          } else {
            alert(res.message);
          }
        }, 'json');
      });

      // Add Service AJAX
      $('#addServiceForm').on('submit', function(e) {
        e.preventDefault();
        var data = {
          action: 'add_service',
          title: $('#serviceTitle').val(),
          description: $('#serviceDescription').val(),
          category: $('#serviceCategory').val(),
          fee: $('#serviceFee').val(),
          icon_class: $('#serviceIconClass').val(),
          link: $('#serviceLink').val(),
          is_active: $('#serviceStatus').val()
        };
        $.post('admin.php', data, function(res) {
          if (res.success) {
            alert(res.message);
            location.reload();
          } else {
            alert(res.message);
          }
        }, 'json');
      });

      // Edit Settings AJAX
      $('#editSettingsForm').on('submit', function(e) {
        e.preventDefault();
        var data = $(this).serializeArray();
        data.push({name: 'action', value: 'update_settings'});
        $.post('admin.php', data, function(res) {
          if (res.success) {
            alert(res.message);
            location.reload();
          } else {
            alert(res.message);
          }
        }, 'json');
      });

      // User Search AJAX
      $('#userSearchBtn').on('click', function(e) {
        e.preventDefault();
        var q = $('#userSearch').val();
        $.get('admin.php', {action: 'search_users', q: q}, function(users) {
          var $tbody = $('#usersTable tbody');
          $tbody.empty();
          users.forEach(function(u) {
            $tbody.append(
              `<tr>
                <td>${u.id}</td>
                <td>${u.full_name}</td>
                <td>${u.email}</td>
                <td>${u.phone}</td>
                <td>${u.created_at}</td>
                <td>${u.status}</td>
                <td>₦${parseFloat(u.wallet_balance).toLocaleString()}</td>
                <td>
                  <button class="btn btn-outline btn-sm view-user" data-id="${u.id}">View</button>
                </td>
              </tr>`
            );
          });
        }, 'json');
      });

      // Reset search on input clear
      $('#userSearch').on('input', function() {
        if (!this.value) {
          $('#userSearchBtn').click();
        }
      });

      // View User Modal (centered alert style)
      $(document).on('click', '.view-user', function() {
        var userId = $(this).data('id');
        var users = <?php echo json_encode($users); ?>;
        var user = users.find(u => u.id == userId);
        var html = '';
        if (user) {
          html = `
            <div style="margin-bottom:0.7rem;"><strong>Name:</strong> ${user.full_name}</div>
            <div style="margin-bottom:0.7rem;"><strong>Email:</strong> ${user.email}</div>
            <div style="margin-bottom:0.7rem;"><strong>Phone:</strong> ${user.phone}</div>
            <div style="margin-bottom:0.7rem;"><strong>Joined:</strong> ${user.created_at}</div>
            <div style="margin-bottom:0.7rem;"><strong>Status:</strong> ${user.status}</div>
            <div style="margin-bottom:0.7rem;"><strong>Wallet Balance:</strong> ₦${parseFloat(user.wallet_balance).toLocaleString()}</div>
          `;
        } else {
          html = '<div>User not found.</div>';
        }
        $('#userDetailContent').html(html);
        $('#userDetailModal').addClass('active');
      });
      // Close modal on close button or OK
      $('#userDetailModal').on('click', '.close-modal', function() {
        $('#userDetailModal').removeClass('active');
      });
      // Close modal on outside click
      $('#userDetailModal').on('click', function(e) {
        if (e.target === this) $(this).removeClass('active');
      });

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

      // Dashboard charts (from DB)
      var userGrowthLabels = <?php echo json_encode($userGrowthLabels); ?>;
      var userGrowthData = <?php echo json_encode($userGrowthData); ?>;
      var ctxUser = document.getElementById('userGrowthChart').getContext('2d');
      var userGrowthChart = new Chart(ctxUser, {
        type: 'line',
        data: {
          labels: userGrowthLabels,
          datasets: [{
            label: 'New Users',
            data: userGrowthData,
            backgroundColor: 'rgba(6, 124, 60, 0.12)',
            borderColor: '#067c3c',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#067c3c'
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#f0f0f0' } }
          }
        }
      });

      var revenueLabels = <?php echo json_encode($revenueLabels); ?>;
      var revenueData = <?php echo json_encode($revenueData); ?>;
      var ctxRevenue = document.getElementById('revenueChart').getContext('2d');
      var revenueChart = new Chart(ctxRevenue, {
        type: 'doughnut',
        data: {
          labels: revenueLabels.length ? revenueLabels : ['No Data'],
          datasets: [{
            data: revenueData.length ? revenueData : [1],
            backgroundColor: [
              '#067c3c', '#1a6b54', '#e0f2ec', '#f5b041', '#d32f2f', '#888'
            ],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' }
          },
          cutout: '65%'
        }
      });
    });
  </script>
  <!-- API Keys Section JS -->
  <script>
  $(document).ready(function() {
    // Show API Keys section on tab click
    $('.admin-tab-btn[data-section="apiKeysSection"]').on('click', function() {
      $('.admin-section').removeClass('active');
      $('#apiKeysSection').addClass('active');
    });

    // Edit API Key modal logic
    $(document).on('click', '.edit-api-key', function() {
      var key = $(this).data('key');
      var value = $(this).data('value');
      $('#editApiKeyName').val(key);
      $('#editApiKeyValue').val(value);
      $('#editApiKeyModal').show();
    });
    $('#editApiKeyModal .close-modal').on('click', function() {
      $('#editApiKeyModal').hide();
    });
    $('#editApiKeyModal').on('click', function(e) {
      if (e.target === this) $(this).hide();
    });

    // Edit API Key AJAX
    $('#editApiKeyForm').on('submit', function(e) {
      e.preventDefault();
      var key = $('#editApiKeyName').val();
      var value = $('#editApiKeyValue').val();
      $.post('admin.php', {action: 'update_api_key', key: key, value: value}, function(res) {
        if (res.success) {
          alert('API key updated.');
          location.reload();
        } else {
          alert(res.message || 'Failed to update API key.');
        }
      }, 'json');
    });
  });
  </script>
</body>
</html>
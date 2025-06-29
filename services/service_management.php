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
  <div class="container" style="max-width:1200px;margin:2rem auto;">
    <h2 style="color:#067c3c;">Service Management</h2>
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
    <style>
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
      #bvnRequestDetailModal .modal-header h3 {
        font-size: 1.1rem;
        color: #1a6b54;
        margin: 0;
      }
      #bvnRequestDetailModal .close-modal {
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
    </style>
  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script>
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
      $(document).on('click', '.view-bvn-request', function() {
        const reqId = $(this).data('id');
        const req = bvnRequests.find(r => r.id == reqId);
        let html = '';
        if (req) {
          html += `<div style="margin-bottom:0.7rem;"><strong>User:</strong> ${req.full_name}</div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Type:</strong> ${req.modification_type}</div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Status:</strong> ${req.status}</div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Date:</strong> ${req.created_at}</div>`;
          html += `<div style="margin-bottom:0.7rem;"><strong>Details:</strong><br>`;
          for (const k in req.details) {
            html += `<strong>${k}:</strong> ${req.details[k]}<br>`;
          }
          html += `</div>`;
          if (req.support_doc) {
            html += `<div style="margin-bottom:0.7rem;"><strong>Document:</strong> <a href="../${req.support_doc}" target="_blank">View</a></div>`;
          }
        } else {
          html = '<div>Request not found.</div>';
        }
        $('#bvnRequestDetailContent').html(html);
        $('#bvnRequestDetailModal').addClass('active');
      });
      $('#bvnRequestDetailModal').on('click', '.close-modal', function() {
        $('#bvnRequestDetailModal').removeClass('active');
      });
      $('#bvnRequestDetailModal').on('click', function(e) {
        if (e.target === this) $(this).removeClass('active');
      });
    });
  </script>
  
</body>
</html>

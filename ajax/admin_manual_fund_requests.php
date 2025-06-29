<?php
// Disable error display and reporting for clean JSON output
ini_set('display_errors', 0);
error_reporting(0);

// Place this at the VERY TOP of the file, before any whitespace or PHP code:
if (php_sapi_name() !== 'cli') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json');
}

// Move all output logic to the very top to prevent any accidental whitespace or error output
ob_start();

require_once('../../config/db.php');
session_start();

// --- FIX: Use admin_id session for admin privilege check ---
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$conn = getDbConnection();

// Handle different actions
$response = ['success' => false, 'message' => 'Invalid action'];

if (isset($_GET['action']) && $_GET['action'] === 'get_requests') {
    // Return all pending requests
    $sql = "SELECT m.id, u.full_name, m.amount, m.bank, m.sender, m.proof, m.status, m.created_at
           FROM manual_fund_requests m
           LEFT JOIN users u ON m.user_id = u.id
           WHERE m.status = 'pending'
           ORDER BY m.created_at DESC";
    
    $result = $conn->query($sql);
    $requests = [];
    
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    $response = ['success' => true, 'requests' => $requests];
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';
    $adminId = $_SESSION['admin_id'];

    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        $response = ['success' => false, 'message' => 'Invalid action'];
    } else {
        $conn->begin_transaction();
        try {
            // 1. Get the request details
            $stmt = $conn->prepare("SELECT * FROM manual_fund_requests WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();

            if (!$request) {
                throw new Exception("Request not found");
            }

            // 2. Update request status
            $updateStmt = $conn->prepare("UPDATE manual_fund_requests SET status = ?, admin_note = ? WHERE id = ?");
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $updateStmt->bind_param('ssi', $newStatus, $note, $id);
            $updateStmt->execute();

            // 3. If approved, update user balance and record transaction
            if ($action === 'approve') {
                $balanceStmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $balanceStmt->bind_param('di', $request['amount'], $request['user_id']);
                $balanceStmt->execute();

                $txnStmt = $conn->prepare("
                    INSERT INTO transactions 
                    (user_id, type, amount, description, status, reference)
                    VALUES (?, 'credit', ?, 'Manual wallet funding', 'completed', ?)
                ");
                $reference = 'MF-' . time() . '-' . $request['user_id'];
                $txnStmt->bind_param('ids', $request['user_id'], $request['amount'], $reference);
                $txnStmt->execute();
            }

            // 4. Log admin action (audit trail)
            $auditStmt = $conn->prepare("
                INSERT INTO admin_audit_log (admin_id, action_type, target_id, target_table, details, created_at)
                VALUES (?, ?, ?, 'manual_fund_requests', ?, NOW())
            ");
            $details = "Action: $action, Note: $note";
            $auditStmt->bind_param('isis', $adminId, $action, $id, $details);
            $auditStmt->execute();

            // 5. (Optional) Send notification to user (placeholder)
            // send_user_notification($request['user_id'], $action, $newStatus);

            $conn->commit();
            $response = ['success' => true, 'message' => "Request {$newStatus} successfully"];
        } catch (Exception $e) {
            $conn->rollback();
            $response = ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

$conn->close();

// Output JSON and exit
echo json_encode($response);
exit;
?>
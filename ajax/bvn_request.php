<?php
session_start();
require_once('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bvn_request') {
    $conn = getDbConnection();
    $user_id = $_SESSION['user_id'];
    $request_type = $_POST['request_type'];
    $bvn = trim($_POST['bvn'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'wallet';
    $amount = in_array($request_type, ['verify', 'retrieve']) ? 500.00 : 0.00;

    // Validate input
    if (in_array($request_type, ['verify', 'update']) && (!preg_match('/^\d{11}$/', $bvn))) {
        echo json_encode(['success' => false, 'message' => 'Invalid BVN. Must be 11 digits.']);
        exit();
    }
    if ($request_type === 'update' && empty($details)) {
        echo json_encode(['success' => false, 'message' => 'Details are required for update requests.']);
        exit();
    }

    // Check wallet balance for wallet payment
    if ($payment_method === 'wallet' && in_array($request_type, ['verify', 'retrieve'])) {
        $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($wallet_balance);
        $stmt->fetch();
        $stmt->close();
        if ($wallet_balance < $amount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance.']);
            exit();
        }
    }

    // Generate Monnify reference for card/USSD payments
    $monnify_reference = $payment_method === 'card' ? "bvn_{$user_id}_" . time() : null;

    // Insert BVN request
    $stmt = $conn->prepare("INSERT INTO bvn_requests (user_id, request_type, bvn, amount, status, monnify_reference, details) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    $stmt->bind_param("issdss", $user_id, $request_type, $bvn, $amount, $monnify_reference, $details);
    $success = $stmt->execute();
    $request_id = $conn->insert_id;

    // Update wallet balance and log transaction if wallet payment
    if ($success && $payment_method === 'wallet' && $amount > 0) {
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();
        $stmt->close();

        $txn_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, reference, description) VALUES (?, 'debit', ?, 'completed', ?, ?)");
        $txn_reference = "txn_bvn_{$request_id}";
        $description = "BVN {$request_type} fee";
        $txn_stmt->bind_param("idss", $user_id, $amount, $txn_reference, $description);
        $txn_stmt->execute();
        $txn_stmt->close();
    }

    $conn->close();
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'BVN request submitted successfully.' : 'Failed to submit request.'
    ]);
}
?>
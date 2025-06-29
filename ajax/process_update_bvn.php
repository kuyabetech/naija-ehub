<?php
session_start();
require_once('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_bvn') {
    $conn = getDbConnection();
    $user_id = $_SESSION['user_id'];
    $update_type = $_POST['update_type'] ?? 'cafe'; // cafe or bank
    $modification_type = $_POST['modification_type'] ?? '';
    $bvn = trim($_POST['bvn'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'wallet';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if ($csrf_token !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    // Validate inputs
    if (!in_array($modification_type, ['change_of_name', 'date_of_birth', 'change_of_address', 'change_of_phone', 'rearrangement_of_name'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid modification type']);
        exit();
    }
    if (!preg_match('/^\d{11}$/', $bvn)) {
        echo json_encode(['success' => false, 'message' => 'Invalid BVN. Must be 11 digits.']);
        exit();
    }

    // Define prices
    $prices = [
        'change_of_name' => 2000.00,
        'date_of_birth' => 2500.00,
        'change_of_address' => 1500.00,
        'change_of_phone' => 1000.00,
        'rearrangement_of_name' => 2000.00
    ];
    $amount = $prices[$modification_type];

    // Collect modification details
    $details = [];
    switch ($modification_type) {
        case 'change_of_name':
            $details = ['old_name' => trim($_POST['old_name'] ?? ''), 'new_name' => trim($_POST['new_name'] ?? '')];
            if (empty($details['old_name']) || empty($details['new_name'])) {
                echo json_encode(['success' => false, 'message' => 'Old and new names are required']);
                exit();
            }
            break;
        case 'date_of_birth':
            $details = ['current_dob' => trim($_POST['current_dob'] ?? ''), 'correct_dob' => trim($_POST['correct_dob'] ?? '')];
            if (empty($details['current_dob']) || empty($details['correct_dob'])) {
                echo json_encode(['success' => false, 'message' => 'Current and correct dates of birth are required']);
                exit();
            }
            break;
        case 'change_of_address':
            $details = ['old_address' => trim($_POST['old_address'] ?? ''), 'new_address' => trim($_POST['new_address'] ?? '')];
            if (empty($details['old_address']) || empty($details['new_address'])) {
                echo json_encode(['success' => false, 'message' => 'Old and new addresses are required']);
                exit();
            }
            break;
        case 'change_of_phone':
            $details = ['old_phone' => trim($_POST['old_phone'] ?? ''), 'new_phone' => trim($_POST['new_phone'] ?? '')];
            if (empty($details['old_phone']) || empty($details['new_phone'])) {
                echo json_encode(['success' => false, 'message' => 'Old and new phone numbers are required']);
                exit();
            }
            break;
        case 'rearrangement_of_name':
            $details = ['current_arrangement' => trim($_POST['current_arrangement'] ?? ''), 'desired_arrangement' => trim($_POST['desired_arrangement'] ?? '')];
            if (empty($details['current_arrangement']) || empty($details['desired_arrangement'])) {
                echo json_encode(['success' => false, 'message' => 'Current and desired name arrangements are required']);
                exit();
            }
            break;
    }

    // Handle file upload
    $support_doc = '';
    if (in_array($modification_type, ['change_of_name', 'date_of_birth', 'change_of_address', 'rearrangement_of_name'])) {
        if (isset($_FILES['support_doc']) && $_FILES['support_doc']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['support_doc']['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'pdf'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Use JPG, PNG, or PDF.']);
                exit();
            }
            if ($_FILES['support_doc']['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
                exit();
            }
            $support_doc = 'Uploads/bvn_docs/' . $user_id . '_' . time() . '.' . $ext;
            if (!is_dir('Uploads/bvn_docs')) {
                mkdir('Uploads/bvn_docs', 0755, true);
            }
            move_uploaded_file($_FILES['support_doc']['tmp_name'], $support_doc);
        } else {
            echo json_encode(['success' => false, 'message' => 'Supporting document is required']);
            exit();
        }
    }

    // Check wallet balance for wallet payment
    if ($payment_method === 'wallet') {
        $stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($wallet_balance);
        $stmt->fetch();
        $stmt->close();
        if ($wallet_balance < $amount) {
            echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance']);
            exit();
        }
    }

    // Generate Monnify reference for card/USSD payments
    $monnify_reference = $payment_method === 'card' ? "bvn_update_{$user_id}_" . time() : null;

    // Insert BVN update request
    $details_json = json_encode(['update_type' => $update_type, 'modification_type' => $modification_type, 'details' => $details]);
    $stmt = $conn->prepare("INSERT INTO bvn_requests (user_id, request_type, bvn, amount, status, monnify_reference, details, support_doc) VALUES (?, 'update', ?, ?, 'pending', ?, ?, ?)");
    $stmt->bind_param("isdsss", $user_id, $bvn, $amount, $monnify_reference, $details_json, $support_doc);
    $success = $stmt->execute();
    $request_id = $conn->insert_id;
    $stmt->close();

    // Update wallet balance and log transaction if wallet payment
    if ($success && $payment_method === 'wallet') {
        $stmt = $conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();
        $stmt->close();

        $txn_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, status, reference, description) VALUES (?, 'debit', ?, 'completed', ?, ?)");
        $txn_reference = "txn_bvn_update_{$request_id}";
        $description = "BVN Update - {$modification_type}";
        $txn_stmt->bind_param("idss", $user_id, $amount, $txn_reference, $description);
        $txn_stmt->execute();
        $txn_stmt->close();
    }

    $conn->close();
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'BVN update request submitted successfully' : 'Failed to submit request'
    ]);
}
?>
<?php
session_start();
require_once('../config/db.php');

// Redirect if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Get user info and wallet balance
$stmt = $conn->prepare("SELECT full_name, wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $wallet_balance);
$stmt->fetch();
$stmt->close();

// Get POST data
$modification_type = $_POST['modification_type'] ?? '';
$price_map = [
    'change_of_name' => 2000,
    'date_of_birth' => 2500,
    'change_of_address' => 1500,
    'change_of_phone' => 1000,
    'rearrangement_of_name' => 2000
];
$amount = $price_map[$modification_type] ?? 0;

// Validate
if (!$modification_type || $amount == 0) {
    $_SESSION['bvn_update_error'] = "Invalid modification type.";
    header('Location: update_bvn.php');
    exit();
}

// Check wallet balance
if ($wallet_balance < $amount) {
    echo "<script>
      alert('Insufficient wallet balance. Please fund your wallet.');
      window.location.href = '../fund_wallet.php';
    </script>";
    exit();
}

// Handle file upload if required
$support_doc_path = null;
if (isset($_FILES['support_doc']) && $_FILES['support_doc']['error'] == UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['support_doc']['name'], PATHINFO_EXTENSION);
    $target_dir = "../uploads/bvn_docs/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $filename = "bvn_" . $user_id . "_" . time() . "." . $ext;
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($_FILES['support_doc']['tmp_name'], $target_file)) {
        $support_doc_path = "uploads/bvn_docs/" . $filename;
    }
}

// Save request in DB
$fields = [
    'change_of_name' => ['old_name', 'new_name'],
    'date_of_birth' => ['current_dob', 'correct_dob'],
    'change_of_address' => ['old_address', 'new_address'],
    'change_of_phone' => ['old_phone', 'new_phone'],
    'rearrangement_of_name' => ['current_arrangement', 'desired_arrangement']
];
$field_values = [];
foreach ($fields[$modification_type] as $field) {
    $field_values[$field] = $_POST[$field] ?? '';
}

// Insert into bvn_update_requests table
$stmt = $conn->prepare("INSERT INTO bvn_update_requests 
    (user_id, modification_type, details, support_doc, status, created_at) 
    VALUES (?, ?, ?, ?, 'pending', NOW())");
$details = json_encode($field_values);
$stmt->bind_param("isss", $user_id, $modification_type, $details, $support_doc_path);
$stmt->execute();
$stmt->close();

// Deduct wallet and record transaction
$new_balance = $wallet_balance - $amount;
$stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
$stmt->bind_param("di", $new_balance, $user_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, created_at) VALUES (?, 'debit', ?, 'BVN Update ($modification_type)', 'completed', NOW())");
$stmt->bind_param("id", $user_id, $amount);
$stmt->execute();
$stmt->close();

$conn->close();

$_SESSION['bvn_update_success'] = "Your BVN update request has been submitted. Our team will process it shortly.";

// Redirect back with a success flag for popup
header('Location: update_bvn.php?success=1');
exit();

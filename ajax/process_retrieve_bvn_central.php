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

// Service price
$amount = 1000;

// Validate
if ($wallet_balance < $amount) {
    echo "<script>
      alert('Insufficient wallet balance. Please fund your wallet.');
      window.location.href = '../fund_wallet.php';
    </script>";
    exit();
}

// Handle file upload
$means_of_id_path = null;
if (isset($_FILES['means_of_id']) && $_FILES['means_of_id']['error'] == UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['means_of_id']['name'], PATHINFO_EXTENSION);
    $target_dir = "../uploads/bvn_docs/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $filename = "bvn_id_" . $user_id . "_" . time() . "." . $ext;
    $target_file = $target_dir . $filename;
    if (move_uploaded_file($_FILES['means_of_id']['tmp_name'], $target_file)) {
        $means_of_id_path = "uploads/bvn_docs/" . $filename;
    }
}

// Save request in DB (table: bvn_retrieve_requests)
$details = [
    'agent_code' => $_POST['agent_code'] ?? '',
    'bmst_code' => $_POST['bmst_code'] ?? '',
    'ticket_id' => $_POST['ticket_id'] ?? '',
    'agent_name' => $_POST['agent_name'] ?? ''
];
$stmt = $conn->prepare("INSERT INTO bvn_retrieve_requests 
    (user_id, method, details, means_of_id, status, created_at) 
    VALUES (?, 'central_risk', ?, ?, 'pending', NOW())");
$details_json = json_encode($details);
$stmt->bind_param("iss", $user_id, $details_json, $means_of_id_path);
$stmt->execute();
$stmt->close();

// Deduct wallet and record transaction
$new_balance = $wallet_balance - $amount;
$stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
$stmt->bind_param("di", $new_balance, $user_id);
$stmt->execute();
$stmt->close();

$reference = 'BVNRET-' . $user_id . '-' . time() . '-' . bin2hex(random_bytes(4));
$description = "BVN Retrieve (Central Risk)";
$stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, reference, created_at) VALUES (?, 'debit', ?, ?, 'completed', ?, NOW())");
$stmt->bind_param("idss", $user_id, $amount, $description, $reference);
$stmt->execute();
$stmt->close();

$conn->close();

$_SESSION['bvn_retrieve_success'] = "Your BVN retrieval request has been submitted. Our team will process it shortly.";
header('Location: retrieve_bvn.php?success=1');
exit();

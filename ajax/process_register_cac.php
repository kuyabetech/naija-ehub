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

// Set CAC registration fee (₦20,000)
$amount = 20000;

// Check wallet balance
if ($wallet_balance < $amount) {
    echo "<script>
      alert('Insufficient wallet balance. Please fund your wallet.');
      window.location.href = '../fund_wallet.php';
    </script>";
    exit();
}

// Handle file uploads
function handle_upload($input_name, $user_id) {
    if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
        $target_dir = "../uploads/cac_docs/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = $input_name . "_" . $user_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $target_file)) {
            return "uploads/cac_docs/" . $filename;
        }
    }
    return null;
}
$id_card_path = handle_upload('id_card', $user_id);
$passport_path = handle_upload('passport', $user_id);
$signature_path = handle_upload('signature', $user_id);

// Collect form data
$data = [
    'surname' => $_POST['surname'] ?? '',
    'first_name' => $_POST['first_name'] ?? '',
    'other_name' => $_POST['other_name'] ?? '',
    'dob' => $_POST['dob'] ?? '',
    'gender' => $_POST['gender'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'home_state' => $_POST['home_state'] ?? '',
    'home_lga' => $_POST['home_lga'] ?? '',
    'home_city' => $_POST['home_city'] ?? '',
    'home_house_number' => $_POST['home_house_number'] ?? '',
    'home_street' => $_POST['home_street'] ?? '',
    'biz_state' => $_POST['biz_state'] ?? '',
    'biz_lga' => $_POST['biz_lga'] ?? '',
    'biz_city' => $_POST['biz_city'] ?? '',
    'biz_house_number' => $_POST['biz_house_number'] ?? '',
    'biz_street' => $_POST['biz_street'] ?? '',
    'nature_of_business' => $_POST['nature_of_business'] ?? '',
    'business_name1' => $_POST['business_name1'] ?? '',
    'business_name2' => $_POST['business_name2'] ?? '',
    'email' => $_POST['email'] ?? ''
];

// Save request in DB
$stmt = $conn->prepare("INSERT INTO cac_registration_requests 
    (user_id, details, id_card, passport, signature, status, created_at) 
    VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
$details = json_encode($data);
$stmt->bind_param("issss", $user_id, $details, $id_card_path, $passport_path, $signature_path);
$stmt->execute();
$stmt->close();

// Deduct wallet and record transaction
$new_balance = $wallet_balance - $amount;
$stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
$stmt->bind_param("di", $new_balance, $user_id);
$stmt->execute();
$stmt->close();

$reference = 'CAC-' . $user_id . '-' . time() . '-' . bin2hex(random_bytes(4));
$description = "CAC Registration";
$stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, reference, created_at) VALUES (?, 'debit', ?, ?, 'completed', ?, NOW())");
$stmt->bind_param("idss", $user_id, $amount, $description, $reference);
$stmt->execute();
$stmt->close();

$conn->close();

$_SESSION['cac_register_success'] = "Your CAC registration request has been submitted. Our team will process it shortly.";
header('Location: register_cac.php?success=1');
exit();

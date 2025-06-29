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
      showWalletPopup();
      function showWalletPopup() {
        let popup = document.createElement('div');
        popup.style.position = 'fixed';
        popup.style.top = '30px';
        popup.style.left = '50%';
        popup.style.transform = 'translateX(-50%)';
        popup.style.background = '#d32f2f';
        popup.style.color = '#fff';
        popup.style.padding = '1rem 2.2rem';
        popup.style.borderRadius = '8px';
        popup.style.boxShadow = '0 4px 24px rgba(0,0,0,0.13)';
        popup.style.fontSize = '1.08rem';
        popup.style.zIndex = '99999';
        popup.style.textAlign = 'center';
        popup.innerHTML = `<span style='margin-right:0.7rem;'><i class='fas fa-wallet'></i></span>Insufficient wallet balance. Please fund your wallet.`;
        document.body.appendChild(popup);
        setTimeout(() => {
          popup.style.transition = 'opacity 0.4s';
          popup.style.opacity = '0';
          setTimeout(() => { popup.remove(); window.location.href = '../fund_wallet.php'; }, 400);
        }, 3200);
      }
    </script>";
    exit();
}

// Save request in DB (table: bvn_retrieve_requests)
$details = [
    'full_name' => $_POST['full_name'] ?? '',
    'dob' => $_POST['dob'] ?? '',
    'phone' => $_POST['phone'] ?? ''
];
$stmt = $conn->prepare("INSERT INTO bvn_retrieve_requests 
    (user_id, method, details, status, created_at) 
    VALUES (?, 'phone', ?, 'pending', NOW())");
$details_json = json_encode($details);
$stmt->bind_param("is", $user_id, $details_json);
$stmt->execute();
$stmt->close();

// Deduct wallet and record transaction
$new_balance = $wallet_balance - $amount;
$stmt = $conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
$stmt->bind_param("di", $new_balance, $user_id);
$stmt->execute();
$stmt->close();

$reference = 'BVNRET-' . $user_id . '-' . time() . '-' . bin2hex(random_bytes(4));
$description = "BVN Retrieve (Phone)";
$stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, reference, created_at) VALUES (?, 'debit', ?, ?, 'completed', ?, NOW())");
$stmt->bind_param("idss", $user_id, $amount, $description, $reference);
$stmt->execute();
$stmt->close();

$conn->close();

$_SESSION['bvn_retrieve_success'] = "Your BVN retrieval request has been submitted. Our team will process it shortly.";
header('Location: retrieve_bvn.php?success=1');
exit();

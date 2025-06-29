<?php
// Manual Fund PHP handler (AJAX)
// This file should be called via AJAX POST from the manual funding form
session_start();
require_once('../config/db.php');

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? 0;
$amount = intval($_POST['manualAmount'] ?? 0);
$bank = trim($_POST['manualBank'] ?? '');
$sender = trim($_POST['manualSender'] ?? '');
$response = ['success' => false];

if ($user_id && $amount > 0 && $bank && $sender && isset($_FILES['manualProof']) && $_FILES['manualProof']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = dirname(__DIR__) . '/uploads/manual_fund/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $ext = strtolower(pathinfo($_FILES['manualProof']['name'], PATHINFO_EXTENSION));
    // Only allow image and pdf
    $allowed = ['jpg','jpeg','png','gif','pdf'];
    if (!in_array($ext, $allowed)) {
        $response['message'] = 'Invalid file type.';
        echo json_encode($response);
        exit;
    }
    $filename = 'manual_' . $user_id . '_' . time() . '.' . $ext;
    $target = $uploadDir . $filename;
    $proofPath = 'uploads/manual_fund/' . $filename;
    if (move_uploaded_file($_FILES['manualProof']['tmp_name'], $target)) {
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO manual_fund_requests (user_id, amount, bank, sender, proof, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        if (!$stmt) {
            $response['message'] = 'Database error: ' . $conn->error;
            echo json_encode($response);
            $conn->close();
            exit;
        }
        $stmt->bind_param("iisss", $user_id, $amount, $bank, $sender, $proofPath);
        $ok = $stmt->execute();
        if (!$ok) {
            $response['message'] = 'Database error: ' . $stmt->error;
        } else {
            $response['success'] = true;
        }
        $stmt->close();
        $conn->close();
    } else {
        $response['message'] = 'Failed to upload file.';
    }
} else {
    $response['message'] = 'Invalid input or file.';
}
echo json_encode($response);
exit;

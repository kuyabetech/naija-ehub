<?php
session_start();
require_once('../config/db.php');
$user_id = $_SESSION['user_id'] ?? 0;
$out = [];
if ($user_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT amount, status, created_at FROM manul_fund_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($amount, $status, $created_at);
    while ($stmt->fetch()) {
        $out[] = [
            'amount' => $amount,
            'status' => $status,
            'created_at' => $created_at
        ];
    }
    $stmt->close();
    $conn->close();
}
header('Content-Type: application/json');
echo json_encode($out);

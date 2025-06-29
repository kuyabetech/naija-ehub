<?php
header('Content-Type: application/json');
require_once('../config/db.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get DB connection
$conn = getDbConnection();
$user_id = $_SESSION['user_id'];

// Get filter parameters
$search = isset($_POST['search']) ? '%' . $_POST['search'] . '%' : '%';
$type = isset($_POST['type']) && $_POST['type'] !== 'all' ? $_POST['type'] : null;
$status = isset($_POST['status']) && $_POST['status'] !== 'all' ? $_POST['status'] : null;
$dateStart = isset($_POST['dateStart']) ? $_POST['dateStart'] : null;
$dateEnd = isset($_POST['dateEnd']) ? $_POST['dateEnd'] : null;
$page = isset($_POST['page']) && is_numeric($_POST['page']) ? (int)$_POST['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$query = "SELECT id, type, amount, description, status, reference, created_at 
          FROM transactions 
          WHERE user_id = ? AND (description LIKE ? OR reference LIKE ?)";
$params = [$user_id, $search, $search];
$types = "iss";

if ($type) {
    $query .= " AND type = ?";
    $params[] = $type;
    $types .= "s";
}
if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($dateStart) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $dateStart;
    $types .= "s";
}
if ($dateEnd) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $dateEnd;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM transactions WHERE user_id = ? AND (description LIKE ? OR reference LIKE ?)";
$count_params = [$user_id, $search, $search];
$count_types = "iss";

if ($type) {
    $count_query .= " AND type = ?";
    $count_params[] = $type;
    $count_types .= "s";
}
if ($status) {
    $count_query .= " AND status = ?";
    $count_params[] = $status;
    $count_types .= "s";
}
if ($dateStart) {
    $count_query .= " AND DATE(created_at) >= ?";
    $count_params[] = $dateStart;
    $count_types .= "s";
}
if ($dateEnd) {
    $count_query .= " AND DATE(created_at) <= ?";
    $count_params[] = $dateEnd;
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_query);
$conn->bind_param($count_stmt, $count_types, ...$count_params);
$count_stmt->execute();
$count_stmt->bind_result($totalTxns);
$count_stmt->fetch();
$count_stmt->close();

$totalPages = ceil($totalTxns / $perPage);

// Fetch transactions
$stmt = $conn->prepare($query);
$conn->bind_param($stmt, $types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $row['amount'] = number_format($row['amount'], 2);
    $row['status_display'] = in_array($row['status'], ['completed', 'success']) ? 'Success' : ($row['status'] === 'pending' ? 'Pending' : 'Failed');
    $transactions[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode([
    'transactions' => $transactions,
    'totalPages' => $totalPages,
    'currentPage' => $page
]);

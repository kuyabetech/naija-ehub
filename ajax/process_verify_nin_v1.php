<?php
session_start();
require_once('../config/db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $result = ['success' => false, 'message' => 'Invalid CSRF token'];
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// Rate limiting
$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$rate_limit_key = 'nin_verify_' . $user_id;
$rate_limit_count = apcu_fetch($rate_limit_key) ?: 0;
$rate_limit_max = 5;
$rate_limit_window = 60; // 1 minute

if ($rate_limit_count >= $rate_limit_max) {
    $result = ['success' => false, 'message' => 'Rate limit exceeded. Please wait a minute before trying again.'];
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// Increment rate limit counter
apcu_store($rate_limit_key, $rate_limit_count + 1, $rate_limit_window);

// Replace with real NIMC API credentials and endpoint
define('NIMC_API_URL', 'https://api.nimc.gov.ng/v1');
define('NIMC_API_PHONE_URL', 'https://api.nimc.gov.ng/v1');
define('NIMC_API_KEY', 'lv_imSmart_6v99f3zto71p1e4sn8q6rmc0j5xg8h15');
define('NIMC_API_SECRET', 'YOUR_NIMC_API_SECRET');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($_POST['nin']) || !empty($_POST['phone']))) {
    $nin = isset($_POST['nin']) ? preg_replace('/[^0-9]/', '', trim($_POST['nin'])) : '';
    $phone = isset($_POST['phone']) ? preg_replace('/[^0-9+]/', '', trim($_POST['phone'])) : '';

    // Validate input
    if ($nin && !preg_match('/^\d{11}$/', $nin)) {
        $result = ['success' => false, 'message' => 'Invalid NIN: Must be 11 digits'];
    } elseif ($phone && !preg_match('/^(\+234|0)[789][0-1]\d{8}$/', $phone)) {
        $result = ['success' => false, 'message' => 'Invalid phone number: Must be a valid Nigerian number'];
    } else {
        $payload = [];
        $api_url = '';
        if ($nin && !$phone) {
            $payload = ['nin' => $nin];
            $api_url = NIMC_API_URL;
        } elseif ($phone && !$nin) {
            $payload = ['phone' => $phone];
            $api_url = NIMC_API_PHONE_URL;
        }

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . NIMC_API_KEY,
            'x-api-secret: ' . NIMC_API_SECRET
        ];

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        // Log the verification attempt
        $input_data = $nin ? "NIN: $nin" : "Phone: $phone";
        $status = 'pending';
        $result_data = $response;
        if ($err) {
            $status = 'failed';
            $result = ['success' => false, 'message' => 'API Error: ' . $err];
        } else {
            $data = json_decode($response, true);
            if ($http_code === 200 && isset($data['success']) && $data['success']) {
                $status = 'success';
                $result = ['success' => true, 'data' => $data['data']];
            } else {
                $status = 'failed';
                $result = ['success' => false, 'message' => $data['message'] ?? 'Verification failed. Error code: ' . $http_code];
            }
        }

        // Store in database
        $stmt = $conn->prepare("INSERT INTO nin_verifications (user_id, version, input_data, status, result, created_at) VALUES (?, 'ver1', ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $user_id, $input_data, $status, $result_data);
        $stmt->execute();
        $stmt->close();
    }
} else {
    $result = ['success' => false, 'message' => 'Invalid request: NIN or phone number required'];
}

// Fetch verification history
$history = [];
$history_stmt = $conn->prepare("SELECT id, version, input_data, status, result, created_at FROM nin_verifications WHERE user_id = ? AND version = 'ver1' ORDER BY created_at DESC LIMIT 5");
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$result_history = $history_stmt->get_result();
while ($row = $result_history->fetch_assoc()) {
    $history[] = $row;
}
$history_stmt->close();
$conn->close();

// Generate CSRF token for the form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Naija eHub NIN Verification Result - View and download your NIN verification results.">
    <title>NIN Verification Result | Naija eHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>
            <section class="service-header">
                <div class="header-content">
                    <h2>NIN Verification Result</h2>
                    <p>View and manage your NIN verification details</p>
                </div>
                <div class="service-icon">
                    <i class="fas fa-id-card"></i>
                </div>
            </section>

            <section class="result-section">
                <div class="result-card">
                    <?php if ($result['success']): ?>
                        <h3>Verification Successful</h3>
                        <pre class="result-data"><?php echo htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT)); ?></pre>
                        <form method="post" action="download_nin_slip.php" target="_blank" class="download-form" role="form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="nin" value="<?php echo htmlspecialchars($nin ?? ''); ?>">
                            <label for="slipType">Download NIN Slip as:</label>
                            <select name="slip_type" id="slipType" required>
                                <option value="premium">Premium Slip</option>
                                <option value="improved">Improved Slip</option>
                                <option value="basic">Basic Slip</option>
                                <option value="standard">Standard Slip</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Download</button>
                            <button type="button" class="btn btn-outline" id="previewSlip">Preview</button>
                        </form>
                    <?php else: ?>
                        <h3>Verification Failed</h3>
                        <p class="error-message"><?php echo htmlspecialchars($result['message']); ?></p>
                    <?php endif; ?>
                    <a href="verify_nin.php" class="btn btn-outline">Back to NIN Verification</a>
                </div>

                <!-- Verification History -->
                <div class="verification-history">
                    <h3>Recent Verifications</h3>
                    <div class="table-responsive">
                        <table id="verificationHistoryTable" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Input</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="5" class="no-data">No verification history found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history as $entry): ?>
                                        <tr>
                                            <td>Verification v<?php echo htmlspecialchars($entry['version'][3]); ?></td>
                                            <td><?php echo htmlspecialchars($entry['input_data']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo htmlspecialchars($entry['status']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($entry['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($entry['created_at']); ?></td>
                                            <td>
                                                <button class="btn btn-outline view-verification" 
                                                        data-details='<?php echo json_encode($entry); ?>'>Details</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-primary" id="exportHistory">Export History</button>
                </div>
            </section>
        </main>
    </div>

    <!-- Result Modal -->
    <div class="modal" id="resultModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeResultModal()">×</span>
            <h3>Verification Details</h3>
            <div class="modal-body">
                <p><strong>Version:</strong> <span id="resultVersion"></span></p>
                <p><strong>Input:</strong> <span id="resultInput"></span></p>
                <p><strong>Status:</strong> <span id="resultStatus"></span></p>
                <p><strong>Details:</strong> <span id="resultDetails"></span></p>
                <button class="btn btn-primary" id="retryVerification">Retry</button>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePreviewModal()">×</span>
            <h3>NIN Slip Preview</h3>
            <div class="modal-body">
                <iframe id="previewIframe" style="width:100%;height:400px;border:none;"></iframe>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/whatsapp-chat.php'; ?>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php include __DIR__ . '/../includes/spinner.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
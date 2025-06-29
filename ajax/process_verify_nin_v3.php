<?php
// NIN Verification v3: First Name, Last Name, NIN
session_start();
require_once('../config/db.php');

define('NIMC_API_URL', 'https://api.nimc.gov.ng/v1/verify-nin-name');
define('NIMC_API_KEY', 'YOUR_NIMC_API_KEY');
define('NIMC_API_SECRET', 'YOUR_NIMC_API_SECRET');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nin']) && !empty($_POST['first_name']) && !empty($_POST['last_name'])) {
    $nin = trim($_POST['nin']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);

    $payload = [
        'nin' => $nin,
        'first_name' => $first_name,
        'last_name' => $last_name
    ];
    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . NIMC_API_KEY,
        'x-api-secret: ' . NIMC_API_SECRET
    ];

    $ch = curl_init(NIMC_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO nin_verification_logs (user_id, nin, api_response, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $_SESSION['user_id'], $nin, $response);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    if ($err) {
        $result = ['success' => false, 'message' => 'API Error: ' . $err];
    } else {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success']) {
            $result = ['success' => true, 'data' => $data['data']];
        } else {
            $result = ['success' => false, 'message' => $data['message'] ?? 'Verification failed'];
        }
    }
} else {
    $result = ['success' => false, 'message' => 'Invalid request'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NIN Verification Result</title>
    <link rel="stylesheet" href="../css/nin.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<div class="dashboard-container">
    <main class="main-content">
        <section class="service-header">
            <div class="header-content">
                <h2>NIN Verification Result</h2>
            </div>
        </section>
        <div style="background:#fff;padding:2rem;border-radius:10px;max-width:500px;margin:2rem auto;">
            <?php if ($result['success']): ?>
                <h3 style="color:#067c3c;">Verification Successful</h3>
                <pre style="background:#f5f5f5;padding:1rem;border-radius:7px;"><?php echo htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT)); ?></pre>
            <?php else: ?>
                <h3 style="color:#d32f2f;">Verification Failed</h3>
                <p><?php echo htmlspecialchars($result['message']); ?></p>
            <?php endif; ?>
            <a href="verify_nin.php" class="btn btn-outline" style="margin-top:1.5rem;">Back to NIN Verification</a>
        </div>
    </main>
</div>
</body>
</html>

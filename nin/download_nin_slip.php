<?php
// Download NIN slip (dummy implementation, replace with real NIMC API if available)
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}
$nin = $_POST['nin'] ?? '';
$slipType = $_POST['slip_type'] ?? 'premium';

$slipNames = [
    'premium' => 'Premium Slip',
    'improved' => 'Improved Slip',
    'basic' => 'Basic Slip',
    'standard' => 'Standard Slip'
];
$slipLabel = $slipNames[$slipType] ?? 'Premium Slip';

// In real implementation, call NIMC API to generate/download the slip PDF for the NIN and type
// For demo, just output a simple PDF with the slip type and NIN

require_once('../vendor/autoload.php'); // mPDF or similar library

// For local testing without mPDF, output a simple HTML file as a download
if (!file_exists('../vendor/autoload.php')) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="NIN-' . $slipType . '-Slip.html"');
    echo "<!DOCTYPE html>
    <html>
    <head><meta charset='UTF-8'><title>$slipLabel</title></head>
    <body>
        <h2 style='color:#067c3c;'>$slipLabel</h2>
        <p><strong>NIN:</strong> " . htmlspecialchars($nin) . "</p>
        <p>This is a sample $slipLabel for demonstration purposes only.</p>
        <p style='margin-top:2rem;font-size:0.95em;color:#888;'>Generated by Naija eHub</p>
    </body>
    </html>";
    exit;
}

// Check ifstmPDF is available before using it
// if (!class_exists('\Mpdf\Mpdf')) {
//     header('Content-Type: application/octet-stream');
//     header('Content-Disposition: attachment; filename="NIN-' . $slipType . '-Slip.html"');
//     echo "<!DOCTYPE html>
//     <html>
//     <head><meta charset='UTF-8'><title>$slipLabel</title></head>
//     <body>
//         <h2 style='color:#067c3c;'>$slipLabel</h2>
//         <p><strong>NIN:</strong> " . htmlspecialchars($nin) . "</p>
//         <p>This is a sample $slipLabel for demonstration purposes only.</p>
//         <p style='margin-top:2rem;font-size:0.95em;color:#888;'>Generated by Naija eHub</p>
//     </body>
//     </html>";
//     exit;
// }

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML("
    <h2 style='color:#067c3c;'>$slipLabel</h2>
    <p><strong>NIN:</strong> " . htmlspecialchars($nin) . "</p>
    <p>This is a sample $slipLabel for demonstration purposes only.</p>
    <p style='margin-top:2rem;font-size:0.95em;color:#888;'>Generated by Naija eHub</p>
");
$mpdf->Output("NIN-$slipType-Slip.pdf", "D");
exit;

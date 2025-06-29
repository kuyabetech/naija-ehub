<?php
require_once('../config/db.php');
require_once __DIR__ . '/../vendor/autoload.php'; // <-- Make sure this path is correct and file exists
session_start();

// Ensure PHPMailer is installed via Composer and autoloaded
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $message = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address.';
    } else {
        $conn = getDbConnection();
        // Change 'admins' to 'admin_users'
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt->close();
            $stmt = $conn->prepare("UPDATE admin_users SET reset_token=?, reset_token_expires=? WHERE email=?");
            $stmt->bind_param("sss", $token, $expires, $email);
            $stmt->execute();

            // Define $subject and $body before using them
            $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/Verify-app/admin/adminReset.php?token=$token";
            $subject = "Admin Password Reset - Naija eHub";
            $body = '
            <div style="font-family:Segoe UI,Arial,sans-serif;background:#f5f6fa;padding:24px;">
                <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:10px;box-shadow:0 4px 18px rgba(0,0,0,0.07);padding:32px 24px;">
                    <h2 style="color:#067c3c;margin-bottom:18px;font-size:1.3rem;">Admin Password Reset</h2>
                    <p style="color:#222;font-size:1.05rem;margin-bottom:18px;">
                        Hello,<br><br>
                        We received a request to reset your admin password for <b>Naija eHub</b>.
                    </p>
                    <p style="margin-bottom:22px;">
                        <a href="' . $resetLink . '" style="display:inline-block;padding:12px 28px;background:#067c3c;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:1.08rem;">Reset Password</a>
                    </p>
                    <p style="color:#444;font-size:0.98rem;margin-bottom:18px;">
                        Or copy and paste this link in your browser:<br>
                        <span style="color:#067c3c;font-size:0.97rem;word-break:break-all;">' . $resetLink . '</span>
                    </p>
                    <p style="color:#d32f2f;font-size:0.97rem;margin-bottom:18px;">
                        If you did not request this, please ignore this email.<br>
                        This link will expire in 1 hour.
                    </p>
                    <div style="margin-top:22px;color:#888;font-size:0.97rem;">
                        Regards,<br>
                        <b>Naija eHub Team</b>
                    </div>
                </div>
            </div>
            ';

            // Use PHPMailer for live email sending
            // Make sure Composer's vendor/autoload.php is present and correct
            if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $message = 'PHPMailer is not installed or autoloaded. Please run <b>composer require phpmailer/phpmailer</b> in your project root, then reload this page.';
            } else {
                $mail = new PHPMailer(true);
                try {
                    // Gmail SMTP configuration
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'kuyabetech@gmail.com';    // Your Gmail address
                    $mail->Password = 'dlix xtim dhtx biau';      // Your Gmail App Password (not your Gmail login password)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->setFrom('kuyabetech@gmail.com', 'Naija eHub');
                    $mail->addAddress($email);
                    $mail->Subject = $subject;
                    $mail->isHTML(true);
                    $mail->Body = $body;
                    $mail->send();
                    $message = 'A password reset link has been sent to your email.';
                } catch (PHPMailerException $e) {
                    $message = 'Failed to send reset email. Please contact support.';
                }
            }
        } else {
            $message = 'No admin account found with that email.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Forgot Password | Naija eHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
    <div class="auth-container">
        <form class="auth-form" method="post" action="">
            <h2>Admin Forgot Password</h2>
            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" name="email" id="email" required placeholder="Enter your admin email">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Send Reset Link</button>
            <div style="margin-top:1rem;text-align:center;">
                <a href="adminLogin.php">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>

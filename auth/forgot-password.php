<?php
require_once('../config/db.php');
// Add PHPMailer for email sending
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $message = "Please enter your email address.";
    } else {
        $conn = getDbConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in DB (create table if not exists)
            $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");

            $stmt->bind_result($user_id);
            $stmt->fetch();
            $stmt->close();

            // Remove old tokens for this user
            $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();
            $del->close();

            // Insert new token
            $ins = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user_id, $token, $expires);
            $ins->execute();
            $ins->close();

            // Send reset link via email using PHPMailer
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=$token";
            $subject = "Password Reset - Naija eHub";
            $body = '
            <div style="font-family:Segoe UI,Arial,sans-serif;background:#f5f6fa;padding:24px;">
                <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:10px;box-shadow:0 4px 18px rgba(0,0,0,0.07);padding:32px 24px;">
                    <h2 style="color:#067c3c;margin-bottom:18px;font-size:1.3rem;">Password Reset</h2>
                    <p style="color:#222;font-size:1.05rem;margin-bottom:18px;">
                        Hello,<br><br>
                        We received a request to reset your password for <b>Naija eHub</b>.
                    </p>
                    <p style="margin-bottom:22px;">
                        <a href="' . $reset_link . '" style="display:inline-block;padding:12px 28px;background:#067c3c;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:1.08rem;">Reset Password</a>
                    </p>
                    <p style="color:#444;font-size:0.98rem;margin-bottom:18px;">
                        Or copy and paste this link in your browser:<br>
                        <span style="color:#067c3c;font-size:0.97rem;word-break:break-all;">' . $reset_link . '</span>
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

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'kuyabetech@gmail.com'; // Your Gmail address
                $mail->Password = 'dlix xtim dhtx biau';   // Your Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom('kuyabetech@gmail.com', 'Naija eHub');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->send();
                $message = "A password reset link has been sent to your email.";
            } catch (Exception $e) {
                $message = "Failed to send reset email. Please contact support.";
            }
        } else {
            $message = "No account found with that email.";
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Naija eHub</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <style>
    body {
      background: #f7f7f7;
      font-family: 'Segoe UI', Arial, sans-serif;
    }
    .reset-container {
      max-width: 400px;
      margin: 60px auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.07);
      padding: 2.5rem 2rem 2rem 2rem;
    }
    .reset-container h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: #067c3c;
    }
    form label {
      display: block;
      margin-bottom: 0.5rem;
      color: #444;
      font-weight: 500;
    }
    form input[type="email"] {
      width: 100%;
      padding: 0.7rem;
      border: 1px solid #ccc;
      border-radius: 5px;
      margin-bottom: 1.2rem;
      font-size: 1rem;
      background: #fafafa;
    }
    form button[type="submit"] {
      width: 100%;
      background: #067c3c;
      color: #fff;
      border: none;
      padding: 0.8rem 0;
      border-radius: 5px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    form button[type="submit"]:hover {
      background: #055c2a;
    }
    .message {
      margin-top: 1.2rem;
      padding: 0.9rem 1rem;
      border-radius: 5px;
      background: #e6f9ed;
      color: #067c3c;
      border: 1px solid #b6e7c9;
      font-size: 1rem;
      text-align: center;
    }
    .message a {
      color: #067c3c;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="reset-container">
    <h2>Forgot Password</h2>
    <form method="post" action="">
      <label>Email Address</label>
      <input type="email" name="email" required placeholder="Enter your email">
      <button type="submit">Send Reset Link</button>
    </form>
    <?php if ($message): ?>
      <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
  </div>
</body>
</html>
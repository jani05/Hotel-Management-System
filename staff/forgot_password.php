<?php
include 'connections.php';
$msg = '';
if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT * FROM account WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Generate token and expiry
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        // Remove old tokens for this email
        $conn->query("DELETE FROM password_resets WHERE email = '" . $conn->real_escape_string($email) . "'");
        // Insert new token
        $stmt2 = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt2->bind_param("sss", $email, $token, $expires);
        $stmt2->execute();
        $stmt2->close();
        // Send email
        $reset_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=$token";
        $subject = "Villa Valore Hotel Password Reset";
        $body = "Click the link to reset your password: <a href='$reset_link'>$reset_link</a>\nIf you did not request this, ignore this email.";
        // Try PHPMailer if available
        if (file_exists('../phpmailer/src/PHPMailer.php')) {
            require_once '../phpmailer/src/PHPMailer.php';
            require_once '../phpmailer/src/SMTP.php';
            require_once '../phpmailer/src/Exception.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your_gmail@gmail.com'; // CHANGE THIS
            $mail->Password = 'your_app_password'; // CHANGE THIS
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom('your_gmail@gmail.com', 'Villa Valore Hotel');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            @$mail->send();
        } else {
            // Fallback to mail()
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Villa Valore Hotel <noreply@villavalore.com>' . "\r\n";
            @mail($email, $subject, $body, $headers);
        }
    }
    $msg = "If this email exists in our records, you will receive a password reset instruction.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password | Villa Valore Hotel</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #ffffff;
        }

        .container {
            background-color: #f0f0f0;
            padding: 40px 35px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #2e7d32;
            color: white;
            border: none;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #256428;
        }

        .message {
            color: green;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <p>Enter your email to reset your password.</p> 
        <form method="POST">
            <input type="email" name="email" placeholder="Your email" required>
            <button type="submit" name="submit">Send Reset Link</button>
            <button type="button" onclick="window.location.href='login.php'">← Back to Login</button>
        </form>
        <?php if (isset($msg)) echo "<div class='message'>$msg</div>"; ?>
    </div>
</body>
</html>

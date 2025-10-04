<?php
include 'connections.php';
$token = $_GET['token'] ?? '';
$msg = '';
$show_form = false;
$email = '';
if ($token) {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $email = $row['email'];
        $show_form = true;
    } else {
        $msg = 'Invalid or expired reset link.';
    }
    $stmt->close();
} else {
    $msg = 'Invalid or expired reset link.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $show_form) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (strlen($new_password) < 6) {
        $msg = 'Password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $msg = 'Passwords do not match.';
    } else {
        // Update password in account table
        $stmt = $conn->prepare("UPDATE account SET Password = ? WHERE Email = ?");
        $stmt->bind_param("ss", $new_password, $email);
        if ($stmt->execute()) {
            // Delete the token
            $conn->query("DELETE FROM password_resets WHERE email = '" . $conn->real_escape_string($email) . "'");
            $msg = 'Password reset successful! You may now <a href=\"../login.php\">sign in</a>.';
            $show_form = false;
        } else {
            $msg = 'Failed to reset password. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password | Villa Valore Hotel</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #ffffff; }
        .container { background-color: #f0f0f0; padding: 40px 35px; border-radius: 12px; width: 100%; max-width: 400px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 8px; border: 1px solid #ccc; font-size: 14px; }
        button { width: 100%; padding: 12px; background-color: #2e7d32; color: white; border: none; font-size: 16px; border-radius: 8px; cursor: pointer; margin-top: 10px; }
        button:hover { background-color: #256428; }
        .message { color: green; font-size: 14px; margin-top: 10px; }
        .error { color: red; font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($msg): ?>
            <div class="<?php echo $show_form ? 'error' : 'message'; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if ($show_form): ?>
        <form method="POST">
            <input type="password" name="new_password" placeholder="New password" required>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
            <button type="submit" name="reset">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>

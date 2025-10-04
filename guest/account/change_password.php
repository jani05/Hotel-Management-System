<?php
session_start();
include '../connections.php';

// Require login
//if (!isset($_SESSION['student_id'])) {
    //header('Location: ../login.php');
    //exit();
//}

$student_id = $_SESSION['student_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch current password hash
    $stmt = $conn->prepare('SELECT Password FROM student WHERE StudentID = ? LIMIT 1');
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!password_verify($old_password, $row['Password'])) {
            $msg = '<div class="error">Old password is incorrect.</div>';
        } elseif (strlen($new_password) < 6) {
            $msg = '<div class="error">New password must be at least 6 characters.</div>';
        } elseif ($new_password !== $confirm_password) {
            $msg = '<div class="error">New passwords do not match.</div>';
        } else {
            // Update password and confirm password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare('UPDATE student SET Password = ?, ConfirmPassword = ? WHERE StudentID = ?');
            $update->bind_param('sss', $new_hash, $new_password, $student_id);
            if ($update->execute()) {
                $msg = '<div class="success">Password changed successfully!</div>';
            } else {
                $msg = '<div class="error">Failed to update password. Please try again.</div>';
            }
        }
    } else {
        $msg = '<div class="error">User not found.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Change Password | Villa Valore Hotel</title>
    <link rel="stylesheet" href="../guest.css">
    <style>
        body { background: #f4f4f4; }
        .container { max-width: 400px; margin: 60px auto; background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 2px 8px #eee; text-align: center; }
        .logo-container { display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 1.5em; }
        .logo-container img { width: 90px; height: auto; display: block; margin: 0 auto 0.5em auto; }
        .hotel-title { color: #018000; font-size: 1.5em; font-weight: bold; margin-bottom: 0.2em; }
        .hotel-subtitle { color: #555; font-size: 0.95em; margin-bottom: 1em; }
        h2 { color: #018000; margin-bottom: 1em; }
        .form-group { margin-bottom: 1.2em; text-align: left; }
        label { display: block; margin-bottom: 0.5em; color: #333; }
        input[type="password"] { width: 100%; padding: 0.7em; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 0.8em; background: #018000; color: #fff; border: none; border-radius: 4px; font-size: 1em; cursor: pointer; }
        button:hover { background: #016b00; }
        .error { background: #ffe6e6; color: #b30000; padding: 0.7em; border-radius: 4px; margin-bottom: 1em; }
        .success { background: #e6f8ec; color: #018000; padding: 0.7em; border-radius: 4px; margin-bottom: 1em; }
        .back-link { display: block; margin-top: 1.5em; text-align: center; color: #018000; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="../villa-valore-logo.png" alt="Villa Valore Logo">
            <div class="hotel-title">Villa Valore Hotel</div>
            <div class="hotel-subtitle">BIGA I, SILANG, CAVITE</div>
        </div>
        <h2>Change Password</h2>
        <?php echo $msg; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="old_password">Old Password</label>
                <input type="password" id="old_password" name="old_password" required />
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required />
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required />
            </div>
            <button type="submit">Change Password</button>
        </form>
        <a href="../mybookings.php" class="back-link">&larr; Back to My Bookings</a>
    </div>
</body>
</html> 
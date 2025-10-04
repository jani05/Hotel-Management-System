<?php
session_start();
include 'connections.php';

$email = ""; 
$stored_otp = ""; 
$message=""; 

$ip_address = $_SERVER['REMOTE_ADDR']; 

$sql = "SELECT Email, OTP FROM student WHERE IP = '$ip_address' AND Status = 'pending' 
        ORDER BY OTP_Send_Time DESC"; 

$result = $connect->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $email = $row['Email'];
    $stored_otp = $row['OTP'];
    
} else {
    $message = "No pending OTP with this email.";
}

if (isset($_POST['register'])) {
    $entered_otp = isset($_POST['otp']) ? $_POST['otp'] : '';

    if ($entered_otp === $stored_otp && !empty($stored_otp)) {
        $sql_update = "UPDATE student SET Status = 'verified' WHERE Email = '$email' AND IP = '$ip_address'";
        if ($connect->query($sql_update) === TRUE) {
            $message = "Email Verified successfully";
            header("Location: register.php");
            exit();
        } else {
            $message = "Error updating OTP Status: " . $connect->error;
        }
    } elseif (!empty($entered_otp)) {
        $message = "Invalid OTP. Please try again.";
    }
}
?>

<?php
include 'connections.php';

// Handle setting cookie if student_id is in the URL
if (isset($_GET['setcookie'])) {
    $studentId = $_GET['setcookie'];
    setcookie('selected_student', $studentId, time() + 3600, "/"); // 1-hour expiry
    header("Location: student.php"); // Redirect to prevent resubmission
    exit();
}

$sql = "SELECT StudentID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate, DocumentIDType, IDNumber, IssuedDate, ExpiryDate FROM student";
$result = $conn->query($sql);
?>
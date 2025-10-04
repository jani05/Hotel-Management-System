<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'connections.php';

$student_id = $_SESSION['student_id'] ?? '';
if (!$student_id) {
    header('Location: login.php');
    exit();
}

// Fetch account info
$student = $conn->query("SELECT * FROM student WHERE StudentID = '$student_id'")->fetch_assoc();

// Fetch booking history
$bookings = $conn->query("SELECT * FROM booking WHERE StudentID = '$student_id' ORDER BY BookingDate DESC");
$all_bookings = [];
$booking_ids = [];
while ($row = $bookings->fetch_assoc()) {
    $all_bookings[] = $row;
    $booking_ids[] = "'" . $conn->real_escape_string($row['BookingID']) . "'";
}
$booking_ids_str = implode(',', $booking_ids);
$payments = [];
if ($booking_ids_str) {
    $result = $conn->query("SELECT * FROM payment WHERE BookingID IN ($booking_ids_str) ORDER BY PaymentDate DESC");
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}

// Fetch guest requests for this student
$guest_requests = [];
$req_result = $conn->query("SELECT * FROM guest_requests WHERE GuestName = '{$student['FirstName']} {$student['LastName']}' ORDER BY RequestTime DESC");
while ($row = $req_result && $req_result->fetch_assoc() ? $row : null) {
    $guest_requests[] = $row;
}

// Handle profile photo upload
if (isset($_POST['upload_photo']) && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $newName = $student_id . '_' . time() . '.' . $ext;
        $targetDir = __DIR__ . '/images/profiles/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . $newName;
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFile)) {
            $conn->query("UPDATE student SET ProfilePhoto = '$newName' WHERE StudentID = '$student_id'");
            header("Location: mybookings.php");
            exit();
        } else {
            echo '<div class="error">Failed to upload photo.</div>';
        }
    } else {
        echo '<div class="error">Invalid file type. Only JPG, PNG, GIF allowed.</div>';
    }
}

// Handle guest request submission
if (isset($_POST['submit_guest_request'])) {
    $req_student_id = $conn->real_escape_string($_POST['req_student_id'] ?? '');
    $req_room_number = $conn->real_escape_string($_POST['req_room_number'] ?? '');
    $req_details = $conn->real_escape_string($_POST['req_details'] ?? '');
    if ($req_student_id && $req_room_number && $req_details) {
        $conn->query("INSERT INTO guest_requests (GuestName, RoomNumber, RequestDetails, Priority, Status) VALUES ('{$student['FirstName']} {$student['LastName']}', '$req_room_number', '$req_details', 'Low', 'Pending')");
        header("Location: mybookings.php?request_success=1");
        exit();
    } else {
        $guest_request_msg = '<div class="error">All fields are required.</div>';
    }
}

// Show success message if redirected after request
if (isset($_GET['request_success'])) {
    $guest_request_msg = '<div class="success-msg">Your request has been sent!</div>';
}

// Add logic to handle account info update
if (isset($_POST['save_account_info']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $first_name = $conn->real_escape_string($_POST['first_name'] ?? '');
    $last_name = $conn->real_escape_string($_POST['last_name'] ?? '');
    $gender = $conn->real_escape_string($_POST['gender'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    $nationality = $conn->real_escape_string($_POST['nationality'] ?? '');
    $birthdate = $conn->real_escape_string($_POST['birthdate'] ?? '');
    $email = $conn->real_escape_string($student['Email']);
    $update_student = "UPDATE student SET FirstName='$first_name', LastName='$last_name', Gender='$gender', PhoneNumber='$phone', Address='$address', Nationality='$nationality', Birthdate='$birthdate' WHERE StudentID='$student_id'";
    $update_account = "UPDATE account SET FirstName='$first_name', LastName='$last_name', PhoneNumber='$phone' WHERE Email='$email'";
    $ok1 = $conn->query($update_student);
    $ok2 = $conn->query($update_account);
    if ($ok1) {
        echo json_encode([
            'success' => true,
            'msg' => 'Account information updated successfully!',
            'data' => [
                'FirstName' => $first_name,
                'LastName' => $last_name,
                'Gender' => $gender,
                'PhoneNumber' => $phone,
                'Address' => $address,
                'Nationality' => $nationality,
                'Birthdate' => $birthdate,
                'FullName' => $first_name . ' ' . $last_name,
                'Email' => $student['Email']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'msg' => 'Failed to update account information. SQL Error: ' . $conn->error
        ]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    :root {
      --primary-green: #018000;
      --primary-green-dark: #016b00;
      --card-bg: #f8f8f8;
      --border-radius: 12px;
      --shadow: 0 2px 12px rgba(1,128,0,0.07);
    }
    body { background: #f4f4f4; font-family: 'Segoe UI', Arial, sans-serif; }
    .section {
      background: #fff;
      border-radius: var(--border-radius);
      box-shadow: var(--shadow);
      margin: 2em 0;
      padding: 2.5em 3vw;
      max-width: 100vw;
      width: 100vw;
    }
    .tab-bar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      border-bottom: 2px solid #e0e0e0;
      margin-bottom: 2em;
      background: #fff;
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      overflow: hidden;
      width: 100%;
    }
    .tab-btn {
      flex: 1;
      text-align: center;
      padding: 1.1em 0 0.7em 0;
      background: #fff;
      border: none;
      border-bottom: 3px solid transparent;
      color: #444;
      font-size: 1.13em;
      font-weight: 600;
      cursor: pointer;
      outline: none;
      transition: background 0.18s, border-bottom 0.18s, color 0.18s;
      position: relative;
    }
    .tab-btn .fa-solid { margin-right: 0.5em; }
    .tab-btn.active, .tab-btn:focus {
      background: #fff;
      border-bottom: 3px solid var(--primary-green);
      color: var(--primary-green);
      z-index: 2;
    }
    .tab-btn:not(.active):hover {
      background: #e6f8ec;
      color: var(--primary-green);
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .profile-card {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      box-shadow: 0 1px 6px #e0e0e0;
      padding: 2.5em 2vw 2em 2vw;
      margin-bottom: 2em;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      width: 100%;
      max-width: 100vw;
    }
    .profile-photo { width: 110px; height: 110px; object-fit: cover; border-radius: 50%; border: 3px solid var(--primary-green); box-shadow: 0 2px 8px #e0e0e0; margin-bottom: 1em; background: #fff; }
    .upload-form { display: flex; flex-direction: column; align-items: center; gap: 0.5em; }
    .upload-label { background: var(--primary-green); color: #fff; padding: 0.5em 1.3em; border-radius: 4px; cursor: pointer; font-size: 1em; font-weight: 500; border: none; margin-bottom: 0.2em; transition: background 0.2s; display: inline-block; }
    .upload-label:hover { background: var(--primary-green-dark); }
    .upload-form input[type="file"] { display: none; }
    .account-table, .booking-table, .payment-table, .requests-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: var(--border-radius);
      overflow: hidden;
      margin-top: 1.5em;
      box-shadow: 0 1px 6px #e0e0e0;
      max-width: 100vw;
    }
    .account-table th, .account-table td, .booking-table th, .booking-table td, .payment-table th, .payment-table td, .requests-table th, .requests-table td { padding: 0.85em 1.2em; border-bottom: 1px solid #eee; text-align: left; }
    .account-table th, .booking-table th, .payment-table th, .requests-table th { background: #e6f8ec; color: var(--primary-green); font-weight: 600; font-size: 1.05em; }
    .account-table tr:last-child td, .booking-table tr:last-child td, .payment-table tr:last-child td, .requests-table tr:last-child td { border-bottom: none; }
    .success-msg { background: #e6f8ec; color: var(--primary-green); border-left: 6px solid #34a56f; padding: 1em; border-radius: 6px; margin-bottom: 2em; }
    .change-password-btn, .request-btn { display: inline-block; padding: 0.5em 1.2em; background: var(--primary-green); color: #fff; border-radius: 4px; text-decoration: none; font-size: 0.98em; margin-top: 1.2em; margin-bottom: 0.5em; border: none; transition: background 0.2s; cursor:pointer; }
    .change-password-btn:hover, .request-btn:hover { background: var(--primary-green-dark); }
    .requests-table td.status-pending { color: #b8860b; font-weight: 600; }
    .requests-table td.status-completed { color: #018000; font-weight: 600; }
    @media (max-width: 700px) {
      .section { padding: 1.2em 0.1em; }
      .profile-card { padding: 1.2em 0.2em; }
      .account-table th, .account-table td, .booking-table th, .booking-table td, .payment-table th, .payment-table td, .requests-table th, .requests-table td { padding: 0.7em 0.5em; font-size: 0.98em; }
      .tab-bar { flex-direction: column; }
    }
    /* Success notification bar (matches staff guest request style) */
    .top-success-notification {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 9999;
      background: #c6f6d5;
      color: #2f855a;
      font-weight: 600;
      text-align: center;
      padding: 1.1em 0.5em;
      font-size: 1.08em;
      box-shadow: 0 2px 8px #0001;
      border-bottom: 2px solid #34a56f;
      display: none;
      animation: fadeIn 0.5s;
    }
    @keyframes fadeIn { from { opacity: 0; top: -40px; } to { opacity: 1; top: 0; } }
    .profile-photo-hover:hover .upload-form { opacity: 1 !important; }
    .profile-photo-hover .upload-form { opacity: 0; pointer-events: none; }
    .profile-photo-hover:hover .upload-form { pointer-events: auto; }
    .main-section-container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(1,128,0,0.08), 0 1.5px 8px rgba(44,204,64,0.06);
      max-width: 1200px;
      margin: 2.5em auto 2em auto;
      padding: 2.5em 2em 2em 2em;
      min-height: 70vh;
      width: 95vw;
      transition: box-shadow 0.2s;
    }
    @media (max-width: 900px) {
      .main-section-container { padding: 1.2em 0.5em; }
    }
  </style>
</head>
<body>

<!-- Header -->
<header class="main-header">
  <div class="brand">
    <img src="villa-valore-logo.png" alt="Villa Valore Logo" class="villa-logo">
    <div class="brand-text">
      <h1>Villa Valore Hotel</h1>
      <small>BIGA I, SILANG, CAVITE</small>
    </div>
  </div>
  <nav class="nav-links">
    <a href="index.php">Home</a>
    <a href="mybookings.php">My Bookings</a>
    <?php if (isset($_SESSION['student_id'])): ?>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Log In</a>
    <?php endif; ?>
  </nav>
</header>

<!-- Removed main .section container -->
<div class="main-section-container">
<div class="tab-bar">
  <button class="tab-btn active" id="tab-account" type="button"><i class="fa-solid fa-user"></i>Account Information</button>
  <button class="tab-btn" id="tab-booking" type="button"><i class="fa-solid fa-bed"></i>Booking History</button>
  <button class="tab-btn" id="tab-payment" type="button"><i class="fa-solid fa-credit-card"></i>Payment History</button>
  <button class="tab-btn" id="tab-requests" type="button"><i class="fa-solid fa-bell-concierge"></i>Requests</button>
</div>
<div class="tab-content active" id="content-account">
  <?php if (isset($_GET['success'])): ?><div class="success-msg">Your booking and payment were successful!</div><?php endif; ?>
  <?php if (!empty($guest_request_msg)) echo $guest_request_msg; ?>
  <div id="topSuccessNotification" class="top-success-notification"></div>
  <?php $profilePhoto = !empty($student['ProfilePhoto']) ? 'images/profiles/' . htmlspecialchars($student['ProfilePhoto']) : 'images/default-profile.png'; ?>
  <div class="profile-card" style="display:flex;flex-direction:row;align-items:flex-start;gap:2.5em;max-width:1200px;margin:0 auto 2em auto;padding:2.5em 2em 2em 2em;width:100%;">
    <div style="flex:0 0 140px;display:flex;flex-direction:column;align-items:center;position:relative;">
      <form method="POST" enctype="multipart/form-data" id="photoUploadForm" style="position:relative;width:130px;height:130px;">
        <img src="<?php echo $profilePhoto; ?>" alt="Profile Photo" class="profile-photo" style="width:130px;height:130px;">
        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display:none;" onchange="document.getElementById('photoUploadForm').submit()">
        <input type="hidden" name="upload_photo" value="1">
        <button type="button" class="upload-label" style="background:rgba(1,128,0,0.85);color:#fff;padding:0.4em 1em;font-size:0.98em;border-radius:6px;cursor:pointer;z-index:2;position:absolute;bottom:10px;left:50%;transform:translateX(-50%);opacity:0;transition:opacity 0.2s;" onclick="document.getElementById('profile_photo').click();" id="uploadPhotoBtn">Upload Photo</button>
      </form>
      <style>
        #photoUploadForm:hover #uploadPhotoBtn { opacity: 1; }
        #uploadPhotoBtn { opacity: 0; pointer-events: none; }
        #photoUploadForm:hover #uploadPhotoBtn { pointer-events: auto; }
      </style>
    </div>
    <div style="flex:1;display:flex;flex-direction:column;justify-content:space-between;height:100%;min-width:0;">
      <form method="POST" id="accountInfoForm">
        <table class="account-table" style="margin-top:0;margin-bottom:1.5em;width:100%;max-width:100%;">
          <tr><th>Student ID</th><td id="td-studentid"><input type="text" name="student_id" value="<?php echo htmlspecialchars($student['StudentID']); ?>" readonly style="width:100%;background:#f4f4f4;border:none;"></td></tr>
          <tr><th>Name</th><td id="td-name">
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['FirstName']); ?>" style="width:48%;margin-right:2%;" required>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['LastName']); ?>" style="width:48%;" required>
          </td></tr>
          <tr><th>Email</th><td id="td-email"><input type="email" name="email" value="<?php echo htmlspecialchars($student['Email']); ?>" readonly style="width:100%;background:#f4f4f4;border:none;"></td></tr>
          <tr><th>Gender</th><td id="td-gender">
            <select name="gender" style="width:100%;">
              <option value="Male" <?php if ($student['Gender'] == 'Male') echo 'selected'; ?>>Male</option>
              <option value="Female" <?php if ($student['Gender'] == 'Female') echo 'selected'; ?>>Female</option>
              <option value="Prefer not to say" <?php if ($student['Gender'] == 'Prefer not to say') echo 'selected'; ?>>Prefer not to say</option>
              <option value="Other" <?php if ($student['Gender'] == 'Other') echo 'selected'; ?>>Other</option>
            </select>
          </td></tr>
          <tr><th>Phone Number</th><td id="td-phone"><input type="text" name="phone" value="<?php echo htmlspecialchars($student['PhoneNumber']); ?>" style="width:100%;"></td></tr>
          <tr><th>Address</th><td id="td-address"><input type="text" name="address" value="<?php echo htmlspecialchars($student['Address']); ?>" style="width:100%;"></td></tr>
          <tr><th>Nationality</th><td id="td-nationality"><input type="text" name="nationality" value="<?php echo htmlspecialchars($student['Nationality']); ?>" style="width:100%;"></td></tr>
          <tr><th>Birthdate</th><td id="td-birthdate"><input type="date" name="birthdate" value="<?php echo htmlspecialchars($student['Birthdate']); ?>" style="width:100%;"></td></tr>
        </table>
        <div style="text-align:left;width:100%;margin-top:auto;display:flex;gap:0.7em;align-items:center;">
          <form method="POST" id="accountInfoFormBtn" style="display:inline;">
            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($student['FirstName']); ?>">
            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($student['LastName']); ?>">
            <input type="hidden" name="gender" value="<?php echo htmlspecialchars($student['Gender']); ?>">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($student['PhoneNumber']); ?>">
            <input type="hidden" name="address" value="<?php echo htmlspecialchars($student['Address']); ?>">
            <input type="hidden" name="nationality" value="<?php echo htmlspecialchars($student['Nationality']); ?>">
            <input type="hidden" name="birthdate" value="<?php echo htmlspecialchars($student['Birthdate']); ?>">
            <button type="submit" name="save_account_info" class="change-password-btn" style="background:#018000;padding:0.5em 1.2em;font-size:0.98em;border-radius:4px;width:180px;min-width:180px;max-width:180px;text-align:center;">Update Info</button>
          </form>
          <a href="account/change_password.php" class="change-password-btn" style="margin:0;padding:0.5em 1.2em;font-size:0.98em;border-radius:4px;width:180px;min-width:180px;max-width:180px;text-align:center;display:inline-block;">Change Password</a>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="tab-content" id="content-booking">
  <div style="max-width:1100px;margin:0 auto 2em auto;padding:0 1vw;">
    <table class="booking-table" style="width:100%;">
      <tr>
        <th>Booking ID</th>
        <th>Room Type</th>
        <th>Check-in</th>
        <th>Check-out</th>
        <th>Status</th>
        <th>Price</th>
        <th>Booking Date</th>
      </tr>
      <?php if (count($all_bookings)): foreach ($all_bookings as $b): ?>
      <tr>
        <td><?php echo htmlspecialchars($b['BookingID']); ?></td>
        <td><?php echo htmlspecialchars($b['RoomType']); ?></td>
        <td><?php echo htmlspecialchars($b['CheckInDate']); ?></td>
        <td><?php echo htmlspecialchars($b['CheckOutDate']); ?></td>
        <td><?php echo htmlspecialchars($b['BookingStatus']); ?></td>
        <td>₱<?php echo number_format($b['Price']); ?></td>
        <td><?php echo htmlspecialchars($b['BookingDate']); ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="7">No bookings found.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<div class="tab-content" id="content-payment">
  <div style="max-width:1100px;margin:0 auto 2em auto;padding:0 1vw;">
    <table class="payment-table" style="width:100%;">
      <tr>
        <th>Payment ID</th>
        <th>Booking ID</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Method</th>
        <th>Date</th>
        <th>Reference Code</th>
      </tr>
      <?php if (count($payments)): foreach ($payments as $p): ?>
      <tr>
        <td><?php echo htmlspecialchars($p['PaymentID']); ?></td>
        <td><?php echo htmlspecialchars($p['BookingID']); ?></td>
        <td>₱<?php echo number_format($p['Amount']); ?></td>
        <td><?php echo htmlspecialchars($p['PaymentStatus']); ?></td>
        <td><?php echo htmlspecialchars($p['PaymentMethod']); ?></td>
        <td><?php echo htmlspecialchars($p['PaymentDate']); ?></td>
        <td><?php echo htmlspecialchars($p['ReferenceCode']); ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="7">No payments found.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<div class="tab-content" id="content-requests">
  <form method="POST" autocomplete="off" style="margin-bottom:2em;max-width:1200px;margin-left:auto;margin-right:auto;">
    <h3 style="color:#018000;margin-bottom:1em;">Guest Request</h3>
    <div class="form-group" style="margin-bottom:1em;">
      <label for="req_student_id">Student ID</label>
      <input type="text" id="req_student_id" name="req_student_id" value="<?php echo htmlspecialchars($student['StudentID']); ?>" readonly style="width:100%;padding:0.5em;border-radius:4px;border:1px solid #ccc;">
    </div>
    <div class="form-group" style="margin-bottom:1em;">
      <label for="req_room_number">Room Number</label>
      <input type="text" id="req_room_number" name="req_room_number" required style="width:100%;padding:0.5em;border-radius:4px;border:1px solid #ccc;">
    </div>
    <div class="form-group" style="margin-bottom:1em;">
      <label for="req_details">Request Details</label>
      <textarea id="req_details" name="req_details" rows="3" required style="width:100%;padding:0.5em;border-radius:4px;border:1px solid #ccc;"></textarea>
    </div>
    <button type="submit" name="submit_guest_request" class="request-btn">Send Request</button>
  </form>
  <div style="max-width:1200px;margin:0 auto 2em auto;padding:0 1vw;">
    <h4 style="color:#018000;margin-bottom:0.7em;">Your Requests</h4>
    <table class="requests-table" style="width:100%;">
      <tr>
        <th>Room Number</th>
        <th>Details</th>
        <th>Status</th>
        <th>Time</th>
      </tr>
      <?php if (count($guest_requests)): foreach ($guest_requests as $r): ?>
      <tr>
        <td><?php echo htmlspecialchars($r['RoomNumber']); ?></td>
        <td><?php echo htmlspecialchars($r['RequestDetails']); ?></td>
        <td class="status-<?php echo strtolower($r['Status']); ?>"><?php echo htmlspecialchars($r['Status']); ?></td>
        <td><?php echo htmlspecialchars($r['RequestTime']); ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="4">No requests found.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
</div>
<script>
  // Tab switching logic
  const tabBtns = document.querySelectorAll('.tab-btn');
  const tabContents = document.querySelectorAll('.tab-content');
  tabBtns.forEach((btn, idx) => {
    btn.addEventListener('click', function() {
      tabBtns.forEach(b => b.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      tabContents[idx].classList.add('active');
    });
  });
  // Show top notification if account info updated
  <?php if (!empty($account_update_msg)) { ?>
    document.addEventListener('DOMContentLoaded', function() {
      var notif = document.getElementById('topSuccessNotification');
      notif.textContent = <?php echo json_encode(strip_tags($account_update_msg)); ?>;
      notif.style.display = 'block';
      setTimeout(function() { notif.style.display = 'none'; }, 3500);
    });
  <?php } ?>
  document.getElementById('accountInfoForm').onsubmit = function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
      console.log(xhr.responseText); // Debug: log raw response
      if (xhr.status === 200) {
        try {
          var res = JSON.parse(xhr.responseText);
          if (res.success) {
            // Update table cells with new values
            document.querySelector('#td-name').innerHTML =
              '<input type="text" name="first_name" value="'+res.data.FirstName+'" style="width:48%;margin-right:2%;" required> '
              +'<input type="text" name="last_name" value="'+res.data.LastName+'" style="width:48%;" required>';
            document.querySelector('#td-gender').innerHTML =
              '<select name="gender" style="width:100%;">'
              +'<option value="Male"'+(res.data.Gender==='Male'?' selected':'')+'>Male</option>'
              +'<option value="Female"'+(res.data.Gender==='Female'?' selected':'')+'>Female</option>'
              +'<option value="Prefer not to say"'+(res.data.Gender==='Prefer not to say'?' selected':'')+'>Prefer not to say</option>'
              +'<option value="Other"'+(res.data.Gender==='Other'?' selected':'')+'>Other</option>'
              +'</select>';
            document.querySelector('#td-phone').innerHTML = '<input type="text" name="phone" value="'+res.data.PhoneNumber+'" style="width:100%;">';
            document.querySelector('#td-address').innerHTML = '<input type="text" name="address" value="'+res.data.Address+'" style="width:100%;">';
            document.querySelector('#td-nationality').innerHTML = '<input type="text" name="nationality" value="'+res.data.Nationality+'" style="width:100%;">';
            document.querySelector('#td-birthdate').innerHTML = '<input type="date" name="birthdate" value="'+res.data.Birthdate+'" style="width:100%;">';
            // Show success notification
            var notif = document.getElementById('topSuccessNotification');
            notif.textContent = res.msg;
            notif.style.display = 'block';
            setTimeout(function() { notif.style.display = 'none'; }, 3500);
          } else {
            alert(res.msg);
          }
        } catch (e) { alert('Error updating info.'); }
      }
    };
    xhr.send(formData);
    return false;
  };
</script>
</body>
</html>
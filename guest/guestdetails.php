<?php
include 'connections.php';
session_start(); // Enable sessions

// Add PHPMailer use statements at the top
// require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
// require_once __DIR__ . '/../phpmailer/src/SMTP.php';
// require_once __DIR__ . '/../phpmailer/src/Exception.php';
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;

// $confirmation = "";
$generated_booking_id = "";
$estimated_price = "";
$booking_date = date("Y-m-d");

// Fetch Booking ID from GET, SESSION, or latest booking for this student
if (isset($_GET['BookingID'])) {
  $booking_id_param = $conn->real_escape_string($_GET['BookingID']);
  $_SESSION['BookingID'] = $booking_id_param;
} elseif (isset($_SESSION['BookingID'])) {
  $booking_id_param = $conn->real_escape_string($_SESSION['BookingID']);
} elseif (!empty($_SESSION['student_id'])) {
  // Fetch the latest booking for this student
  $student_id = $conn->real_escape_string($_SESSION['student_id']);
  $result = $conn->query("SELECT BookingID FROM booking WHERE StudentID = '$student_id' ORDER BY BookingDate DESC, BookingID DESC LIMIT 1");
  if ($result && $row = $result->fetch_assoc()) {
    $booking_id_param = $row['BookingID'];
    $_SESSION['BookingID'] = $booking_id_param;
  } else {
    $booking_id_param = "";
  }
} else {
  $booking_id_param = "";
}
$generated_booking_id = $booking_id_param;

// Get transferred booking parameters from session
$total_price = $_SESSION['total_price'] ?? '';
$reservation_fee = $_SESSION['reservation_fee'] ?? '';
$duration = $_SESSION['duration'] ?? '';
$adults = $_SESSION['adults'] ?? 1;
$children = $_SESSION['children'] ?? 0;
$checkin_date = $_SESSION['checkin_date'] ?? '';
$checkout_date = $_SESSION['checkout_date'] ?? '';

if (!empty($booking_id_param)) {
  $query = "SELECT BookingID, Price, RoomType, CheckInDate, CheckOutDate, Notes FROM booking WHERE BookingID = '$booking_id_param' LIMIT 1";
  $result = $conn->query($query);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $generated_booking_id = $row['BookingID'];
    $estimated_price = $row['Price'];
    // Use database values if session values are not available
    if (empty($total_price)) $total_price = $estimated_price;
    if (empty($checkin_date)) $checkin_date = $row['CheckInDate'];
    if (empty($checkout_date)) $checkout_date = $row['CheckOutDate'];
  }
}

// Fetch previous student data for pre-filling
$student_data = [
  'StudentID'   => $_SESSION['student_id']   ?? '',
  'FirstName'   => $_SESSION['first_name']   ?? '',
  'LastName'    => $_SESSION['last_name']    ?? '',
  'Gender'      => '',
  'PhoneNumber' => '',
  'Address'     => '',
  'Email'       => $_SESSION['email']        ?? '',
  'Nationality' => '',
  'Birthdate'   => '',
];
if (!empty($student_data['StudentID'])) {
  $sid = $conn->real_escape_string($student_data['StudentID']);
  $res = $conn->query("SELECT * FROM student WHERE StudentID = '$sid' LIMIT 1");
  if ($res && $row = $res->fetch_assoc()) {
    $student_data['FirstName']   = $row['FirstName'];
    $student_data['LastName']    = $row['LastName'];
    $student_data['Gender']      = $row['Gender'];
    $student_data['PhoneNumber'] = $row['PhoneNumber'];
    $student_data['Address']     = $row['Address'];
    $student_data['Email']       = $row['Email'];
    $student_data['Nationality'] = $row['Nationality'];
    $student_data['Birthdate']   = $row['Birthdate'];
  }
}

$form_values = $student_data;

// Fetch payment-related data for pre-filling
$payment_date = date("Y-m-d");
$generated_payment_id = 'PAY' . uniqid();
$generated_reference_code = 'REF' . strtoupper(bin2hex(random_bytes(4)));

// Fetch latest payment for this booking (optional, for pre-fill)
$latest_payment = [
  'Amount' => $total_price,
  'PaymentMethod' => 'Cash',
  'PaymentStatus' => 'Pending',
];

// Fetch Reservation ID from GET or SESSION
if (isset($_GET['ReservationID'])) {
    $reservation_id = $conn->real_escape_string($_GET['ReservationID']);
    $_SESSION['reservation_id'] = $reservation_id;
} else {
    $reservation_id = $_SESSION['reservation_id'] ?? '';
}

// Calculate duration in hours if check-in and check-out are set
$duration_hours = '';
if (!empty($checkin_date) && !empty($checkout_date)) {
    $dt1 = new DateTime($checkin_date);
    $dt2 = new DateTime($checkout_date);
    $interval = $dt1->diff($dt2);
    $hours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
    $duration_hours = $hours . ' hour(s)';
}

// Combined Form Submission Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate Guest Details fields
  $required_guest = [
    'StudentID', 'ReservationID', 'FirstName', 'LastName', 'Gender', 'PhoneNumber', 'Address', 'Email', 'Nationality', 'Birthdate'
  ];
  $missing = false;
  foreach ($required_guest as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) $missing = true;
  }
  if ($missing) {
    $confirmation = "<p style='color: red;'>All fields are required.</p>";
  } else {
    // --- GUEST DETAILS DB LOGIC ---
    $student_id   = $conn->real_escape_string($_POST['StudentID']);
    $reservation_id = $conn->real_escape_string($_POST['ReservationID']);
    $gender       = $conn->real_escape_string($_POST['Gender']);
    $phone_number = $conn->real_escape_string($_POST['PhoneNumber']);
    $address      = $conn->real_escape_string($_POST['Address']);
    $nationality  = $conn->real_escape_string($_POST['Nationality']);
    $birthdate    = $conn->real_escape_string($_POST['Birthdate']);
    $first_name   = $conn->real_escape_string($_POST['FirstName']);
    $last_name    = $conn->real_escape_string($_POST['LastName']);
    $email        = $conn->real_escape_string($_POST['Email']);
    // Check if student exists
    $check = $conn->query("SELECT 1 FROM student WHERE StudentID = '$student_id' LIMIT 1");
    if ($check && $check->num_rows > 0) {
      // Student exists, update only the allowed fields
      $sql = "UPDATE student SET 
        Gender = '$gender',
        PhoneNumber = '$phone_number',
        Address = '$address',
        Nationality = '$nationality',
        Birthdate = '$birthdate'
        WHERE StudentID = '$student_id'";
      $conn->query($sql);
    } else {
      // Student does not exist, insert new
      $sql_insert = "INSERT INTO student (StudentID, FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, Birthdate)
        VALUES ('$student_id', '$first_name', '$last_name', '$gender', '$phone_number', '$address', '$email', '$nationality', '$birthdate')";
      $conn->query($sql_insert);
    }
    // TODO: Insert reservation record if not already done in reservenow.php
    // TODO: Staff-side: When payment is confirmed, issue invoice, send receipt, and update guest account/bookings.
  }
}

// DEBUG: Show last 5 bookings and payments for troubleshooting
$debug_bookings = $conn->query("SELECT * FROM booking ORDER BY BookingDate DESC, BookingID DESC LIMIT 5");
$debug_payments = $conn->query("SELECT * FROM payment ORDER BY PaymentDate DESC, PaymentID DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="styles/guestinfo.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <style>
    .booking-summary {
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .booking-summary h3 {
      color: #018000;
      margin-bottom: 10px;
      font-size: 18px;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 14px;
    }
    .summary-label {
      font-weight: 600;
      color: #555;
    }
    .summary-value {
      color: #333;
    }
    .price-highlight {
      font-size: 18px;
      font-weight: bold;
      color: #018000;
    }
    .flex-container {
      display: flex;
      flex-wrap: wrap;
      gap: 32px;
      align-items: flex-start;
    }
    .form-section {
      flex: 2 1 350px;
      min-width: 320px;
    }
    .booking-summary-section {
      flex: 1 1 300px;
      min-width: 260px;
      max-width: 350px;
      margin-top: 32px;
    }
    @media (max-width: 900px) {
      .flex-container { flex-direction: column; }
      .booking-summary-section { margin-top: 0; max-width: 100%; }
    }
    .booking-summary-floating {
      position: absolute;
      top: 120px;
      right: 5vw;
      z-index: 10;
      max-width: 350px;
      min-width: 260px;
    }
    @media (max-width: 1100px) {
      .booking-summary-floating {
        position: static;
        margin: 0 auto 24px auto;
        display: block;
        max-width: 98vw;
        right: unset;
        top: unset;
      }
    }
    .container_booking {
      position: relative;
      margin-top: 32px;
    }
    .booking-details-flex-row {
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
      align-items: flex-start;
      justify-content: center;
      margin-top: 0;
    }
    .form-section {
      flex: 2 1 350px;
      min-width: 320px;
      max-width: 500px;
    }
    .booking-summary-section {
      flex: 1 1 300px;
      min-width: 260px;
      max-width: 350px;
      margin-top: 0;
    }
    @media (max-width: 900px) {
      .booking-details-flex-row { flex-direction: column; align-items: stretch; }
      .booking-summary-section { margin-top: 24px; max-width: 100%; }
    }
  </style>
</head>
<body>

  <!-- Main Navigation -->
  <header class="main-header">
  <div class="brand">
    <img src="villa-valore-logo.png" alt="Villa Valore Logo" class="villa-logo">
    <div class="brand-text">
    <h1>Villa Valore Hotel</h1>
    <small>BIGA I, SILANG, CAVITE</small>
    </div>
  </div>

  <nav class="nav-links">
    <a href="booking.php">Rooms</a>
    <a href="about.php">About</a>
    <a href="mybookings.php">My Bookings</a>
    <?php if (isset($_SESSION['student_id'])): ?>
      <a href="account/change_password.php">Change Password</a>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Log In</a>
    <?php endif; ?>
  </nav>
  </header>

<!-- Place the booking summary outside and above the container_booking, aligned right
<div class="booking-summary-floating">
  <div class="booking-summary">
    <h3>Booking Summary</h3>
    <div class="summary-row">
      <span class="summary-label">Duration:</span>
      <span class="summary-value"><?php echo htmlspecialchars($duration_hours); ?></span>
    </div>
    <div class="summary-row">
      <span class="summary-label">Guests:</span>
      <span class="summary-value"><?php echo $adults + $children; ?> (<?php echo $adults; ?> adults, <?php echo $children; ?> children)</span>
    </div>
    <div class="summary-row">
      <span class="summary-label">Check-in:</span>
      <span class="summary-value"><?php echo htmlspecialchars($checkin_date); ?></span>
    </div>
    <div class="summary-row">
      <span class="summary-label">Check-out:</span>
      <span class="summary-value"><?php echo htmlspecialchars($checkout_date); ?></span>
    </div>
    <div class="summary-row">
      <span class="summary-label">Total Price:</span>
      <span class="summary-value price-highlight">₱<?php echo number_format($total_price); ?></span>
    </div>
    <div class="summary-row">
      <span class="summary-label">Reservation Fee:</span>
      <span class="summary-value">₱<?php echo number_format($reservation_fee); ?></span>
    </div>
  </div>
</div>
-->

<!-- Guest Details Form -->
<div class="container_booking">
  <div class="booking-details-flex-row">
    <div class="form-section">
      <h2>Guest Information</h2>
      <form method="POST">
        <!-- Reservation ID field -->
        <div class="form-group">
          <label for="ReservationID">Reservation ID:</label>
          <input type="text" id="ReservationID" name="ReservationID" value="<?php echo htmlspecialchars($reservation_id); ?>" readonly />
        </div>

        <div class="form-group">
          <label for="StudentID">Student ID:</label>
          <input type="text" id="StudentID" name="StudentID" required 
          value="<?php echo htmlspecialchars($form_values['StudentID']); ?>" />
        </div>

        <div class="form-group">
          <label for="FirstName">First Name:</label>
          <input type="text" id="FirstName" name="FirstName" required 
          value="<?php echo htmlspecialchars($form_values['FirstName']); ?>" />
        </div>

        <div class="form-group">
          <label for="LastName">Last Name:</label>
          <input type="text" id="LastName" name="LastName" required 
          value="<?php echo htmlspecialchars($form_values['LastName']); ?>" />
        </div>

        <div class="form-group">
          <label for="Gender">Gender:</label>
          <select id="Gender" name="Gender" required>
          <option value="">Select Gender</option>
          <option value="Male" <?php if ($form_values['Gender'] == 'Male') echo 'selected'; ?>>Male</option>
          <option value="Female" <?php if ($form_values['Gender'] == 'Female') echo 'selected'; ?>>Female</option>
          <option value="Prefer not to say" <?php if ($form_values['Gender'] == 'Prefer not to say') echo 'selected'; ?>>Prefer not to say</option>
          <option value="Other" <?php if ($form_values['Gender'] == 'Other') echo 'selected'; ?>>Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="PhoneNumber">Phone Number:</label>
          <input type="text" id="PhoneNumber" name="PhoneNumber" required 
          value="<?php echo htmlspecialchars($form_values['PhoneNumber']); ?>" />
        </div>

        <div class="form-group">
          <label for="Address">Address:</label>
          <input type="text" id="Address" name="Address" required 
          value="<?php echo htmlspecialchars($form_values['Address']); ?>" />
        </div>

        <div class="form-group">
          <label for="Email">Email:</label>
          <input type="email" id="Email" name="Email" required 
          value="<?php echo htmlspecialchars($form_values['Email']); ?>" />
        </div>

        <div class="form-group">
          <label for="Nationality">Nationality:</label>
          <input type="text" id="Nationality" name="Nationality" required 
          value="<?php echo htmlspecialchars($form_values['Nationality']); ?>" />
        </div>

        <div class="form-group">
          <label for="Birthdate">Birthdate:</label>
          <input type="date" id="Birthdate" name="Birthdate" required 
          value="<?php echo htmlspecialchars($form_values['Birthdate']); ?>" />
        </div>

        <button type="submit" class="btn">Submit</button>
        <button type="button" class="btn" onclick="window.location.href='booking.php';">Back</button>

      </form>

    </div>
    <div class="booking-summary-section">
      <div class="booking-summary">
        <h3>Booking Summary</h3>
        <div class="summary-row">
          <span class="summary-label">Duration:</span>
          <span class="summary-value"><?php echo htmlspecialchars($duration_hours); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Guests:</span>
          <span class="summary-value"><?php echo $adults + $children; ?> (<?php echo $adults; ?> adults, <?php echo $children; ?> children)</span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Check-in:</span>
          <span class="summary-value"><?php echo htmlspecialchars($checkin_date); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Check-out:</span>
          <span class="summary-value"><?php echo htmlspecialchars($checkout_date); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Total Price:</span>
          <span class="summary-value price-highlight">₱<?php echo number_format($total_price); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Reservation Fee:</span>
          <span class="summary-value">₱<?php echo number_format($reservation_fee); ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
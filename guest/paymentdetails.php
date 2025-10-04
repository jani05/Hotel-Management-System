<?php 
include 'connections.php';
session_start();
$confirmation = "";
$generated_booking_id = "";
$estimated_price = "";
$student_id = "";
$payment_date = date("Y-m-d");

// Generate a unique PaymentID
$generated_payment_id = 'PAY' . time(); // e.g., PAY1723059271

// Generate a unique ReferenceCode
$generated_reference_code = 'REF' . strtoupper(bin2hex(random_bytes(4))); // e.g., REF1A2B3C4

// Get transferred booking parameters from session
$total_price = $_SESSION['total_price'] ?? '';
$reservation_fee = $_SESSION['reservation_fee'] ?? '';
$duration = $_SESSION['duration'] ?? '';
$adults = $_SESSION['adults'] ?? 1;
$children = $_SESSION['children'] ?? 0;
$checkin_date = $_SESSION['checkin_date'] ?? '';
$checkout_date = $_SESSION['checkout_date'] ?? '';

// Fetch BookingID from GET or SESSION
if (isset($_GET['BookingID'])) {
    $generated_booking_id = $conn->real_escape_string($_GET['BookingID']);
    $_SESSION['BookingID'] = $generated_booking_id;
} elseif (isset($_SESSION['BookingID'])) {
    $generated_booking_id = $conn->real_escape_string($_SESSION['BookingID']);
}

// Fetch booking data
$booking_data = [
    'BookingID' => $generated_booking_id,
    'StudentID' => '',
    'Price' => '',
    'RoomType' => '',
    'CheckInDate' => '',
    'CheckOutDate' => '',
    'Notes' => ''
];
if (!empty($generated_booking_id)) {
    $query = "SELECT * FROM booking WHERE BookingID = '$generated_booking_id' LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $booking_data = $row;
        $student_id = $row['StudentID'];
        if (empty($total_price)) $total_price = $row['Price'];
        if (empty($checkin_date)) $checkin_date = $row['CheckInDate'];
        if (empty($checkout_date)) $checkout_date = $row['CheckOutDate'];
    }
}

// Fetch student data
$student_data = [
    'StudentID'   => $student_id,
    'FirstName'   => '',
    'LastName'    => '',
    'Gender'      => '',
    'PhoneNumber' => '',
    'Address'     => '',
    'Email'       => '',
    'Nationality' => '',
    'Birthdate'   => '',
];
if (!empty($student_id)) {
    $sid = $conn->real_escape_string($student_id);
    $res = $conn->query("SELECT * FROM student WHERE StudentID = '$sid' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $student_data = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['PaymentID']) ||
        empty($_POST['BookingID']) ||
        empty($_POST['PaymentDate']) ||
        empty($_POST['Amount']) ||
        empty($_POST['PaymentStatus']) ||
        empty($_POST['PaymentMethod']) ||
        empty($_POST['ReferenceCode']) ||
        empty($_POST['StudentID']) 
    ) {
        $confirmation = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $payment_id = $conn->real_escape_string($_POST['PaymentID']);
        $booking_id = $conn->real_escape_string($_POST['BookingID']);
        $amount = $conn->real_escape_string($_POST['Amount']);
        $payment_status = $conn->real_escape_string($_POST['PaymentStatus']);
        $payment_date = $conn->real_escape_string($_POST['PaymentDate']);
        $payment_method = $conn->real_escape_string($_POST['PaymentMethod']);
        $reference_code = $conn->real_escape_string($_POST['ReferenceCode']);
        $student_id = $conn->real_escape_string($_POST['StudentID']);

        $sql = "INSERT INTO payment (PaymentID, BookingID, Amount, PaymentStatus, PaymentDate, PaymentMethod, ReferenceCode)
                VALUES ('$payment_id', '$booking_id', '$amount', '$payment_status', '$payment_date', '$payment_method', '$reference_code')";

        if ($conn->query($sql)) {
            $conn->close();
            header("Location: paymentdetails.php?BookingID=$booking_id");
            exit();
        } else {
            $confirmation = "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
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

<!-- Payment Form -->
<div class="container_booking">
  <h2>Payment Details</h2>
  
  <!-- Booking Summary -->
  <?php if ($total_price || $duration || $checkin_date): ?>
  <div class="booking-summary">
    <h3>Booking Summary</h3>
    <?php if ($duration): ?>
    <div class="summary-row">
      <span class="summary-label">Duration:</span>
      <span class="summary-value"><?php echo htmlspecialchars($duration); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($adults || $children): ?>
    <div class="summary-row">
      <span class="summary-label">Guests:</span>
      <span class="summary-value"><?php echo $adults + $children; ?> (<?php echo $adults; ?> adults, <?php echo $children; ?> children)</span>
    </div>
    <?php endif; ?>
    <?php if ($checkin_date): ?>
    <div class="summary-row">
      <span class="summary-label">Check-in:</span>
      <span class="summary-value"><?php echo htmlspecialchars($checkin_date); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($checkout_date): ?>
    <div class="summary-row">
      <span class="summary-label">Check-out:</span>
      <span class="summary-value"><?php echo htmlspecialchars($checkout_date); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($total_price): ?>
    <div class="summary-row">
      <span class="summary-label">Total Price:</span>
      <span class="summary-value price-highlight">₱<?php echo number_format($total_price); ?></span>
    </div>
    <?php endif; ?>
    <?php if ($reservation_fee): ?>
    <div class="summary-row">
      <span class="summary-label">Reservation Fee:</span>
      <span class="summary-value">₱<?php echo number_format($reservation_fee); ?></span>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  
  <form method="POST">
    <input type="hidden" id="BookingID" name="BookingID" value="<?php echo htmlspecialchars($generated_booking_id); ?>" />
    <input type="hidden" id="StudentID" name="StudentID" value="<?php echo htmlspecialchars($student_id); ?>" />

    <div class="form-group">
      <label for="PaymentID">Payment ID:</label>
      <input type="text" id="PaymentID" name="PaymentID" value="<?php echo $generated_payment_id; ?>" readonly />
    </div>

     <!-- Payment Date (hidden for backend) -->
    <input type="hidden" name="PaymentDate" value="<?php echo $payment_date; ?>" />

    <!-- Payment Date (visible for user) -->
    <div class="form-group">
      <label for="PaymentDate">Payment Date:</label>
      <input type="text" id="PaymentDate" value="<?php echo $payment_date; ?>" readonly />
    </div>

  
    <div class="form-group">
      <label for="Amount">Amount:</label>
      <input type="text" id="Amount" name="Amount" value="<?php echo htmlspecialchars($total_price ?: $estimated_price); ?>" required />
    </div>

    <div class="form-group">
      <label for="PaymentMethod">Payment Method:</label>
      <select id="PaymentMethod" name="PaymentMethod" required>
        <option value="Cash">Cash</option>
      </select>
    </div>

    <div class="form-group">
      <label for="PaymentStatus">Payment Status:</label>
      <input type="text" id="PaymentStatus" name="PaymentStatus" value="Pending" required />
    </div>

    <div class="form-group">
      <label for="ReferenceCode">Reference Code:</label>
      <input type="text" id="ReferenceCode" name="ReferenceCode" value="<?php echo $generated_reference_code; ?>" readonly />
    </div>

    <button type="submit" class="btn">Submit</button>
    <button type="button" class="btn" onclick="window.history.back();">Back</button>
  </form>

  <?php echo $confirmation; ?>
</div>

</body>
</html>

<?php
session_start();
include 'connections.php';
$confirmation = "";
$generatedBookingID = "";
$bookingDate = date("Y-m-d");

// Room type selection logic (prefer URL, then session)
$room_type = '';
$valid_rooms = ['standard', 'deluxe', 'suite'];

if (isset($_GET['room']) && in_array(strtolower($_GET['room']), $valid_rooms)) {
  $room_type = strtolower($_GET['room']);
  $_SESSION['selected_room_type'] = $room_type;
} elseif (isset($_SESSION['selected_room_type']) && in_array(strtolower($_SESSION['selected_room_type']), $valid_rooms)) {
  $room_type = strtolower($_SESSION['selected_room_type']);
}

// Get transferred booking parameters (priority: GET > SESSION)
$checkin_date = $_GET['checkin'] ?? $_SESSION['checkin_date'] ?? '';
$checkout_date = $_GET['checkout'] ?? $_SESSION['checkout_date'] ?? '';
$checkin_time = $_GET['checkin_time'] ?? $_SESSION['checkin_time'] ?? '14:00';
$checkout_time = $_GET['checkout_time'] ?? $_SESSION['checkout_time'] ?? '12:00';
$total_price = $_GET['price'] ?? $_SESSION['total_price'] ?? '';
$reservation_fee = $_GET['rf'] ?? $_SESSION['reservation_fee'] ?? '';
$duration = $_GET['duration'] ?? $_SESSION['duration'] ?? '';
$adults = $_GET['adults'] ?? $_SESSION['adults'] ?? 1;
$children = $_GET['children'] ?? $_SESSION['children'] ?? 0;

// Combine date and time for datetime-local inputs
$checkin_datetime = '';
$checkout_datetime = '';
if ($checkin_date && $checkin_time) {
  $checkin_datetime = $checkin_date . 'T' . $checkin_time;
}
if ($checkout_date && $checkout_time) {
  $checkout_datetime = $checkout_date . 'T' . $checkout_time;
}

// Always set Booking ID: use POST if available, else generate new
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['booking_id'])) {
  $generatedBookingID = $_POST['booking_id'];
} else {
  $date_code = date("Ymd");
  $result = $conn->query("SELECT COUNT(*) AS total FROM booking WHERE DATE(BookingDate) = CURDATE()");
  $row = $result->fetch_assoc();
  $count_today = $row['total'] + 1;
  $generatedBookingID = "BK-" . $date_code . "-" . str_pad($count_today, 4, '0', STR_PAD_LEFT);
}

// Booking submission logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST['RoomType']) || empty($_POST['CheckInDate']) || empty($_POST['CheckOutDate'])) {
    $confirmation = "<p style='color: red;'>All fields are required.</p>";
  } else {
    $room_type = $conn->real_escape_string(strtolower($_POST['RoomType']));
    $_SESSION['selected_room_type'] = $room_type;
    $check_in = $_POST['CheckInDate'];
    $check_out = $_POST['CheckOutDate'];
    $special_request = $conn->real_escape_string($_POST['Notes']);
    $booking_id = $_POST['booking_id'];
    $bookingDate = $_POST['BookingDate'];
    
    // Use the transferred price or calculate based on room type
    $price = $_POST['Price'] ?? $total_price;
    if (empty($price)) {
      // Fallback price calculation
      $price_map = [
        'standard' => 2000,
        'deluxe' => 10000,
        'suite' => 5000
      ];
      $price = $price_map[$room_type] ?? 2000;
    }

    // Check if user is logged in
    if (!isset($_SESSION['student_id'])) {
      // Not logged in, redirect to login with all booking parameters
      $params = [
        'room' => $room_type,
        'checkin' => $check_in,
        'checkout' => $check_out,
        'checkin_time' => $checkin_time,
        'checkout_time' => $checkout_time,
        'price' => $price,
        'rf' => $reservation_fee,
        'duration' => $duration,
        'adults' => $adults,
        'children' => $children,
        'next' => 'booknow.php'
      ];
      $query = http_build_query($params);
      header("Location: login.php?$query");
      exit();
    }
    $student_id = $_SESSION['student_id'];
    // Save to database (booking table) with StudentID
    $stmt = $conn->prepare("INSERT INTO booking (BookingID, StudentID, RoomType, CheckInDate, CheckOutDate, Notes, Price, BookingDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", $booking_id, $student_id, $room_type, $check_in, $check_out, $special_request, $price, $bookingDate);
  
    if ($stmt->execute()) {
      $_SESSION['BookingID'] = $booking_id;
      header("Location: guestdetails.php?BookingID=" . urlencode($booking_id));
      exit();
    } else {
      $confirmation = "<p style='color: red;'>Booking failed: " . $stmt->error . "</p>";
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    background: #f8f8f8;
  }
  .top-bar {
    background-color: #018000;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 20px;
    font-size: 14px;
  }
  .logo-container {
    display: flex;
    align-items: center;
  }
  .cvsu-logo {
    height: 40px;
    margin-right: 10px;
  }
  .university-info strong {
    display: block;
  }
  .top-icons {
    display: flex;
    gap: 15px;
  }
  .icon {
    font-size: 18px;
    cursor: pointer;
  }
  .main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background-color: white;
    border-bottom: 1px solid #ccc;
    flex-wrap: wrap;
  }
  .brand {
    display: flex;
    align-items: center;
  }
  .villa-logo {
    height: 60px;
    margin-right: 15px;
  }
  .brand-text h1 {
    color: #018000;
    font-size: 24px;
    font-weight: bold;
  }
  .brand-text small {
    color: #666;
    font-size: 12px;
  }
  .nav-links {
    display: flex;
    align-items: center;
    gap: 25px;
    position: relative;
  }
  .nav-links a {
    text-decoration: none;
    color: #018000;
    font-weight: bold;
    font-size: 16px;
    position: relative;
  }
  .container_booking {
    max-width: 550px;
    margin: 30px auto;
    background: white;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 8px;
  }
  .form-group {
    margin-bottom: 15px;
  }
  label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
  }
  input, select, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  .btn {
    background-color: #0b3d2e;
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: inline-block;
    margin-top: 10px;
    text-decoration: none;
  }
  .btn:hover {
    background-color: #096649;
  }
  .confirmation {
    margin-top: 25px;
    background-color: #e6f8ec;
    padding: 20px;
    border-left: 6px solid #34a56f;
    border-radius: 6px;
  }
  .readonly-field {
    background-color: #f2f2f2;
    border: 1px solid #ccc;
    color: #333;
    cursor: not-allowed;
  }
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
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Log In</a>
    <?php endif; ?>
  </nav>
  </header>

<!-- Booking Form -->
<div class="container_booking">
  <h2>Book a Room</h2> <br>
  <p>Fill out the form below to book your room.</p><br>
  
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

  <!-- Booking ID -->
  <div class="form-group">
    <label for="booking-id">Booking ID:</label>
    <input type="text" id="booking-id" name="booking_id" value="<?php echo $generatedBookingID; ?>" readonly />
  </div>

  <!-- Booking Date (hidden for backend) -->
  <input type="hidden" name="BookingDate" value="<?php echo $bookingDate; ?>" />

  <!-- Booking Date (visible for user) -->
  <div class="form-group">
    <label for="BookingDate">Booking Date:</label>
    <input type="text" id="BookingDate" value="<?php echo $bookingDate; ?>" readonly />
  </div>

  <!-- Room Type (readonly text, no select/arrow) -->
  <div class="form-group">
    <label for="RoomType">Room Type:</label>
    <input type="text" id="RoomType" name="RoomType" value="<?php
      if (isset($_POST['RoomType'])) {
        echo htmlspecialchars(ucfirst(strtolower($_POST['RoomType'])));
      } elseif ($room_type) {
        echo htmlspecialchars(ucfirst($room_type));
      } else {
        echo '';
      }
    ?>" readonly class="readonly-field" required />
  </div>

  <!-- Check-in Date & Time -->
  <div class="form-group">
    <label for="CheckInDate">Check-in Date & Time:</label>
    <input type="datetime-local" id="checkin" name="CheckInDate" required value="<?php
      // Priority: POST > GET/SESSION combined datetime > individual date/time
      if (isset($_POST['CheckInDate'])) {
        echo htmlspecialchars($_POST['CheckInDate']);
      } elseif ($checkin_datetime) {
        echo htmlspecialchars($checkin_datetime);
      } else {
        echo '';
      }
    ?>" />
  </div>

  <!-- Check-out Date & Time -->
  <div class="form-group">
    <label for="CheckOutDate">Check-out Date & Time:</label>
    <input type="datetime-local" id="CheckOutDate" name="CheckOutDate" required value="<?php
      // Priority: POST > GET/SESSION combined datetime > individual date/time
      if (isset($_POST['CheckOutDate'])) {
        echo htmlspecialchars($_POST['CheckOutDate']);
      } elseif ($checkout_datetime) {
        echo htmlspecialchars($checkout_datetime);
      } else {
        echo '';
      }
    ?>" />
  </div>

  <!-- Notes -->
  <div class="form-group">
    <label for="Notes">Special Request / Notes:</label>
    <textarea id="Notes" name="Notes" rows="3"><?php echo isset($_POST['Notes']) ? htmlspecialchars($_POST['Notes']) : ''; ?></textarea>
  </div>

  <!-- Price (hidden for backend, visible for user) -->
  <input type="hidden" name="Price" value="<?php echo $total_price; ?>" />
  <div class="form-group">
    <label for="Price">Total Price:</label>
    <input type="text" id="Price" value="<?php
    if ($total_price) {
      echo "₱" . number_format($total_price);
    } elseif ($room_type) {
      $price_map = [
        'standard' => 2000,
        'deluxe' => 10000,
        'suite' => 5000
      ];
      echo "₱" . number_format($price_map[$room_type]);
    } else {
      echo '';
    }
    ?>" readonly class="readonly-field" />
  </div>

  <!-- Book Now Button -->
  <button type="submit" class="btn">Book Now</button>
  </form>

  <?php echo $confirmation; ?>
</div>

</body>
</html>

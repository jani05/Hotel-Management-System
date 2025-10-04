<?php
include 'connections.php';
$confirmation = "";
$generatedReservationID = "";
$reservationDate = date("Y-m-d");

// Generate Reservation ID on page load if not submitting
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $date_code = date("Ymd");
    $result = $conn->query("SELECT COUNT(*) AS total FROM reservations WHERE DATE(PCheckInDate) = CURDATE()");
    $row = $result->fetch_assoc();
    $count_today = $row['total'] + 1;
    $generatedReservationID = "RS-" . $date_code . "-" . str_pad($count_today, 4, '0', STR_PAD_LEFT);
}

// Accept booking parameters from GET/SESSION
$room_type = $_GET['room'] ?? $_SESSION['selected_room_type'] ?? '';
$checkin_date = $_GET['checkin'] ?? $_SESSION['checkin_date'] ?? '';
$checkout_date = $_GET['checkout'] ?? $_SESSION['checkout_date'] ?? '';
$checkin_time = $_GET['checkin_time'] ?? $_SESSION['checkin_time'] ?? '14:00';
$checkout_time = $_GET['checkout_time'] ?? $_SESSION['checkout_time'] ?? '12:00';
$total_price = $_GET['price'] ?? $_SESSION['total_price'] ?? '';
$reservation_fee = $_GET['rf'] ?? $_SESSION['reservation_fee'] ?? '';
$duration = $_GET['duration'] ?? $_SESSION['duration'] ?? '';
$adults = $_GET['adults'] ?? $_SESSION['adults'] ?? 1;
$children = $_GET['children'] ?? $_SESSION['children'] ?? 0;

// Reservation submission logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['RoomType']) || empty($_POST['CheckInDate']) || empty($_POST['CheckOutDate'])) {
        $confirmation = "<p style='color: red;'>All fields are required.</p>";
    } else {
        $room_type = $conn->real_escape_string($_POST['RoomType']);
        $_SESSION['selected_room_type'] = $room_type;
        $check_in = $_POST['CheckInDate'];
        $check_out = $_POST['CheckOutDate'];
        $special_request = $conn->real_escape_string($_POST['Notes']);
        $reservation_id = $_POST['reservation_id'];
        $reservationDate = (!empty($_POST['ReservationDate']) && $_POST['ReservationDate'] !== '0000-00-00')
            ? $_POST['ReservationDate']
            : date('Y-m-d');
        // Reservation fee is 30% of total price
        $reservation_fee = 0;
        if (!empty($total_price) && is_numeric($total_price)) {
            $reservation_fee = round($total_price * 0.3);
        }
        // Calculate duration (in hours)
        $duration = '';
        if (!empty($check_in) && !empty($check_out)) {
            $dt1 = new DateTime($check_in);
            $dt2 = new DateTime($check_out);
            $interval = $dt1->diff($dt2);
            $hours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
            if ($hours < 24) {
                $duration = round($hours, 2) . ' hour(s)';
            } else {
                $duration = $interval->days . ' day(s)';
            }
        }
        // Insert into reservation table
        $sql = "INSERT INTO reservations (ReservationID, ReservationDate, RoomType, PCheckInDate, PCheckOutDate, Notes, ReservationFee)
                VALUES ('$reservation_id', '$reservationDate', '$room_type', '$check_in', '$check_out', '$special_request', '$reservation_fee')";
        if ($conn->query($sql)) {
            // Save to session for guestdetails/paymentdetails
            $_SESSION['reservation_id'] = $reservation_id;
            $_SESSION['reservation_date'] = $reservationDate;
            $_SESSION['selected_room_type'] = $room_type;
            $_SESSION['checkin_date'] = $check_in;
            $_SESSION['checkout_date'] = $check_out;
            $_SESSION['special_request'] = $special_request;
            $_SESSION['reservation_fee'] = $reservation_fee;
            $_SESSION['duration'] = $duration;
            $_SESSION['adults'] = $adults;
            $_SESSION['children'] = $children;
            // Redirect to guestdetails.php
            header("Location: guestdetails.php?ReservationID=" . urlencode($reservation_id));
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
  <title>Villa Valore Hotel - Reservation</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
    <a href="login.php">Log In</a>
  </nav>
</header>

<!-- Reservation Form -->
<div class="container_booking">
  <h2>Reserve a Room</h2>

  <form method="POST">

    <!-- Reservation ID -->
    <div class="form-group">
      <label for="reservation-id">Reservation ID:</label>
      <input type="text" id="reservation-id" name="reservation_id" value="<?php echo $generatedReservationID; ?>" readonly />
    </div>

    <!-- Reservation Date (displayed, not editable) -->
    <div class="form-group">
      <label for="ReservationDate">Reservation Date:</label>
      <input type="text" id="ReservationDate" value="<?php echo $reservationDate; ?>" readonly />
    </div>

    <!-- Room Type -->
    <div class="form-group">
      <label for="RoomType">Room Type:</label>
      <select id="RoomType" name="RoomType" required>
        <option value="">Select a Room Type</option>
        <option value="standard" <?php if($room_type=='standard') echo 'selected'; ?>>Standard Room</option>
        <option value="deluxe" <?php if($room_type=='deluxe') echo 'selected'; ?>>Deluxe Room</option>
        <option value="suite" <?php if($room_type=='suite') echo 'selected'; ?>>Suite Room</option>
      </select>
    </div>

    <!-- Check-in -->
    <div class="form-group">
      <label for="CheckInDate">Check-in Date & Time:</label>
      <input type="datetime-local" id="CheckInDate" name="CheckInDate" required value="<?php echo htmlspecialchars($checkin_date . 'T' . $checkin_time); ?>" />
    </div>

    <!-- Check-out -->
    <div class="form-group">
      <label for="CheckOutDate">Check-out Date & Time:</label>
      <input type="datetime-local" id="CheckOutDate" name="CheckOutDate" required value="<?php echo htmlspecialchars($checkout_date . 'T' . $checkout_time); ?>" />
    </div>

    <!-- Notes -->
    <div class="form-group">
      <label for="Notes">Special Request / Notes:</label>
      <textarea id="Notes" name="Notes" rows="3"></textarea>
    </div>

    <!-- Reservation Fee -->
    <div class="form-group">
      <label for="ReservationFee">Reservation Fee:</label>
      <input type="text" id="ReservationFee" readonly value="<?php echo $reservation_fee ? '₱'.number_format($reservation_fee) : ''; ?>" />
      <small style="color:#018000;display:block;margin-top:4px;">Reservation Fee is 30% of the Total Price.</small>
    </div>

    <button type="submit" class="btn">Confirm Reservation</button>
  </form>

  <?php echo $confirmation; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const feeDisplay = document.getElementById("ReservationFee");
  const totalPriceInput = document.getElementById("TotalPrice");
  let totalPrice = 0;
  if (totalPriceInput) {
    totalPrice = parseFloat(totalPriceInput.value) || 0;
  } else if (typeof total_price !== 'undefined') {
    totalPrice = parseFloat(total_price) || 0;
  }
  if (totalPrice > 0) {
    feeDisplay.value = "₱" + Math.round(totalPrice * 0.3).toLocaleString();
  }
});
document.getElementById("RoomType").addEventListener("change", function () {
  const feeDisplay = document.getElementById("ReservationFee");
  const roomType = this.value;
  const fees = {
    standard: 500,
    deluxe: 800,
    suite: 1000
  };
  if (fees[roomType]) {
    feeDisplay.value = "₱" + fees[roomType].toLocaleString();
  } else {
    feeDisplay.value = "";
  }
});
</script>

</body>
</html>

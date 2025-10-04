<?php
// ============================================================================
// DATABASE CONNECTION
// ============================================================================
$host = "localhost";
$user = "root";
$password = "";
$dbname = "hotel_reservation_systemdb";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ============================================================================
// CALENDAR LOGIC
// ============================================================================
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);

// ============================================================================
// FETCH BOOKING DATA
// ============================================================================
$bookings = [];
$firstDateOfMonth = "$year-$month-01";
$lastDateOfMonth = "$year-$month-$daysInMonth";

// 1. Auto-update room status to 'Available' for finished stays
$today = date('Y-m-d');
$autoUpdateSql = "UPDATE room r
    JOIN booking b ON r.RoomNumber = b.RoomNumber
    SET r.RoomStatus = 'Available'
    WHERE b.CheckOutDate < '$today' AND (b.BookingStatus = 'Confirmed' OR b.BookingStatus = 'Completed') AND r.RoomStatus != 'Available'";
$conn->query($autoUpdateSql);

$sql = "SELECT b.*,b.StudentID AS StudentID, 
            s.FirstName AS FirstName, 
            s.LastName AS LastName, 
            s.Gender AS Gender, 
            s.PhoneNumber AS PhoneNumber, 
            s.Address AS Address, 
            s.Email AS Email, 
            s.Nationality AS Nationality, 
            s.BirthDate AS BirthDate, 
            s.StudentID AS StudentIDNum
        FROM booking b
        LEFT JOIN student s ON b.StudentID = s.StudentID
        WHERE 
            (b.CheckInDate <= '$lastDateOfMonth' AND b.CheckOutDate >= '$firstDateOfMonth') 
        AND b.BookingStatus NOT IN ('Cancelled')";

$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        // Guarantee StudentID is always set from the booking table
        $row['StudentID'] = isset($row['StudentID']) && $row['StudentID'] !== '' ? $row['StudentID'] : (isset($row['StudentIDNum']) && $row['StudentIDNum'] !== '' ? $row['StudentIDNum'] : '');
        $row['ReservationID'] = isset($row['ReservationID']) ? $row['ReservationID'] : '';
        $row['Gender'] = isset($row['Gender']) ? $row['Gender'] : '';
        $row['PhoneNumber'] = isset($row['PhoneNumber']) ? $row['PhoneNumber'] : '';
        $row['Address'] = isset($row['Address']) ? $row['Address'] : '';
        $row['Email'] = isset($row['Email']) ? $row['Email'] : '';
        $row['Nationality'] = isset($row['Nationality']) ? $row['Nationality'] : '';
        $row['BirthDate'] = isset($row['BirthDate']) ? $row['BirthDate'] : '';
        $bookings[] = $row;
    }
}

// Collect ReservationIDs that already have a booking
$bookedReservationIds = [];
foreach ($bookings as $b) {
    if (!empty($b['ReservationID'])) {
        $bookedReservationIds[] = $b['ReservationID'];
    }
}

// Fetch reservation data and merge with bookings for calendar display
$reservationSql = "SELECT * FROM reservations WHERE Status != 'Cancelled'";
$reservationResult = $conn->query($reservationSql);
if ($reservationResult) {
    while ($row = $reservationResult->fetch_assoc()) {
        // Only add reservations that do NOT already have a booking
        if (!in_array($row['ReservationID'], $bookedReservationIds)) {
            $studentIdVal = isset($row['StudentID']) && !empty($row['StudentID']) ? $row['StudentID'] : (isset($row['StudentIDNum']) && !empty($row['StudentIDNum']) ? $row['StudentIDNum'] : '');
            $bookings[] = [
                'BookingID' => '',
                'ReservationID' => $row['ReservationID'],
                'StudentID' => $studentIdVal,
                'RoomNumber' => $row['RoomNumber'],
                'RoomType' => $row['RoomType'],
                'CheckInDate' => $row['PCheckInDate'],
                'CheckOutDate' => $row['PCheckOutDate'],
                'BookingDate' => null,
                'BookingStatus' => $row['Status'],
                'Notes' => '',
                'RoomStatus' => 'Reserved', // Always yellow for reservations
                'FirstName' => $row['GuestName'],
                'LastName' => '',
                'Gender' => '',
                'PhoneNumber' => '',
                'Address' => '',
                'Email' => '',
                'Nationality' => '',
                'BirthDate' => '',
                'StudentIDNum' => $studentIdVal,
            ];
        }
    }
}

function getBookingsForDate($date, $bookings) {
    $bookingsOnDate = [];
    foreach ($bookings as $booking) {
        $checkIn = new DateTime($booking['CheckInDate']);
        $checkOut = new DateTime($booking['CheckOutDate']);
        $current = new DateTime($date);
        
        // Check if the date is within the booking range (inclusive of check-in, exclusive of check-out)
        if ($current >= $checkIn && $current < $checkOut) {
            $bookingsOnDate[] = $booking;
        }
    }
    return $bookingsOnDate;
}

// ============================================================================
// AJAX HANDLER FOR ALL BOOKINGS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all_bookings') {
    $search = $_GET['search'] ?? '';
    $roomType = $_GET['roomType'] ?? '';
    $roomNumber = $_GET['roomNumber'] ?? '';
    $sql = "SELECT b.BookingID, b.BookingCode, b.RoomNumber, b.RoomType, b.BookingStatus, b.CheckInDate, b.CheckOutDate, b.BookingDate, b.StudentID, s.FirstName, s.LastName FROM booking b LEFT JOIN student s ON b.StudentID = s.StudentID WHERE 1";
    if ($search) {
      $search = $conn->real_escape_string($search);
      $sql .= " AND (b.BookingCode LIKE '%$search%' OR b.BookingID LIKE '%$search%' OR s.FirstName LIKE '%$search%' OR s.LastName LIKE '%$search%')";
    }
    if ($roomType) {
      $roomType = $conn->real_escape_string($roomType);
      $sql .= " AND b.RoomType = '$roomType'";
    }
    if ($roomNumber) {
      $roomNumber = $conn->real_escape_string($roomNumber);
      $sql .= " AND b.RoomNumber = '$roomNumber'";
    }
    $sql .= " ORDER BY b.BookingDate DESC";
    $result = $conn->query($sql);
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
      $row['GuestName'] = trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''));
      $row['StudentIDNum'] = $row['StudentID']; // For compatibility with JS
      $bookings[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode(['bookings' => $bookings]);
    exit;
}

// ============================================================================
// AJAX HANDLER: Return available rooms by type for dropdown (for modal)
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['roomType'])) {
    header('Content-Type: application/json');
    $roomType = $conn->real_escape_string($_GET['roomType']);
    $checkIn = isset($_GET['checkIn']) ? $conn->real_escape_string($_GET['checkIn']) : null;
    $checkOut = isset($_GET['checkOut']) ? $conn->real_escape_string($_GET['checkOut']) : null;

    $rooms = [];
    // Debug: Log the values being used
    error_log('AJAX roomType: ' . $roomType);
    error_log('AJAX checkIn: ' . $checkIn);
    error_log('AJAX checkOut: ' . $checkOut);
    if ($checkIn && $checkOut) {
        $sql = "SELECT RoomNumber FROM room
            WHERE RoomType = '$roomType'
              AND RoomStatus IN ('Available', 'Maintenance', 'Cleaning')
              AND RoomNumber NOT IN (
                  SELECT CAST(RoomNumber AS CHAR) FROM booking
                  WHERE BookingStatus NOT IN ('Cancelled', 'Completed')
                    AND (
                        (DATE(CheckInDate) < '$checkOut' AND DATE(CheckOutDate) > '$checkIn')
                    )
              )
              AND RoomNumber NOT IN (
                  SELECT CAST(RoomNumber AS CHAR) FROM reservations
                  WHERE Status != 'Cancelled'
                    AND (
                        (PCheckInDate < '$checkOut' AND PCheckOutDate > '$checkIn')
                    )
              )
            ORDER BY RoomNumber
        ";
        error_log('ROOM AVAILABILITY SQL: ' . $sql);
    } else {
        $sql = "SELECT RoomNumber FROM room WHERE RoomType = '$roomType' AND RoomStatus IN ('Available', 'Maintenance', 'Cleaning') ORDER BY RoomNumber";
        error_log('ROOM AVAILABILITY SQL (no dates): ' . $sql);
    }
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
    }
    if (empty($rooms)) {
        echo json_encode(['noRooms' => true, 'message' => 'No rooms available for the selected dates.']);
        exit;
    }
    echo json_encode($rooms);
    exit;
}

// ============================================================================
// AJAX HANDLER FOR WALK-IN BOOKING
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_walkin') {
    // Step 2: Guest Information
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $studentIdNum = $_POST['studentId'] ?? '';
    
    // Step 1: Booking Details
    $checkIn = $_POST['checkInDate'] ?? '';
    $checkOut = $_POST['checkOutDate'] ?? '';
    $bookingDate = $_POST['bookingDate'] ?? '';
    $bookingStatus = $_POST['bookingStatus'] ?? 'Pending';
    $roomType = $_POST['roomType'] ?? '';
    $specialRequest = $_POST['specialRequest'] ?? '';
    $roomNumber = $_POST['roomNumber'] ?? '';

    // Validate required fields
    if (empty($roomNumber) || empty($checkIn) || empty($checkOut)) {
        echo json_encode(['success' => false, 'message' => 'Please select a room and provide check-in/check-out dates.']);
        exit;
    }

    // Check if the selected room is available for the given dates
    $roomAvailableSql = "SELECT RoomNumber FROM room WHERE RoomNumber = ? AND RoomType = ? AND RoomStatus = 'Available'";
    $stmt = $conn->prepare($roomAvailableSql);
    $stmt->bind_param('is', $roomNumber, $roomType);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected room is not available.']);
        exit;
    }

    // Check for overlapping bookings for this room
    $overlapSql = "SELECT 1 FROM booking WHERE RoomNumber = ? AND BookingStatus NOT IN ('Cancelled', 'Completed') AND ((CheckInDate < ? AND CheckOutDate > ?) OR (CheckInDate < ? AND CheckOutDate > ?) OR (CheckInDate >= ? AND CheckOutDate <= ?))";
    $stmt_overlap = $conn->prepare($overlapSql);
    $stmt_overlap->bind_param('issssss', $roomNumber, $checkOut, $checkIn, $checkIn, $checkOut, $checkIn, $checkOut);
    $stmt_overlap->execute();
    $overlapResult = $stmt_overlap->get_result();
    if ($overlapResult && $overlapResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Selected room is already booked for the chosen dates.']);
        exit;
    }

    // Check for overlapping reservations for this room
    $reservationOverlapSql = "SELECT 1 FROM reservations WHERE RoomNumber = ? AND Status != 'Cancelled' AND (PCheckInDate < ? AND PCheckOutDate > ?)";
    $stmt_reservation_overlap = $conn->prepare($reservationOverlapSql);
    $stmt_reservation_overlap->bind_param('sss', $roomNumber, $checkOut, $checkIn);
    $stmt_reservation_overlap->execute();
    $reservationOverlapResult = $stmt_reservation_overlap->get_result();
    if ($reservationOverlapResult && $reservationOverlapResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Selected room is already reserved for the chosen dates.']);
        exit;
    }

    // Create a new student record for the guest
    $studentSql = "INSERT INTO student (FirstName, LastName, Gender, PhoneNumber, Address, Email, Nationality, BirthDate, StudentID) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_student = $conn->prepare($studentSql);
    if (!$stmt_student) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare guest record statement: ' . $conn->error]);
        exit;
    }
    $stmt_student->bind_param("sssssssss", $firstName, $lastName, $gender, $phone, $address, $email, $nationality, $birthdate, $studentIdNum);
    
    if ($stmt_student->execute()) {
        $studentId = $conn->insert_id;

        $roomStatus = (strtolower($bookingStatus) === 'pending' || strtolower($bookingStatus) === 'reserved') ? 'Reserved' : 'Booked';
        $bookingSql = "INSERT INTO booking (StudentID, RoomNumber, RoomType, CheckInDate, CheckOutDate, BookingDate, BookingStatus, Notes, RoomStatus) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_booking = $conn->prepare($bookingSql);
        if (!$stmt_booking) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare booking statement: ' . $conn->error]);
            exit;
        }
        $stmt_booking->bind_param("iisssssss", $studentId, $roomNumber, $roomType, $checkIn, $checkOut, $bookingDate, $bookingStatus, $specialRequest, $roomStatus);
        
        if ($stmt_booking->execute()) {
            // Update room status
            $updateRoomSql = "UPDATE room SET RoomStatus = ? WHERE RoomNumber = ?";
            $stmt_update = $conn->prepare($updateRoomSql);
            $stmt_update->bind_param("si", $roomStatus, $roomNumber);
            $stmt_update->execute();
            echo json_encode(['success' => true, 'message' => 'Booking created successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create booking: ' . $stmt_booking->error]);
        }
    } else {
         echo json_encode(['success' => false, 'message' => 'Failed to create guest record: ' . $stmt_student->error]);
    }
    exit;
}

// ============================================================================
// AJAX HANDLER FOR BOOKING UPDATE
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_booking') {
    // Ensure no output before JSON response
    ob_clean();
    
    // Validate required fields
    $bookingId = $_POST['detailsBookingId'] ?? '';
    $studentId = $_POST['detailsStudentIdDisplay'] ?? '';
    $roomNumber = $_POST['detailsRoomNumber'] ?? '';
    $bookingStatus = $_POST['detailsBookingStatus'] ?? '';
    $checkIn = $_POST['detailsCheckIn'] ?? '';
    $checkOut = $_POST['detailsCheckOut'] ?? '';
    $firstName = $_POST['detailsFirstName'] ?? '';
    $lastName = $_POST['detailsLastName'] ?? '';
    $email = $_POST['detailsEmail'] ?? '';
    $phone = $_POST['detailsPhone'] ?? '';
    $notes = $_POST['detailsNotes'] ?? '';
    
    // Check if we have either bookingId or studentId
    if (empty($bookingId) && empty($studentId)) {
        echo json_encode(['success' => false, 'message' => 'Missing booking or student information.']);
        exit;
    }
    
    // Validate other required fields
    if (empty($roomNumber) || empty($bookingStatus) || empty($checkIn) || empty($checkOut) || empty($firstName) || empty($lastName)) {
        echo json_encode(['success' => false, 'message' => 'Please fill out all required fields.']);
        exit;
    }
    
    // Validate dates
    if (strtotime($checkOut) <= strtotime($checkIn)) {
        echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date.']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        if (!empty($bookingId)) {
            // Update existing booking
            $sql_booking = "UPDATE booking SET RoomNumber = ?, BookingStatus = ?, CheckInDate = ?, CheckOutDate = ?, Notes = ? WHERE BookingID = ?";
            $stmt_booking = $conn->prepare($sql_booking);
            $stmt_booking->bind_param("sssssi", $roomNumber, $bookingStatus, $checkIn, $checkOut, $notes, $bookingId);
            $booking_success = $stmt_booking->execute();
            
            if (!$booking_success) {
                throw new Exception("Failed to update booking: " . $stmt_booking->error);
            }
            
            // Get the StudentID from the booking
            $student_query = "SELECT StudentID FROM booking WHERE BookingID = ?";
            $stmt_student_query = $conn->prepare($student_query);
            $stmt_student_query->bind_param("i", $bookingId);
            $stmt_student_query->execute();
            $student_result = $stmt_student_query->get_result();
            if ($student_row = $student_result->fetch_assoc()) {
                $studentId = $student_row['StudentID'];
            }
        }
        
        // Update student information if we have a StudentID
        if (!empty($studentId)) {
            $sql_student = "UPDATE student SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ? WHERE StudentID = ?";
            $stmt_student = $conn->prepare($sql_student);
            $stmt_student->bind_param("ssssi", $firstName, $lastName, $email, $phone, $studentId);
            $student_success = $stmt_student->execute();
            
            if (!$student_success) {
                throw new Exception("Failed to update student information: " . $stmt_student->error);
            }
        }
        
        // If both queries are successful, commit the transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Booking and guest details updated successfully!']);
        
    } catch (Exception $exception) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to update details: ' . $exception->getMessage()]);
    }
    exit;
}

// Add AJAX handler for early checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'early_checkout') {
    $bookingId = $_POST['bookingId'] ?? 0;
    $roomNumber = $_POST['roomNumber'] ?? '';
    if ($bookingId > 0 && !empty($roomNumber)) {
        $today = date('Y-m-d');
        $sql = "UPDATE booking SET CheckOutDate = ?, BookingStatus = 'Completed' WHERE BookingID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $today, $bookingId);
        $success = $stmt->execute();
        if ($success) {
            $sql2 = "UPDATE room SET RoomStatus = 'Available' WHERE RoomNumber = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('i', $roomNumber);
            $stmt2->execute();
            echo json_encode(['success' => true, 'message' => 'Guest checked out early. Room is now available.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to process early checkout.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid booking or room.']);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="booking_style.css">
    <style>
        .styled-select {
            width: 100%;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #f9f9f9;
            font-size: 1rem;
            color: #333;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            transition: border 0.2s;
        }
        .styled-select:focus {
            border: 1.5px solid #007bff;
            outline: none;
            background: #fff;
        }
    </style>
    <script>
        window.allBookingsData = <?php echo json_encode($bookings); ?>;
    </script>
</head>
<body>
    <div class="sidebar">
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="sidebar-logo">
        <h4 class="sidebar-title">Villa Valore</h4>
        <div class="nav-section">
            <a class="nav-link" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
            <a class="nav-link active" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
            <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
        </div>
        <div class="nav-section">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
        </div>
    </div>

    <div class="main-content">
        <div class="calendar-container">
            <div class="booking-legend">
                <span class="legend-item"><span class="legend-dot legend-booked"></span>Booked</span>
                <span class="legend-item"><span class="legend-dot legend-reserved"></span>Reserved</span>
                <span class="legend-item"><span class="legend-dot legend-completed"></span>Completed</span>
            </div>
            <div class="calendar-header">
                <h1>Booking Schedule</h1>
                <div class="calendar-nav enhanced-calendar-nav">
                    <a href="?month=<?php echo $month == 1 ? 12 : $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-left"></i></a>
                    <span class="calendar-month-year"><?php echo date('F Y', $firstDay); ?></span>
                    <a href="?month=<?php echo $month == 12 ? 1 : $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>" class="calendar-nav-btn"><i class="fas fa-chevron-right"></i></a>
                    <div class="calendar-picker">
                        <select id="monthSelect">
                            <?php
                            for ($m = 1; $m <= 12; $m++) {
                                $selected = $m == $month ? 'selected' : '';
                                echo "<option value=\"$m\" $selected>" . date('F', mktime(0,0,0,$m,1)) . "</option>";
                            }
                            ?>
                        </select>
                        <input type="number" id="yearInput" value="<?php echo $year; ?>" min="2000" max="2100">
                        <button id="goToDateBtn" class="calendar-go-btn">Go</button>
                    </div>
                </div>
            </div>

            <div class="controls-bar">
                <button class="control-btn" id="filterBtn">Search & Filter</button>
                <button class="control-btn walk-in-btn" id="walkInBtn">Walk-in booking</button>
                <button class="control-btn" id="openAllBookingsModal">View All Bookings</button>
            </div>

            <table class="calendar-grid">
                <thead>
                    <tr>
                        <th>Sunday</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php
                        // Empty cells for days before the first day of the month
                        for ($i = 0; $i < $dayOfWeek; $i++) {
                            echo "<td></td>";
                        }
                        
                        $currentDay = 1;
                        while ($currentDay <= $daysInMonth) {
                            if ($dayOfWeek == 7) {
                                $dayOfWeek = 0;
                                echo "</tr><tr>";
                            }
                            
                            $dateStr = "$year-$month-$currentDay";
                            $dayBookings = getBookingsForDate($dateStr, $bookings);

                            echo "<td><div class='calendar-day'>{$currentDay}</div>";
                            
                            if (!empty($dayBookings)) {
                                echo "<div class='bookings-container'>";
                                foreach($dayBookings as $booking) {
                                    $roomStatus = strtolower($booking['RoomStatus']);
                                    $bookingStatus = strtolower($booking['BookingStatus']);
                                    if (empty($roomStatus) || !in_array($roomStatus, ['booked', 'reserved', 'maintenance'])) {
                                        $roomStatus = 'booked';
                                    }
                                    $statusClass = 'status-' . $roomStatus;
                                    $confirmedClass = ($bookingStatus === 'confirmed') ? ' status-confirmed' : '';
                                    $completedClass = ($bookingStatus === 'completed') ? ' status-completed' : '';
                                    $guestName = trim(($booking['FirstName'] ?? '') . ' ' . ($booking['LastName'] ?? ''));
                                    if(empty($guestName)) { $guestName = 'N/A'; }
                                    $isReservation = empty($booking['BookingID']) && !empty($booking['ReservationID']);
                                    $dataType = $isReservation ? 'reservation' : 'booking';
                                    $dataId = $isReservation ? $booking['ReservationID'] : $booking['BookingID'];
                                    echo "<div class='booking-bar {$statusClass}{$confirmedClass}{$completedClass}' 
                                             data-type='{$dataType}'
                                             data-id='{$dataId}'
                                             data-booking-id='{$booking['BookingID']}'
                                             data-reservation-id='{$booking['ReservationID']}'
                                             data-student-id='{$booking['StudentID']}'
                                             data-room-number='{$booking['RoomNumber']}' 
                                             data-guest-name='{$guestName}' 
                                             data-status='" . $roomStatus . "'
                                             data-check-in='" . date('Y-m-d', strtotime($booking['CheckInDate'])) . "'
                                             data-check-out='" . date('Y-m-d', strtotime($booking['CheckOutDate'])) . "'
                                             data-booking-status='{$booking['BookingStatus']}'
                                             data-notes='" . htmlspecialchars($booking['Notes']) . "'
                                             data-first-name='" . htmlspecialchars($booking['FirstName']) . "'
                                             data-last-name='" . htmlspecialchars($booking['LastName']) . "'
                                             data-phone='" . htmlspecialchars($booking['PhoneNumber']) . "'
                                             data-email='" . htmlspecialchars($booking['Email']) . "'>
                                             Room {$booking['RoomNumber']} ({$guestName})
                                          </div>";
                                }
                                echo "</div>";
                            }
                            
                            echo "</td>";
                            
                            $currentDay++;
                            $dayOfWeek++;
                        }
                        
                        // Empty cells for days after the last day of the month
                        while ($dayOfWeek > 0 && $dayOfWeek < 7) {
                            echo "<td></td>";
                            $dayOfWeek++;
                        }
                        ?>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="detailsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Booking & Guest Details</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form id="detailsForm">
                    <input type="hidden" name="bookingId" id="detailsBookingId">
                    <input type="hidden" id="detailsStudentIdDisplay" name="detailsStudentIdDisplay">
                    <input type="hidden" id="detailsStudentIdHidden" name="detailsStudentIdHidden">
                    
                    <h4>Booking Details</h4>
                    <div class="booking-details-grid">
                        <div class="form-group">
                            <label for="detailsRoomNumber">Room</label>
                            <select id="detailsRoomNumber" name="roomNumber" required>
                                <option value="">Select Room</option>
                                <?php
                                $roomRes = $conn->query("SELECT RoomNumber FROM room ORDER BY RoomNumber");
                                while ($room = $roomRes->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($room['RoomNumber']) . '">' . htmlspecialchars($room['RoomNumber']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="detailsBookingStatus">Booking Status</label>
                            <select id="detailsBookingStatus" name="bookingStatus">
                                <option value="Pending">Pending</option>
                                <option value="Confirmed">Confirmed</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="detailsBookingIdDisplay">Booking ID</label>
                            <input type="text" id="detailsBookingIdDisplay" readonly style="background:#f3f3f3; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label for="detailsReservationIdDisplay">Reservation ID</label>
                            <input type="text" id="detailsReservationIdDisplay" readonly style="background:#f3f3f3; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label for="detailsStudentIdDisplay">Student ID</label>
                            <input type="text" id="detailsStudentIdDisplay" name="detailsStudentIdDisplay" readonly style="background:#f3f3f3; color:#222;">
                        </div>
                        <div class="form-group">
                            <label for="detailsCheckIn">Check In</label>
                            <input type="date" id="detailsCheckIn" name="checkInDate">
                        </div>
                        <div class="form-group">
                            <label for="detailsCheckOut">Check Out</label>
                            <input type="date" id="detailsCheckOut" name="checkOutDate">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h4>Guest Details</h4>
                     <div class="booking-details-grid">
                        <div class="form-group">
                            <label for="detailsFirstName">First Name</label>
                            <input type="text" id="detailsFirstName" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label for="detailsLastName">Last Name</label>
                            <input type="text" id="detailsLastName" name="lastName" required>
                        </div>
                        <div class="form-group">
                            <label for="detailsEmail">Email</label>
                            <input type="email" id="detailsEmail" name="email">
                        </div>
                        <div class="form-group">
                            <label for="detailsPhone">Phone</label>
                            <input type="tel" id="detailsPhone" name="phone">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="detailsNotes">Notes</label>
                        <textarea id="detailsNotes" name="notes"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="control-btn" id="detailsCancelBtn">Close</button>
                        <button type="submit" class="control-btn walk-in-btn">Save Changes</button>
                        <button type="button" class="control-btn" id="earlyCheckoutBtn" style="display:none; background:#e74c3c; color:#fff; border:2px solid #e74c3c; font-weight:bold;">Check Out Now</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="filterModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Search & Filter Bookings</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <form id="filterForm">
                     <div class="form-group">
                        <label for="searchInputModal">Search by Guest, Room #, or Status</label>
                        <div class="search-wrapper-modal">
                             <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInputModal" placeholder="e.g., John Doe, 101, booked...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="filterStatus">Filter by Status</label>
                        <select id="filterStatus" name="filterStatus">
                            <option value="">All</option>
                            <option value="booked">Booked</option>
                            <option value="reserved">Reserved</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                     <div id="filter-results-container">
                        <label>Matching Results</label>
                        <div id="filter-results">
                            <div class="result-item empty">Enter search criteria to see results.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="control-btn" id="clearFilterBtn">Clear</button>
                        <button type="submit" class="control-btn walk-in-btn">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- All Bookings Modal -->
        <div id="allBookingsModal" class="modal">
          <div class="modal-content">
            <span class="close-btn" id="closeAllBookingsModal">&times;</span>
            <h2>All Bookings</h2>
            <div class="search-filter-bar">
              <input type="text" id="bookingSearchInput" placeholder="Search by Booking ID or Guest Name...">
              <select id="bookingRoomTypeFilter">
                <option value="">All Room Types</option>
                <option value="Standard">Standard</option>
                <option value="Deluxe">Deluxe</option>
                <option value="Suite">Suite</option>
              </select>
              <select id="bookingRoomNumberFilter">
                <option value="">All Room Numbers</option>
                <?php
                $roomRes = $conn->query("SELECT DISTINCT RoomNumber FROM room ORDER BY RoomNumber");
                while ($room = $roomRes->fetch_assoc()) {
                  echo '<option value="' . htmlspecialchars($room['RoomNumber']) . '">' . htmlspecialchars($room['RoomNumber']) . '</option>';
                }
                ?>
              </select>
            </div>
            <table id="allBookingsTable">
              <thead>
                <tr>
                  <th>Booking ID</th>
                  <th>Guest Name</th>
                  <th>Room Number</th>
                  <th>Room Type</th>
                  <th>Status</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Booking Date</th>
                  <th>Booking Type</th>
                </tr>
              </thead>
              <tbody>
                <!-- Booking rows will be loaded here via JS -->
              </tbody>
            </table>
          </div>
        </div>
    </div>

    <!-- Walk-in Booking Modal (Step 1: Booking Details) -->
    <div id="bookingModal" class="modal">
        <div class="modal-content compact-modal">
            <form id="bookingForm">
                <div class="modal-header">
                    <h2>Walk-in Booking Details</h2>
                    <span class="close-btn" id="closeBookingModal">&times;</span>
                </div>
                <div class="booking-details-grid compact-grid">
                    <div class="form-group compact-group">
                        <label for="checkInDate">Check In</label>
                        <input type="date" id="checkInDate" name="checkInDate" required autocomplete="off">
                    </div>
                    <div class="form-group compact-group">
                        <label for="checkOutDate">Check Out</label>
                        <input type="date" id="checkOutDate" name="checkOutDate" required autocomplete="off">
                    </div>
                    <div class="form-group compact-group">
                        <label for="bookingDate">Booking Date</label>
                        <input type="date" id="bookingDate" name="bookingDate" value="<?php echo date('Y-m-d'); ?>" required readonly style="background:#f3f3f3; cursor:not-allowed;" autocomplete="off">
                    </div>
                    <div class="form-group compact-group">
                        <label for="bookingStatus">Booking Status</label>
                        <select id="bookingStatus" name="bookingStatus">
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                        </select>
                    </div>
                    <div class="form-group compact-group">
                        <label for="roomType">Room Type</label>
                        <select id="roomType" name="roomType">
                            <option value="">Select Room Type</option>
                            <option value="Standard">Standard</option>
                            <option value="Deluxe">Deluxe</option>
                            <option value="Suite">Suite</option>
                        </select>
                    </div>
                    <div class="form-group compact-group">
                        <label for="roomNumber">Room Number</label>
                        <select id="roomNumber" name="roomNumber" required>
                            <option value="">Select Room Number</option>
                        </select>
                    </div>
                </div>
                <div class="form-group compact-group">
                    <label for="specialRequest">Special Request</label>
                    <textarea id="specialRequest" name="specialRequest"></textarea>
                </div>
                
                <div class="modal-footer compact-footer">
                    <button type="button" class="control-btn" id="cancelBookingBtn">Cancel</button>
                    <button type="button" class="control-btn walk-in-btn" id="nextToGuestBtn">Next</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Guest Information Modal (Step 2) -->
    <div id="guestModal" class="modal">
        <div class="modal-content compact-modal">
            <form id="guestForm">
                <div class="modal-header">
                    <h2>Guest Details</h2>
                    <span class="close-btn" id="closeGuestModal">&times;</span>
                </div>
                <table class="guest-info-table compact-table">
                    <tr><th>First Name</th><td><input type="text" name="firstName" required autocomplete="given-name"></td></tr>
                    <tr><th>Last Name</th><td><input type="text" name="lastName" required autocomplete="family-name"></td></tr>
                    <tr><th>Gender</th><td>
                        <select name="gender" id="detailsGender" class="styled-select" required>
                            <option value="">Select Gender</option>
                            <option value="Female">Female</option>
                            <option value="Male">Male</option>
                            <option value="Other">Other</option>
                        </select>
                    </td></tr>
                    <tr><th>Phone Number</th><td><input type="tel" name="phone" autocomplete="tel"></td></tr>
                    <tr><th>Address</th><td><input type="text" name="address" autocomplete="street-address"></td></tr>
                    <tr><th>Email</th><td><input type="email" name="email" autocomplete="email"></td></tr>
                    <tr><th>Nationality</th><td><input type="text" name="nationality" autocomplete="country"></td></tr>
                    <tr><th>Birthdate</th><td><input type="date" name="birthdate" autocomplete="bday"></td></tr>
                    <tr><th>Student ID</th><td><input type="text" name="studentId" autocomplete="off"></td></tr>
                </table>
                <div class="modal-footer compact-footer">
                    <button type="button" class="control-btn" id="backToBookingBtn">Back</button>
                    <button type="submit" class="control-btn walk-in-btn">Create Booking</button>
                </div>
            </form>
        </div>
    </div>

    <script src="booking_index.js"></script>
</body>
</html> 

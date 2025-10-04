<?php
// Database Connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "hotel_reservation_systemdb";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create cancellation tracking table if it doesn't exist
$createCancellationTableSQL = "CREATE TABLE IF NOT EXISTS reservation_cancellations (
    CancellationID INT AUTO_INCREMENT PRIMARY KEY,
    ReservationID VARCHAR(20) NOT NULL,
    CancellationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CancellationReason TEXT,
    INDEX idx_reservation_id (ReservationID),
    INDEX idx_cancellation_date (CancellationDate)
)";

if (!$conn->query($createCancellationTableSQL)) {
    error_log("Error creating cancellation table: " . $conn->error);
}

// Handle AJAX update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ReservationID']) && !isset($_POST['createReservation'])) {
    $id = $conn->real_escape_string($_POST['ReservationID']);
    $guest = $conn->real_escape_string($_POST['GuestName']);
    $checkin = $conn->real_escape_string($_POST['PCheckInDate']);
    $checkout = $conn->real_escape_string($_POST['PCheckOutDate']);
    $room = $conn->real_escape_string($_POST['RoomNumber']);
    error_log('UPDATE RoomNumber: ' . $_POST['RoomNumber'] . ' (raw: ' . $room . ')');
    $type = $conn->real_escape_string($_POST['RoomType']);
    $status = $conn->real_escape_string($_POST['Status']);

    // Validate room number
    if ($room === '' || $room === '0') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please select a valid room number']);
        exit;
    }
    // Validate dates
    if (strtotime($checkin) >= strtotime($checkout)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date']);
        exit;
    }

    // Check if room is available for the selected dates (excluding current reservation)
    $checkSql = "SELECT COUNT(*) as count FROM reservations 
                 WHERE RoomNumber = '$room' 
                 AND Status != 'Cancelled'
                 AND ReservationID != '$id'
                 AND ((PCheckInDate <= '$checkin' AND PCheckOutDate > '$checkin') 
                      OR (PCheckInDate < '$checkout' AND PCheckOutDate >= '$checkout')
                      OR (PCheckInDate >= '$checkin' AND PCheckOutDate <= '$checkout'))";
    $checkResult = $conn->query($checkSql);
    $checkRow = $checkResult->fetch_assoc();
    if ($checkRow['count'] > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Room $room is not available for the selected dates"]);
        exit;
    }

    $sql = "UPDATE reservations SET 
        GuestName='$guest',
        PCheckInDate='$checkin',
        PCheckOutDate='$checkout',
        RoomNumber='$room',
        RoomType='$type',
        Status='$status'
        WHERE ReservationID='$id'";
    
    $success = $conn->query($sql);

    header('Content-Type: application/json');
    if (!$success) {
        echo json_encode(['success' => false, 'message' => 'Failed to update reservation: ' . $conn->error, 'sql' => $sql]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Reservation updated successfully']);
    }
    exit;
}

// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_reservation') {
    header('Content-Type: application/json');
    
    $reservationId = $conn->real_escape_string($_POST['reservationId']);
    $cancellationReason = isset($_POST['cancellationReason']) ? $conn->real_escape_string($_POST['cancellationReason']) : '';
    
    if (empty($reservationId)) {
        echo json_encode(['success' => false, 'message' => 'Reservation ID is required']);
        exit;
    }
    
    // Check if reservation exists and is not already cancelled
    $checkSql = "SELECT Status FROM reservations WHERE ReservationID = '$reservationId'";
    $checkResult = $conn->query($checkSql);
    
    if (!$checkResult || $checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Reservation not found']);
        exit;
    }
    
    $reservation = $checkResult->fetch_assoc();
    if ($reservation['Status'] === 'Cancelled') {
        echo json_encode(['success' => false, 'message' => 'Reservation is already cancelled']);
        exit;
    }
    
    // Update reservation status to cancelled
    $updateSql = "UPDATE reservations SET Status = 'Cancelled' WHERE ReservationID = '$reservationId'";
    $success = $conn->query($updateSql);
    
    if ($success) {
        // Log the cancellation (you could add this to a separate cancellation_logs table)
        $logSql = "INSERT INTO reservation_cancellations (ReservationID, CancellationDate, CancellationReason) 
                   VALUES ('$reservationId', NOW(), " . ($cancellationReason ? "'$cancellationReason'" : "NULL") . ")";
        $conn->query($logSql); // Don't fail if logging fails
        
        echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel reservation: ' . $conn->error]);
    }
    exit;
}

// Generate a unique ReservationID
function generateReservationID($conn) {
    $date = date('mdY');
    $prefix = 'RS-' . $date . '-';
    $sql = "SELECT ReservationID FROM reservations WHERE ReservationID LIKE '$prefix%' ORDER BY ReservationID DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $parts = explode('-', $row['ReservationID']);
        $lastNumber = isset($parts[2]) ? intval($parts[2]) : 0;
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }
    
    $next = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    return $prefix . $next;
}
// Handle create reservation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['createReservation'])) {
    $guest = $conn->real_escape_string($_POST['GuestName']);
    $checkin = $conn->real_escape_string($_POST['PCheckInDate']);
    $checkout = $conn->real_escape_string($_POST['PCheckOutDate']);
    $room = $conn->real_escape_string($_POST['RoomNumber']);
    error_log('CREATE RoomNumber: ' . $_POST['RoomNumber'] . ' (raw: ' . $room . ')');
    $type = $conn->real_escape_string($_POST['RoomType']);
    $studentID = isset($_POST['StudentID']) ? $conn->real_escape_string($_POST['StudentID']) : '';
    $status = 'Pending'; // Always set to Pending on creation
    
    // Generate unique ReservationID
    $reservationID = generateReservationID($conn);
    error_log('Generated ReservationID: ' . $reservationID);
    
    // Validate room number
    if ($room === '' || $room === '0') {
        $error = "Please select a valid room number.";
    } else if (strtotime($checkin) >= strtotime($checkout)) {
        $error = "Check-out date must be after check-in date";
    } else if ($studentID === '') {
        $error = "Please enter a Student ID.";
    } else {
        // Check if room is available for the selected dates
        $checkSql = "SELECT COUNT(*) as count FROM reservations 
                     WHERE RoomNumber = '$room' 
                     AND Status != 'Cancelled'
                     AND ((PCheckInDate <= '$checkin' AND PCheckOutDate > '$checkin') 
                          OR (PCheckInDate < '$checkout' AND PCheckOutDate >= '$checkout')
                          OR (PCheckInDate >= '$checkin' AND PCheckOutDate <= '$checkout'))";
        $checkResult = $conn->query($checkSql);
        $checkRow = $checkResult->fetch_assoc();
        if ($checkRow['count'] > 0) {
            $error = "Room $room is not available for the selected dates";
        } else {
            $sql = "INSERT INTO reservations (ReservationID, GuestName, PCheckInDate, PCheckOutDate, RoomNumber, RoomType, Status, StudentID) 
                    VALUES ('$reservationID', '$guest', '$checkin', '$checkout', '$room', '$type', '$status', '$studentID')";
            error_log('INSERT SQL: ' . $sql);
            if ($conn->query($sql)) {
                error_log('Reservation created successfully with ID: ' . $reservationID);
                header('Location: reservation.php?success=1');
                exit;
            } else {
                $error = "Failed to create reservation: " . $conn->error;
                error_log('Error creating reservation: ' . $conn->error);
            }
        }
    }
}

// Fetch reservations with search and filter
$whereClause = "WHERE 1=1";
$params = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClause .= " AND (GuestName LIKE '%$search%' OR ReservationID LIKE '%$search%' OR RoomNumber LIKE '%$search%')";
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClause .= " AND Status = '$status'";
}

if (isset($_GET['room_type']) && !empty($_GET['room_type'])) {
    $roomType = $conn->real_escape_string($_GET['room_type']);
    $whereClause .= " AND RoomType = '$roomType'";
}

if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
    $checkinDate = $conn->real_escape_string($_GET['checkin_date']);
    $whereClause .= " AND DATE(PCheckInDate) = '$checkinDate'";
}

$resQuery = "SELECT * FROM reservations $whereClause ORDER BY ReservationID DESC";
$resResult = $conn->query($resQuery);

// Get available rooms for create form
$checkIn = isset($_POST['PCheckInDate']) ? $conn->real_escape_string($_POST['PCheckInDate']) : null;
$checkOut = isset($_POST['PCheckOutDate']) ? $conn->real_escape_string($_POST['PCheckOutDate']) : null;

if ($checkIn && $checkOut) {
    $roomsQuery = "SELECT RoomNumber, RoomType FROM room
        WHERE RoomStatus IN ('Available', 'Maintenance', 'Cleaning')
          AND RoomNumber NOT IN (
              SELECT CAST(RoomNumber AS CHAR) FROM booking
              WHERE BookingStatus NOT IN ('Cancelled', 'Completed')
                AND (
                    (CheckInDate <= '$checkOut' AND CheckOutDate >= '$checkIn')
                )
          )
          AND RoomNumber NOT IN (
              SELECT CAST(RoomNumber AS CHAR) FROM reservations
              WHERE Status != 'Cancelled'
                AND (
                    (PCheckInDate <= '$checkOut' AND PCheckOutDate >= '$checkIn')
                )
          )
        ORDER BY RoomNumber
    ";
} else {
    $roomsQuery = "SELECT RoomNumber, RoomType FROM room WHERE RoomStatus IN ('Available', 'Maintenance', 'Cleaning') ORDER BY RoomNumber";
}
$roomsResult = $conn->query($roomsQuery);
$availableRooms = [];
if ($roomsResult) {
    while ($room = $roomsResult->fetch_assoc()) {
        $availableRooms[] = $room;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['getRooms'])) {
    header('Content-Type: application/json');
    $checkIn = $conn->real_escape_string($_GET['checkIn']);
    $checkOut = $conn->real_escape_string($_GET['checkOut']);
    $roomType = isset($_GET['roomType']) ? $conn->real_escape_string($_GET['roomType']) : '';

    // Error check for missing parameters
    if (empty($checkIn) || empty($checkOut) || empty($roomType)) {
        echo json_encode(['noRooms' => true, 'message' => 'Please select check-in, check-out, and room type.']);
        exit;
    }

    // Get all rooms of the selected type
    $sql = "SELECT RoomNumber, RoomType, RoomStatus FROM room WHERE RoomType = '$roomType' ORDER BY RoomNumber";
    $result = $conn->query($sql);
    if ($result === false) {
        echo json_encode(['noRooms' => true, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $roomNumber = $row['RoomNumber'];
        $status = $row['RoomStatus'];
        $label = $status;
        $available = true;
        // Check for bookings
        $bookedSql = "SELECT 1 FROM booking WHERE RoomNumber = '$roomNumber' AND BookingStatus NOT IN ('Cancelled', 'Completed') AND (DATE(CheckInDate) < '$checkOut' AND DATE(CheckOutDate) > '$checkIn')";
        $bookedResult = $conn->query($bookedSql);
        if ($bookedResult && $bookedResult->num_rows > 0) {
            $label = 'Booked';
            $available = false;
        }
        // Check for reservations
        $reservedSql = "SELECT 1 FROM reservations WHERE RoomNumber = '$roomNumber' AND Status != 'Cancelled' AND (PCheckInDate < '$checkOut' AND PCheckOutDate > '$checkIn')";
        $reservedResult = $conn->query($reservedSql);
        if ($reservedResult && $reservedResult->num_rows > 0) {
            $label = 'Booked';
            $available = false;
        }
        // If status is Maintenance or Cleaning, override label
        if ($status === 'Maintenance' || $status === 'Cleaning') {
            $label = $status;
            $available = false;
        }
        $rooms[] = [
            'RoomNumber' => $roomNumber,
            'RoomType' => $row['RoomType'],
            'Status' => $status,
            'Label' => $label,
            'Available' => $available
        ];
    }
    echo json_encode($rooms);
    exit;
}

// Handle confirm reservation (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_reservation') {
    $reservationId = $conn->real_escape_string($_POST['reservationId'] ?? '');
    if ($reservationId) {
        $res = $conn->query("SELECT * FROM reservations WHERE ReservationID = '$reservationId'");
        if ($res && $row = $res->fetch_assoc()) {
            $checkIn = $row['PCheckInDate'];
            // Use booking creation date for the code
            $bookingDatePart = date('mdY'); // e.g., 06242025
            // Find the highest sequence for this date
            $codeRes = $conn->query("SELECT BookingCode FROM booking WHERE BookingCode LIKE 'BK-{$bookingDatePart}-%' ORDER BY BookingCode DESC LIMIT 1");
            if ($codeRes && $codeRow = $codeRes->fetch_assoc()) {
                $lastSeq = intval(substr($codeRow['BookingCode'], -4));
                $nextSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nextSeq = '0001';
            }
            $bookingCode = "BK-{$bookingDatePart}-{$nextSeq}";

            // Update reservation status
            $conn->query("UPDATE reservations SET Status = 'Confirmed' WHERE ReservationID = '$reservationId'");

            // Insert or update booking record
            // Check if a booking already exists for this reservation
            $bookingRes = $conn->query("SELECT * FROM booking WHERE ReservationID = '$reservationId'");
            if (!$bookingRes || $bookingRes->num_rows == 0) {
                // Insert new booking as shown previously
                $studentId = isset($row['StudentID']) ? $row['StudentID'] : 0;
                $roomNumber = $row['RoomNumber'];
                $roomType = $row['RoomType'];
                $checkOut = $row['PCheckOutDate'];
                $bookingDate = date('Y-m-d');
                $notes = isset($row['Notes']) ? $row['Notes'] : '';
                $roomStatus = 'Booked';
                $price = 0;
                $bookingStatus = 'Confirmed';
                $stmt = $conn->prepare("INSERT INTO booking (ReservationID, StudentID, RoomNumber, RoomType, BookingStatus, RoomStatus, Notes, CheckInDate, CheckOutDate, BookingDate, Price, BookingCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("siisssssssss", $reservationId, $studentId, $roomNumber, $roomType, $bookingStatus, $roomStatus, $notes, $checkIn, $checkOut, $bookingDate, $price, $bookingCode);
                $stmt->execute();
                if ($stmt->error) {
                    echo json_encode(['success' => false, 'message' => 'MySQL error: ' . $stmt->error]);
                    exit;
                }
                $bookingId = $conn->insert_id;
            } else {
                // Update existing booking as before
                $conn->query("UPDATE booking SET BookingStatus = 'Confirmed', BookingCode = '$bookingCode' WHERE ReservationID = '$reservationId'");
                $bookingRow = $bookingRes->fetch_assoc();
                $bookingId = $bookingRow['BookingID'];
            }
            // Ensure student record exists or is updated
            $firstName = isset($row['GuestName']) ? $row['GuestName'] : '';
            $gender = isset($row['Gender']) ? $row['Gender'] : '';
            $phone = isset($row['PhoneNumber']) ? $row['PhoneNumber'] : '';
            $address = isset($row['Address']) ? $row['Address'] : '';
            $email = isset($row['Email']) ? $row['Email'] : '';
            $nationality = isset($row['Nationality']) ? $row['Nationality'] : '';
            $birthdate = isset($row['BirthDate']) ? $row['BirthDate'] : '';
            $studentCheck = $conn->prepare("SELECT StudentID FROM student WHERE StudentID = ?");
            $studentCheck->bind_param("i", $studentId);
            $studentCheck->execute();
            $studentCheck->store_result();
            if ($studentCheck->num_rows > 0) {
                $updateStudent = $conn->prepare("UPDATE student SET FirstName=?, Gender=?, PhoneNumber=?, Address=?, Email=?, Nationality=?, BirthDate=? WHERE StudentID=?");
                $updateStudent->bind_param("sssssssi", $firstName, $gender, $phone, $address, $email, $nationality, $birthdate, $studentId);
                $updateStudent->execute();
            } else {
                $insertStudent = $conn->prepare("INSERT INTO student (StudentID, FirstName, Gender, PhoneNumber, Address, Email, Nationality, BirthDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStudent->bind_param("isssssss", $studentId, $firstName, $gender, $phone, $address, $email, $nationality, $birthdate);
                $insertStudent->execute();
            }
            echo json_encode(['success' => true, 'message' => 'Reservation confirmed! Booking ID: ' . $bookingId, 'bookingId' => $bookingId, 'bookingCode' => $bookingCode]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Reservation not found. SQL: SELECT * FROM reservations WHERE ReservationID = ' . $reservationId]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing Reservation ID. reservationId=' . var_export($_POST['reservationId'], true)]);
        exit;
    }
}

// AJAX handler to return updated reservations table body
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_reservations_table') {
    ob_start();
    // Re-run the same query as for the main table
    $whereClause = 'WHERE 1';
    if (isset($_GET['room_type']) && !empty($_GET['room_type'])) {
        $roomType = $conn->real_escape_string($_GET['room_type']);
        $whereClause .= " AND RoomType = '$roomType'";
    }
    if (isset($_GET['checkin_date']) && !empty($_GET['checkin_date'])) {
        $checkinDate = $conn->real_escape_string($_GET['checkin_date']);
        $whereClause .= " AND DATE(PCheckInDate) = '$checkinDate'";
    }
    $resQuery = "SELECT * FROM reservations $whereClause ORDER BY ReservationID DESC";
    $resResult = $conn->query($resQuery);
    if ($resResult && $resResult->num_rows > 0):
        while ($row = $resResult->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo $row['ReservationID']; ?></td>
                <td><?php echo htmlspecialchars($row['GuestName']); ?></td>
                <td><?php echo htmlspecialchars($row['RoomNumber']); ?></td>
                <td><?php echo htmlspecialchars($row['RoomType']); ?></td>
                <td><?php echo htmlspecialchars($row['PCheckInDate']); ?></td>
                <td><?php echo htmlspecialchars($row['PCheckOutDate']); ?></td>
                <td><?php echo htmlspecialchars($row['Status']); ?></td>
                <td>
                    <div class="action-btns">
                        <?php if ($row['Status'] === 'Pending'): ?>
                        <button class="action-btn confirm-book-btn"
                          data-id="<?php echo $row['ReservationID']; ?>"
                          data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                          data-checkin="<?php echo $row['PCheckInDate']; ?>"
                          data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                          data-room="<?php echo $row['RoomNumber']; ?>"
                          data-type="<?php echo $row['RoomType']; ?>"
                          data-status="<?php echo $row['Status']; ?>"
                        >
                            <i class="fas fa-check"></i> Confirm & Book
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php
        endwhile;
    else:
        ?>
        <tr><td colspan="8">No reservations found.</td></tr>
        <?php
    endif;
    $tbody = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode(['tableHtml' => $tbody]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="reservation_style.css">
</head>
<body>
    <button class="hamburger" id="sidebarToggle" aria-label="Open sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
   <!-- Sidebar Navigation -->
   <div class="sidebar">
        <h4 class="sidebar-title">Villa Valore Hotel</h4>
        
        <div class="nav-section">
            <a class="nav-link" href="index.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="student.php"><i class="fas fa-user"></i>Guest</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
        </div>

        <div class="nav-section">
            <div class="management-label">MANAGEMENT</div>
            <div class="nav-link toggle-btn" onclick="toggleMenu('management')">
                <div><i class="fas fa-cog"></i>Manage</div>
            </div>
            <div class="submenu" id="management">
                <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
                <a class="nav-link" href="menu_service.php"><i class="fas fa-utensils"></i>Menu & Service</a>
                <a class="nav-link" href="account.php"><i class="fas fa-user"></i>Account</a>
                <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i>Inventory</a>
            </div>
        </div>

        <div class="nav-section">
            <a class="nav-link" href="payment.php"><i class="fas fa-credit-card"></i>Payments</a>
            <a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i>Statistics</a>
            <a class="nav-link" href="inbox.php"><i class="fas fa-inbox"></i>Inbox</a>
        </div>

        <div class="nav-section">
            <a class="nav-link" href="profile.php"><i class="fas fa-user-lock"></i>Profile Account</a>
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
    </div>
        <div class="main-content">
        <div class="reservation-section">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> Reservation created successfully!
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="reservation-header">
                <h1 class="reservation-title">Reservation</h1>
                <div class="reservation-controls">
                    <div class="search-bar">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchInput" class="search-input" placeholder="Search by guest name, ID, or room number" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button id="clearSearchBtn" style="display:none; border:none; background:transparent; color:#888; font-size:1.2rem; cursor:pointer;">&times;</button>
                    </div>
                    <button class="filter-btn" id="filterBtn">
                        <i class="fas fa-filter"></i> Filter
                        <?php if (isset($_GET['status']) || isset($_GET['room_type']) || isset($_GET['checkin_date'])): ?>
                            <span style="background: #ff4444; color: white; border-radius: 50%; width: 18px; height: 18px; display: inline-block; font-size: 0.7rem; line-height: 18px; margin-left: 5px;">!</span>
                        <?php endif; ?>
                    </button>
                    <button class="create-btn" id="createBtn">
                        <i class="fas fa-plus"></i> Create Reservation
                    </button>
                </div>
            </div>
            <div class="filter-modal-overlay" id="filterDropdown">
                <div class="filter-modal">
                    <button class="close-modal" id="closeFilterModal" aria-label="Close">&times;</button>
                    <form id="filterForm">
                        <label>Status:
                            <select name="status">
                                <option value="">Any Status</option>
                                <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </label>
                        <label>Room Type:
                            <select name="room_type">
                                <option value="">Any Type</option>
                                <option value="Standard" <?php echo (isset($_GET['room_type']) && $_GET['room_type'] == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                                <option value="Deluxe" <?php echo (isset($_GET['room_type']) && $_GET['room_type'] == 'Deluxe') ? 'selected' : ''; ?>>Deluxe</option>
                                <option value="Suite" <?php echo (isset($_GET['room_type']) && $_GET['room_type'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                            </select>
                        </label>
                        <label>Check-in Date:
                            <input type="date" name="checkin_date" value="<?php echo isset($_GET['checkin_date']) ? htmlspecialchars($_GET['checkin_date']) : ''; ?>">
                        </label>
                        <div class="filter-actions">
                            <button type="button" id="applyFilterBtn" class="filter-btn">Apply</button>
                            <button type="button" id="clearFilterBtn" class="filter-btn">Clear</button>
                        </div>
                    </form>
                </div>
            </div>
            <table class="reservation-table">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Guest Name</th>
                            <th>Check-in Date</th>
                            <th>Check-out Date</th>
                        <th>Room Number</th>
                        <th>Room Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php if ($resResult && $resResult->num_rows > 0): ?>
                    <?php while($row = $resResult->fetch_assoc()): ?>
                    <tr data-id="<?php echo $row['ReservationID']; ?>">
                        <td><b><?php echo $row['ReservationID']; ?></b></td>
                        <td><b><?php echo htmlspecialchars($row['GuestName']); ?></b></td>
                        <td><b><?php echo date('m/d/Y', strtotime($row['PCheckInDate'])); ?></b></td>
                        <td><b><?php echo date('m/d/Y', strtotime($row['PCheckOutDate'])); ?></b></td>
                        <td><?php echo $row['RoomNumber'] ? $row['RoomNumber'] : 'N/A'; ?></td>
                        <td><b><?php echo $row['RoomType']; ?></b></td>
                        <td><b><?php echo $row['Status']; ?></b></td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn edit-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  data-checkin="<?php echo $row['PCheckInDate']; ?>"
                                  data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                                  data-room="<?php echo $row['RoomNumber']; ?>"
                                  data-type="<?php echo $row['RoomType']; ?>"
                                  data-status="<?php echo $row['Status']; ?>"
                                >
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn view-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  data-checkin="<?php echo $row['PCheckInDate']; ?>"
                                  data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                                  data-room="<?php echo $row['RoomNumber']; ?>"
                                  data-type="<?php echo $row['RoomType']; ?>"
                                  data-status="<?php echo $row['Status']; ?>"
                                >
                                    <i class="fas fa-eye"></i> View
                                </button>
                                <?php if ($row['Status'] === 'Pending'): ?>
                                <button class="action-btn confirm-book-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  data-checkin="<?php echo $row['PCheckInDate']; ?>"
                                  data-checkout="<?php echo $row['PCheckOutDate']; ?>"
                                  data-room="<?php echo $row['RoomNumber']; ?>"
                                  data-type="<?php echo $row['RoomType']; ?>"
                                  data-status="<?php echo $row['Status']; ?>"
                                >
                                    <i class="fas fa-check"></i> Confirm & Book
                                </button>
                                <button class="action-btn cancel-btn"
                                  data-id="<?php echo $row['ReservationID']; ?>"
                                  data-guest="<?php echo htmlspecialchars($row['GuestName']); ?>"
                                  style="background-color: #dc3545; color: white;"
                                >
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <?php endif; ?>
                            </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8">No reservations found.</td></tr>
                <?php endif; ?>
                    </tbody>
                </table>
        </div>
    </div>
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="close" id="closeEditModal" aria-label="Close">&times;</button>
            <h2><i class="fas fa-edit"></i> Edit Reservation</h2>
            <form id="editForm">
                <input type="hidden" name="ReservationID" id="editReservationID">
                <div class="form-group">
                    <label for="editGuestName">Guest Name:</label>
                    <input type="text" name="GuestName" id="editGuestName" required>
                </div>
                <div class="form-group">
                    <label for="editCheckIn">Check-in Date:</label>
                    <input type="date" name="PCheckInDate" id="editCheckIn" required>
                </div>
                <div class="form-group">
                    <label for="editCheckOut">Check-out Date:</label>
                    <input type="date" name="PCheckOutDate" id="editCheckOut" required>
                </div>
                <div class="form-group">
                    <label for="editRoomNumber">Room Number:</label>
                    <select name="RoomNumber" id="editRoomNumber" required>
                        <option value="">Select a room</option>
                        <?php foreach ($availableRooms as $room): ?>
                            <option value="<?php echo $room['RoomNumber']; ?>"><?php echo $room['RoomNumber']; ?> (<?php echo $room['RoomType']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editRoomType">Room Type:</label>
                    <select name="RoomType" id="editRoomType" required>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Suite</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editStatus">Status:</label>
                    <select name="Status" id="editStatus" required>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div id="editFormError" style="color: #dc3545; margin-bottom: 1rem; display: none;"></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- View Modal -->
    <div id="viewModal" class="modal view-modal">
        <div class="modal-content">
            <button class="close-modal" id="closeViewModal" aria-label="Close">&times;</button>
            <h2><i class="fas fa-eye"></i> View Reservation Details</h2>
            <div id="viewDetails" class="view-details">
                <!-- Details will be filled by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Cancellation Modal -->
    <div id="cancellationModal" class="modal">
        <div class="modal-content">
            <button class="close-modal" id="closeCancellationModal" aria-label="Close">&times;</button>
            <h2><i class="fas fa-times-circle"></i> Cancel Reservation</h2>
            <form id="cancellationForm">
                <input type="hidden" id="cancellationReservationId" name="reservationId">
                <div class="form-group">
                    <label for="cancellationGuestName">Guest Name:</label>
                    <input type="text" id="cancellationGuestName" readonly style="background-color: #f8f9fa;">
                </div>
                <div class="form-group">
                    <label for="cancellationReason">Cancellation Reason (Optional):</label>
                    <textarea id="cancellationReason" name="cancellationReason" rows="3" placeholder="Please provide a reason for cancellation..."></textarea>
                </div>
                <div class="form-group">
                    <p style="color: #dc3545; font-weight: bold;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Warning: This action cannot be undone. The reservation will be marked as cancelled.
                    </p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelCancellationBtn">Keep Reservation</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Create Reservation Modal -->
    <div class="create-modal-overlay" id="createModal">
        <div class="create-modal">
            <button class="close-modal" id="closeCreateModal" aria-label="Close">&times;</button>
            <h2><i class="fas fa-plus"></i> Create Reservation</h2>
            <form id="createForm" method="POST">
                <input type="hidden" name="createReservation" value="1">
                <label>Guest Name:
                    <input type="text" name="GuestName" required>
                </label>
                <label>Check-in Date:
                    <input type="date" name="PCheckInDate" id="createCheckIn" required min="<?php echo date('Y-m-d'); ?>">
                </label>
                <label>Check-out Date:
                    <input type="date" name="PCheckOutDate" id="createCheckOut" required min="<?php echo date('Y-m-d'); ?>">
                </label>
                <label>Room Number:
                    <select name="RoomNumber" id="createRoomNumber" required>
                        <option value="">Select a room</option>
                        <?php foreach ($availableRooms as $room): ?>
                            <option value="<?php echo $room['RoomNumber']; ?>">
                                Room <?php echo $room['RoomNumber']; ?> (<?php echo $room['RoomType']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Room Type:
                    <select name="RoomType" id="createRoomType" required>
                        <option value="">Select room type</option>
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Suite</option>
                    </select>
                </label>
                <label>Student ID:
                    <input type="text" name="StudentID" id="createStudentID" required>
                </label>
                <div id="createFormError" style="color: #dc3545; margin-bottom: 1rem; display: none;"></div>
                <button type="submit">Create Reservation</button>
            </form>
        </div>
    </div>
    <!-- Booking Modal (Step 1: Booking Details) -->
    <div id="bookingModal" class="modal">
        <div class="modal-content compact-modal">
            <form id="bookingForm">
                <div class="modal-header">
                    <h2>Booking Details</h2>
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
                        <select id="bookingStatus" name="bookingStatus"><option value="Pending">Pending</option><option value="Confirmed">Confirmed</option></select>
                    </div>
                    <div class="form-group compact-group">
                        <label for="roomType">Room Type</label>
                        <select id="roomType" name="roomType">
                            <option value="Deluxe">Deluxe</option>
                            <option value="Standard">Standard</option>
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
    <script src="reservation_index.js"></script>
</body>
</html> 

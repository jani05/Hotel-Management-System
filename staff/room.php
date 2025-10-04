<?php
// ============================================================================
// HANDLE AJAX ROOM STATUS UPDATE
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateStatus') {
    // Database connection for AJAX request
    $host = "localhost";
    $user = "root";
    $password = "";
    $dbname = "hotel_reservation_systemdb";
    $conn = new mysqli($host, $user, $password, $dbname);
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $roomNumber = isset($_POST['roomNumber']) ? intval($_POST['roomNumber']) : 0;
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Validate inputs
    $validStatuses = ['Available', 'Booked', 'Reserved', 'Maintenance', 'Cleaning'];
    if ($roomNumber <= 0 || !in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid room number or status']);
        exit;
    }
    
    // Update room status in database
    $updateQuery = $conn->prepare("UPDATE room SET RoomStatus = ? WHERE RoomNumber = ?");
    $updateQuery->bind_param('si', $newStatus, $roomNumber);
    
    if ($updateQuery->execute()) {
        echo json_encode(['success' => true, 'message' => 'Room status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update room status']);
    }
    
    $updateQuery->close();
    $conn->close();
    exit; // Stop execution here for AJAX requests
}

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
// ROOM CATEGORY SELECTION
// ============================================================================
$roomType = isset($_GET['type']) ? $_GET['type'] : 'Standard';
$roomTypes = ['Standard', 'Deluxe', 'Suite'];
$statuses = ['Available', 'Maintenance', 'Cleaning'];

// Determine previous and next room types for navigation
$currentIndex = array_search($roomType, $roomTypes);
$prevIndex = ($currentIndex - 1 + count($roomTypes)) % count($roomTypes);
$nextIndex = ($currentIndex + 1) % count($roomTypes);
$prevRoomType = $roomTypes[$prevIndex];
$nextRoomType = $roomTypes[$nextIndex];

// ============================================================================
// FETCH ROOMS AND DETERMINE STATUS
// ============================================================================
$rooms = [];
$roomQuery = $conn->prepare("SELECT RoomNumber, RoomType, RoomStatus FROM room ORDER BY RoomNumber ASC");
$roomQuery->execute();
$roomResult = $roomQuery->get_result();

$today = date('Y-m-d');
while ($room = $roomResult->fetch_assoc()) {
    $roomNumber = $room['RoomNumber'];
    $status = $room['RoomStatus'];
    $isOccupied = false;

    // Check for active booking
    $bookingSql = "SELECT 1 FROM booking WHERE RoomNumber = ? AND BookingStatus = 'Confirmed' AND ? >= CheckInDate AND ? < CheckOutDate";
    $bookingStmt = $conn->prepare($bookingSql);
    $bookingStmt->bind_param('iss', $roomNumber, $today, $today);
    $bookingStmt->execute();
    $bookingResult = $bookingStmt->get_result();
    if ($bookingResult->num_rows > 0) {
        $isOccupied = true;
    }
    $bookingStmt->close();

    // Check for active reservation
    $reservationSql = "SELECT 1 FROM reservations WHERE RoomNumber = ? AND Status = 'Confirmed' AND ? >= PCheckInDate AND ? < PCheckOutDate";
    $reservationStmt = $conn->prepare($reservationSql);
    $reservationStmt->bind_param('iss', $roomNumber, $today, $today);
    $reservationStmt->execute();
    $reservationResult = $reservationStmt->get_result();
    if ($reservationResult->num_rows > 0) {
        $isOccupied = true;
    }
    $reservationStmt->close();

    if ($status === 'Maintenance') {
        $dynamicStatus = 'Maintenance';
    } elseif ($status === 'Cleaning') {
        $dynamicStatus = 'Cleaning';
    } elseif ($isOccupied) {
        // Do not add this room as available or with any status
        continue;
    } else {
        $dynamicStatus = 'Available';
    }
    $rooms[] = [
        'RoomNumber' => $roomNumber,
        'RoomType' => $room['RoomType'],
        'Status' => $dynamicStatus
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['roomType'])) {
    header('Content-Type: application/json');
    $roomType = $conn->real_escape_string($_GET['roomType']);
    $rooms = [];
    $sql = "SELECT RoomNumber FROM room WHERE RoomType = '$roomType' AND RoomStatus IN ('Available', 'Maintenance', 'Cleaning') ORDER BY RoomNumber";
    $result = $conn->query($sql);

    // Debug output
    if (!$result) {
        echo json_encode(['error' => 'SQL error', 'sql' => $sql, 'db_error' => $conn->error]);
        exit;
    }

    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }

    // Debug: If no rooms found, output the SQL and roomType
    if (empty($rooms)) {
        echo json_encode(['error' => 'No rooms found', 'roomType' => $roomType, 'sql' => $sql]);
        exit;
    }

    echo json_encode($rooms);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Availability</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- ============================================================================
         CSS STYLES
         ============================================================================ -->
    <style>
        /* BASE LAYOUT */
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #fff; display: flex; }
        /* SIDEBAR */
        .sidebar { width: 200px; background: #008000; min-height: 100vh; padding: 0.5rem; color: white; position: fixed; left: 0; top: 0; bottom: 0; transition: left 0.3s, box-shadow 0.3s; z-index: 1000; }
        .sidebar-title { color: white; font-size: 1.4rem; font-weight: 500; margin-bottom: 1.5rem; padding: 1rem; }
        .sidebar-logo {
            display: block;
            margin: 1.5rem auto;
            width: 80px;
            height: auto;
        }
        .nav-section { margin-bottom: 1rem; }
        .nav-link { display: flex; align-items: center; padding: 0.5rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; transition: background-color 0.2s; }
        .nav-link:hover, .nav-link.active { background-color: rgba(255, 255, 255, 0.1); }
        .nav-link i { margin-right: 0.75rem; width: 20px; text-align: center; opacity: 0.9; }
        /* MAIN CONTENT */
        .main-content { flex: 1; /* Ensure it takes full available width */ padding: 2rem; margin-left: 200px; }
        /* HAMBURGER MENU */
        .hamburger {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1100;
            width: 36px;
            height: 36px;
            background: #008000;
            border: none;
            border-radius: 6px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .hamburger span {
            display: block;
            width: 22px;
            height: 3px;
            background: #fff;
            margin: 4px 0;
            border-radius: 2px;
            transition: 0.3s;
        }
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { left: -220px; box-shadow: none; }
            .sidebar.active { left: 0; box-shadow: 2px 0 8px rgba(0,0,0,0.08); }
            .hamburger { display: flex; }
        }
        /* ROOM AVAILABILITY UI */
        h1 { font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; }
        .room-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        /* .room-header-left { display: flex; align-items: center; gap: 1.5rem; } Removed as per new design*/
        .room-header-right-section { display: flex; flex-direction: row; align-items: center; gap: 2rem; margin-left: auto; /* Pushes the section to the far right */ }
        .room-type-nav { display: flex; align-items: center; gap: 1rem; /* Adjusted spacing between arrows and text */ }
        .room-type-label, .room-type-btn { font-size: 1.3rem; font-weight: 700; cursor: pointer; padding: 0.3rem 1rem; border-radius: 1.2rem; border: none; background: none; transition: background 0.2s, color 0.2s; }
        .room-type-btn.selected, .room-type-label.selected { background: #008000; color: #fff; }
        .room-type-btn:not(.selected):hover { background: #eaeaff; color: #008000; }
        .nav-arrow { background: #d1d1d1; border: none; border-radius: 1.2rem; padding: 0.3rem 0.8rem; font-size: 1.2rem; cursor: pointer; transition: background 0.2s; display: flex; align-items: center; justify-content: center; }
        .nav-arrow:hover { background: #a0a0a0; }
        .search-wrapper { position: relative; }
        .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #555; }
        .search-input { padding: 0.5rem 1.2rem 0.5rem 2.5rem; border-radius: 1.2rem; border: none; background: #ededed; font-size: 1rem; width: 300px; outline: none; }
        .room-grid { display: grid; grid-template-columns: repeat(5, 1fr)                         ; gap: 1.5rem 1rem; margin-bottom: 2.5rem; }
        .room-card { background: #fafaf9; border-radius: 1.2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04); display: flex; flex-direction: column; align-items: center; padding: 0.8rem 0.3rem 1rem 0.3rem; }
        .room-card[data-status="Available"] { background: #b6f7c1; }
        .room-card[data-status="Booked"] { background: #f76e6e; }
        .room-card[data-status="Reserved"] { background: #fff59e; }
        .room-card[data-status="Maintenance"] { background: #e0e0e0; }
        .room-card[data-status="Cleaning"] { background: #ffd59e; }
        .room-number { font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; }
        .room-status-label { font-size: 0.9rem; margin-bottom: 1.2rem; color: #555; }
        .edit-btn { background: #d1d1d1; color: #222; border: none; border-radius: 1.2rem; padding: 0.6rem 2.5rem; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .edit-btn:hover { background: #a0a0a0; }
        /* LEGEND */
        .legend { display: flex; gap: 1.5rem; justify-content: center; margin-top: 1.5rem; }
        .legend-item { display: flex; align-items: center; gap: 0.6rem; font-size: 1.1rem; }
        .legend-badge { width: 2.8rem; height: 1.5rem; border-radius: 0.7rem; display: inline-block; }
        .legend-available { background: #b6f7c1; }
        .legend-booked { background: #f76e6e; }
        .legend-reserved { background: #fff59e; }
        .legend-maintenance { background: #e0e0e0; }
        .legend-cleaning { background: #ffd59e; }
        /* MODAL */
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .modal h2 { margin-bottom: 1.5rem; color: #333; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; }
        .form-group select { width: 100%; padding: 0.7rem; border-radius: 0.5rem; border: 1px solid #ccc; font-size: 1rem; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.7rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 1rem; transition: background 0.2s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #545b62; }
        .room-card.faded-room {
            opacity: 0.45;
            filter: grayscale(0.2);
            border: 1.5px dashed #bbb;
        }
        .room-card.highlight-room {
            box-shadow: 0 0 0 4px #008000, 0 2px 8px rgba(0,128,0,0.10);
            z-index: 2;
            animation: highlightFade 2s forwards;
        }
        @keyframes highlightFade {
            0% { box-shadow: 0 0 0 4px #008000, 0 2px 8px rgba(0,128,0,0.10); }
            80% { box-shadow: 0 0 0 4px #008000, 0 2px 8px rgba(0,128,0,0.10); }
            100% { box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        }
        .room-type-badge {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- ============================================================================
         SIDEBAR NAVIGATION
         ============================================================================ -->
    <button class="hamburger" id="sidebarToggle" aria-label="Open sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="sidebar">
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="sidebar-logo">
        <h4 class="sidebar-title">Villa Valore Hotel</h4>
        <div class="nav-section">
            <a class="nav-link" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link active" href="room.php"><i class="fas fa-door-open"></i>Room</a>
            <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
        </div>
        <div class="nav-section">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
        </div>
    </div>
    <!-- ============================================================================
         MAIN CONTENT
         ============================================================================ -->
    <div class="main-content">
        <div class="room-header">
            <h1>Room Availability</h1>
            <div class="room-header-right-section">
                <div class="room-type-nav">
                    <a href="?type=<?php echo urlencode($prevRoomType); ?>" class="nav-arrow"><i class="fas fa-chevron-left"></i></a>
                    <h2 class="room-type-label selected"><?php echo htmlspecialchars($roomType); ?></h2>
                    <a href="?type=<?php echo urlencode($nextRoomType); ?>" class="nav-arrow"><i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Search Room Number">
                    <button id="clearSearchBtn" style="display:none; position:absolute; right:5px; top:50%; transform:translateY(-50%); border:none; background:transparent; color:#888; font-size:1.2rem; cursor:pointer;">&times;</button>
                </div>
            </div>
        </div>
        <div class="room-grid" id="roomGrid">
            <?php foreach ($rooms as $room): ?>
                <div class="room-card<?php echo ($room['RoomType'] !== $roomType) ? ' faded-room' : ''; ?>" data-room="<?php echo $room['RoomNumber']; ?>" data-status="<?php echo $room['Status']; ?>" data-type="<?php echo $room['RoomType']; ?>">
                    <div class="room-number"><?php echo $room['RoomNumber']; ?></div>
                    <div class="room-status-label"><?php echo $room['Status']; ?></div>
                    <?php if ($room['RoomType'] !== $roomType): ?>
                        <div class="room-type-badge" style="font-size:0.85rem;color:#888;margin-bottom:0.5rem;">(<?php echo htmlspecialchars($room['RoomType']); ?>)</div>
                    <?php endif; ?>
                    <button class="edit-btn" onclick="openEditModal(<?php echo $room['RoomNumber']; ?>, '<?php echo $room['Status']; ?>')">Edit</button>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="legend">
            <div class="legend-item"><span class="legend-badge legend-available"></span>Available</div>
            <div class="legend-item"><span class="legend-badge legend-maintenance"></span>Maintenance</div>
            <div class="legend-item"><span class="legend-badge legend-cleaning"></span>Cleaning</div>
        </div>
    </div>
    <!-- ============================================================================
         EDIT ROOM MODAL
         ============================================================================ -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Room Status</h2>
            <form id="editForm" onsubmit="return false;">
                <div class="form-group">
                    <label for="editRoomNumber">Room Number</label>
                    <input type="text" id="editRoomNumber" readonly>
                </div>
                <div class="form-group">
                    <label for="editRoomStatus">Room Status</label>
                    <select id="editRoomStatus">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRoomStatus()">Save</button>
                </div>
            </form>
        </div>
    </div>
    <!-- ============================================================================
         JAVASCRIPT
         ============================================================================ -->
    <script>
        // Sidebar toggle menu functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        sidebarToggle.onclick = function() {
            sidebar.classList.toggle('active');
        };

        // Room search filter
        const searchInput = document.getElementById('searchInput');
        const roomGrid = document.getElementById('roomGrid');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        searchInput.oninput = function() {
            const val = searchInput.value.trim().toLowerCase();
            const cards = roomGrid.querySelectorAll('.room-card');
            if (val) {
                // Show all rooms that match the search, regardless of category
                cards.forEach(card => {
                    const roomNum = String(card.querySelector('.room-number').innerText).trim().toLowerCase();
                    card.style.display = roomNum.includes(val) ? '' : 'none';
                });
            } else {
                // Show only rooms from the selected category
                cards.forEach(card => {
                    if (card.getAttribute('data-type') === '<?php echo $roomType; ?>') {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }
            clearSearchBtn.style.display = val ? '' : 'none';
        };
        // On page load, show only selected category
        window.addEventListener('DOMContentLoaded', function() {
            searchInput.oninput();
        });
        // Pressing Enter focuses first visible result
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = searchInput.value.trim().toLowerCase();
                const cards = Array.from(roomGrid.querySelectorAll('.room-card')).filter(card => {
                    const roomNum = String(card.querySelector('.room-number').innerText).trim().toLowerCase();
                    return card.style.display !== 'none' && roomNum.includes(val);
                });
                if (cards.length > 0) {
                    cards[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    cards[0].classList.add('highlight-room');
                    setTimeout(() => cards[0].classList.remove('highlight-room'), 2000);
                }
            }
        });
        // Clear search button
        clearSearchBtn.onclick = function(e) {
            e.preventDefault();
            searchInput.value = '';
            const cards = roomGrid.querySelectorAll('.room-card');
            cards.forEach(card => { card.style.display = ''; });
            clearSearchBtn.style.display = 'none';
            searchInput.focus();
        };

        // Modal logic
        let currentRoom = null;
        function openEditModal(roomNumber, status) {
            currentRoom = roomNumber;
            document.getElementById('editRoomNumber').value = roomNumber;
            document.getElementById('editRoomStatus').value = status;
            document.getElementById('editModal').style.display = 'block';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        // Save room status (static update for now)
        function saveRoomStatus() {
            const newStatus = document.getElementById('editRoomStatus').value;
            const roomNumber = document.getElementById('editRoomNumber').value;
            const card = document.querySelector('.room-card[data-room="' + currentRoom + '"]');
            
            // Show loading state
            const saveBtn = document.querySelector('.btn-primary');
            const originalText = saveBtn.textContent;
            saveBtn.textContent = 'Saving...';
            saveBtn.disabled = true;
            
            // Make AJAX call to update database
            const formData = new FormData();
            formData.append('action', 'updateStatus');
            formData.append('roomNumber', roomNumber);
            formData.append('status', newStatus);
            
            fetch('room.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the card's data attribute
            if (card) {
                card.setAttribute('data-status', newStatus);
                        
                        // Update the displayed status text
                        const statusLabel = card.querySelector('.room-status-label');
                        if (statusLabel) {
                            statusLabel.textContent = newStatus;
                        }
                        
                        // Update the card's background color based on new status
                        card.className = card.className.replace(/room-card\[data-status="[^"]*"\]/g, '');
                        card.classList.add('room-card');
                        
                        // Add visual feedback
                        card.classList.add('highlight-room');
                        setTimeout(() => card.classList.remove('highlight-room'), 2000);
                    }
                    
            closeEditModal();
                    
                    // Show success message
                    alert('Room status updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating room status. Please try again.');
            })
            .finally(() => {
                // Reset button state
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            });
        }
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        if(roomTypeSelect && roomNumberSelect) {
            roomTypeSelect.addEventListener('change', async function() {
                const roomType = this.value;
                roomNumberSelect.innerHTML = '<option value="">Loading...</option>';
                const response = await fetch(`booking.php?roomType=${encodeURIComponent(roomType)}`);
                const rooms = await response.json(); 
                let options = '<option value="">Select Room</option>';
                rooms.forEach(room => {
                    options += `<option value="${room.RoomNumber}">${room.RoomNumber}</option>`;
                });
                roomNumberSelect.innerHTML = options;
            });
        }
    </script>
</body>
</html> 

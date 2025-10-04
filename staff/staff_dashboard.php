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

// AJAX handler for live data updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_live_data'])) {
    header('Content-Type: application/json');
    
    // Get Inventory Stats by Category
    function getInventoryStats($conn) {
        $categories = [
            'Toiletries' => [
                'keywords' => ['toothbrush', 'toothpaste', 'soap', 'shampoo', 'towel', 'toiletries'],
                'stock' => 0
            ],
            'Amenities' => [
                'keywords' => ['pillow', 'blanket', 'slippers', 'robe', 'amenities'],
                'stock' => 0
            ],
            'Food' => [
                'keywords' => ['food', 'snack', 'water', 'juice', 'meal', 'bread', 'fruit'],
                'stock' => 0
            ]
        ];
        $query = "SELECT ItemName, SUM(CurrentStocks) as stock FROM inventory GROUP BY ItemName";
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $item = strtolower($row['ItemName']);
                foreach ($categories as $cat => &$catData) {
                    foreach ($catData['keywords'] as $kw) {
                        if (strpos($item, $kw) !== false) {
                            $catData['stock'] += (int)$row['stock'];
                            break;
                        }
                    }
                }
            }
        }
        return [
            'Toiletries' => $categories['Toiletries']['stock'],
            'Amenities' => $categories['Amenities']['stock'],
            'Food' => $categories['Food']['stock']
        ];
    }

    // Get all live data
    $inventoryStats = getInventoryStats($conn);
    $newBooking = $conn->query("SELECT COUNT(*) as count FROM booking WHERE BookingStatus = 'Pending'")->fetch_assoc()['count'] ?? 0;
    $availableRoom = $conn->query("SELECT COUNT(*) as count FROM room WHERE RoomStatus = 'Available'")->fetch_assoc()['count'] ?? 0;
    $checkIn = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckInDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
    $checkOut = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckOutDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
    $reservation = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE Status != 'Cancelled'")->fetch_assoc()['count'] ?? 0;

    // Get recent activity
    $recentBookings = [];
    $recentQuery = "SELECT b.BookingID, b.RoomNumber, b.RoomType, b.BookingStatus, b.CheckInDate, 
                           CONCAT(s.FirstName, ' ', s.LastName) as GuestName
                    FROM booking b 
                    LEFT JOIN student s ON b.StudentID = s.StudentID 
                    WHERE b.BookingDate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY b.BookingDate DESC 
                    LIMIT 5";
    $recentResult = $conn->query($recentQuery);
    if ($recentResult) {
        while ($row = $recentResult->fetch_assoc()) {
            $recentBookings[] = $row;
        }
    }

    // Get low stock alerts
    $lowStockAlerts = [];
    $lowStockQuery = "SELECT ItemName, CurrentStocks, MinimumStocks 
                      FROM inventory 
                      WHERE CurrentStocks <= MinimumStocks 
                      ORDER BY CurrentStocks ASC 
                      LIMIT 5";
    $lowStockResult = $conn->query($lowStockQuery);
    if ($lowStockResult) {
        while ($row = $lowStockResult->fetch_assoc()) {
            $lowStockAlerts[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'newBooking' => $newBooking,
                'availableRoom' => $availableRoom,
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
                'reservation' => $reservation
            ],
            'inventory' => $inventoryStats,
            'recentBookings' => $recentBookings,
            'lowStockAlerts' => $lowStockAlerts,
            'lastUpdate' => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

// Get Inventory Stats by Category
function getInventoryStats() {
    global $conn;
    $categories = [
        'Toiletries' => [
            'keywords' => ['toothbrush', 'toothpaste', 'soap', 'shampoo', 'towel', 'toiletries'],
            'stock' => 0
        ],
        'Amenities' => [
            'keywords' => ['pillow', 'blanket', 'slippers', 'robe', 'amenities'],
            'stock' => 0
        ],
        'Food' => [
            'keywords' => ['food', 'snack', 'water', 'juice', 'meal', 'bread', 'fruit'],
            'stock' => 0
        ]
    ];
    $query = "SELECT ItemName, SUM(CurrentStocks) as stock FROM inventory GROUP BY ItemName";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $item = strtolower($row['ItemName']);
            foreach ($categories as $cat => &$catData) {
                foreach ($catData['keywords'] as $kw) {
                    if (strpos($item, $kw) !== false) {
                        $catData['stock'] += (int)$row['stock'];
                        break;
                    }
                }
            }
        }
    }
    return [
        'Toiletries' => $categories['Toiletries']['stock'],
        'Amenities' => $categories['Amenities']['stock'],
        'Food' => $categories['Food']['stock']
    ];
}

$inventoryStats = getInventoryStats();

// --- Dashboard Stats Queries ---
// New Booking: count of bookings with BookingStatus = 'Pending'
$newBooking = $conn->query("SELECT COUNT(*) as count FROM booking WHERE BookingStatus = 'Pending'")->fetch_assoc()['count'] ?? 0;
// Available Room: count of rooms with RoomStatus = 'Available'
$availableRoom = $conn->query("SELECT COUNT(*) as count FROM room WHERE RoomStatus = 'Available'")->fetch_assoc()['count'] ?? 0;
// Check In: count of bookings with today's CheckInDate
$checkIn = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckInDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
// Check Out: count of bookings with today's CheckOutDate
$checkOut = $conn->query("SELECT COUNT(*) as count FROM booking WHERE DATE(CheckOutDate) = CURDATE() AND BookingStatus = 'Confirmed'")->fetch_assoc()['count'] ?? 0;
// Reservation: count of all reservations (not cancelled)
$reservation = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE Status != 'Cancelled'")->fetch_assoc()['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="sDashboard_styles.css">
</head>
<body>
    <button class="hamburger" id="sidebarToggle" aria-label="Open sidebar">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <!-- Sidebar Navigation (copied from dashboard.php) -->
    <div class="sidebar">
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="sidebar-logo">
        <h4 class="sidebar-title">Villa Valore</h4>
        <div class="nav-section">
            <a class="nav-link active" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
            <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
        </div>
        <div class="nav-section">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard">
            <h1 style="margin-bottom: 2rem;">Staff Dashboard</h1>
            <div class="stats-container">
                <a href="booking.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="far fa-calendar-alt"></i></div>
                        <div class="stat-label">New Booking</div>
                        <div class="stat-value" id="new-booking"><?php echo $newBooking; ?></div>
                    </div>
                </a>
                <a href="room.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-bed"></i></div>
                        <div class="stat-label">Available Room</div>
                        <div class="stat-value" id="available-room"><?php echo $availableRoom; ?></div>
                    </div>
                </a>
                <a href="booking.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-door-open"></i></div>
                        <div class="stat-label">Check In</div>
                        <div class="stat-value" id="check-in"><?php echo $checkIn; ?></div>
                    </div>
                </a>
                <a href="booking.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-door-closed"></i></div>
                        <div class="stat-label">Check Out</div>
                        <div class="stat-value" id="check-out"><?php echo $checkOut; ?></div>
                    </div>
                </a>
                <a href="reservation.php" class="stat-link">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="far fa-calendar-check"></i></div>
                        <div class="stat-label">Reservation</div>
                        <div class="stat-value" id="reservation"><?php echo $reservation; ?></div>
                    </div>
                </a>
            </div>
            <a href="staff_inventory.php" class="inventory-link">
                <div class="inventory-card">
                    <h2>Inventory</h2>
                    <div class="inventory-table-modern">
                        <div class="inventory-header">
                            <span>Category</span>
                            <span>Available Stock</span>
                        </div>
                        <div class="inventory-row">
                            <span class="inventory-icon"><i class="fas fa-suitcase"></i></span>
                            <span>Toiletries</span>
                            <span class="inventory-value" id="toiletries-stock"><?php echo $inventoryStats['Toiletries']; ?></span>
                        </div>
                        <div class="inventory-row">
                            <span class="inventory-icon"><i class="fas fa-bed"></i></span>
                            <span>Amenities</span>
                            <span class="inventory-value" id="amenities-stock"><?php echo $inventoryStats['Amenities']; ?></span>
                        </div>
                        <div class="inventory-row">
                            <span class="inventory-icon"><i class="fas fa-utensils"></i></span>
                            <span>Food</span>
                            <span class="inventory-value" id="food-stock"><?php echo $inventoryStats['Food']; ?></span>
                        </div>
                    </div>
                </div>
            </a>

            <!-- Live Data Sections -->
            <div class="live-data-container">
                <!-- Removed Recent Bookings and Low Stock Alerts sections -->
            </div>

            <!-- Last Update Indicator -->
            <div class="update-indicator" id="update-indicator">
                <span>Last updated: <span id="last-update-time"><?php echo date('Y-m-d H:i:s'); ?></span></span>
                <button class="refresh-btn" id="manual-refresh-btn" title="Refresh data">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>
    <script src="sDashboard_index.js"></script>
</body>
</html> 

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
// CREATE TABLE IF NOT EXISTS
// ============================================================================
$createTableSQL = "CREATE TABLE IF NOT EXISTS guest_requests (
    RequestID VARCHAR(20) PRIMARY KEY,
    GuestName VARCHAR(100) NOT NULL,
    RoomNumber INT NOT NULL,
    RequestDetails TEXT NOT NULL,
    Priority ENUM('Low', 'High') DEFAULT 'Low',
    Status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    RequestTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (Status),
    INDEX idx_priority (Priority),
    INDEX idx_room (RoomNumber)
)";

if (!$conn->query($createTableSQL)) {
    die("Error creating table: " . $conn->error);
}

// ============================================================================
// FUNCTION TO GENERATE REQUEST ID
// ============================================================================
function generateRequestID($conn) {
    $today = date('mdY'); // Format: MMDDYYYY
    $prefix = "GRQ-{$today}-";
    
    // Get the highest sequence number for today
    $sql = "SELECT RequestID FROM guest_requests WHERE RequestID LIKE ? ORDER BY RequestID DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $likePattern = $prefix . "%";
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastID = $row['RequestID'];
        // Extract the sequence number and increment it
        $sequence = intval(substr($lastID, -4)) + 1;
    } else {
        $sequence = 1;
    }
    
    $stmt->close();
    
    // Format sequence number with leading zeros (4 digits)
    return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

// ============================================================================
// INSERT SAMPLE DATA IF TABLE IS EMPTY
// ============================================================================
$checkDataSQL = "SELECT COUNT(*) as count FROM guest_requests";
$result = $conn->query($checkDataSQL);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Generate sample request IDs
    $sampleDataSQL = "
    INSERT INTO guest_requests (RequestID, GuestName, RoomNumber, RequestDetails, Priority, Status, RequestTime) VALUES
    (?, 'John Doe', 101, 'Extra towels needed', 'High', 'Pending', NOW()),
    (?, 'Jane Smith', 205, 'Wake-up call at 7 AM', 'Low', 'Completed', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
    (?, 'Mike Johnson', 302, 'Room service - dinner menu', 'Low', 'In Progress', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
    (?, 'Sarah Wilson', 150, 'Fix air conditioning', 'High', 'Pending', NOW()),
    (?, 'David Brown', 208, 'Extra pillows', 'Low', 'Completed', DATE_SUB(NOW(), INTERVAL 3 HOUR))
    ";
    
    $stmt = $conn->prepare($sampleDataSQL);
    $requestID1 = generateRequestID($conn);
    $requestID2 = generateRequestID($conn);
    $requestID3 = generateRequestID($conn);
    $requestID4 = generateRequestID($conn);
    $requestID5 = generateRequestID($conn);
    
    $stmt->bind_param("sssss", $requestID1, $requestID2, $requestID3, $requestID4, $requestID5);
    
    if (!$stmt->execute()) {
        die("Error inserting sample data: " . $conn->error);
    }
    $stmt->close();
}

// ============================================================================
// AJAX HANDLERS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // Mark request as complete
    if ($action === 'complete_request' && isset($_POST['request_id'])) {
        $requestId = $conn->real_escape_string($_POST['request_id']);
        $stmt = $conn->prepare("UPDATE guest_requests SET Status = 'Completed' WHERE RequestID = ?");
        $stmt->bind_param("s", $requestId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request marked as completed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update request status.']);
        }
        $stmt->close();
        exit;
    }

    // Update or Create request
    if ($action === 'save_request') {
        $requestId = isset($_POST['request_id']) && !empty($_POST['request_id']) ? $conn->real_escape_string($_POST['request_id']) : null;
        $guestName = $conn->real_escape_string($_POST['guestName']);
        $roomNumber = intval($_POST['roomNumber']);
        $requestDetails = $conn->real_escape_string($_POST['requestDetails']);
        $priority = $conn->real_escape_string($_POST['priority']);
        $status = $conn->real_escape_string($_POST['status']);

        if ($requestId) { // Update existing request
            $stmt = $conn->prepare("UPDATE guest_requests SET GuestName = ?, RoomNumber = ?, RequestDetails = ?, Priority = ?, Status = ? WHERE RequestID = ?");
            $stmt->bind_param("sissss", $guestName, $roomNumber, $requestDetails, $priority, $status, $requestId);
        } else { // Create new request
            $newRequestID = generateRequestID($conn);
            $stmt = $conn->prepare("INSERT INTO guest_requests (RequestID, GuestName, RoomNumber, RequestDetails, Priority, Status, RequestTime) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssisss", $newRequestID, $guestName, $roomNumber, $requestDetails, $priority, $status);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Request saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error saving request: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ============================================================================
// FETCH DATA FOR PAGE LOAD
// ============================================================================
// Orders by status first, then by time, so "Pending" always appears at the top.
$sql = "SELECT RequestID, GuestName, RoomNumber, RequestDetails, Priority, RequestTime, Status FROM guest_requests ORDER BY FIELD(Status, 'Pending', 'In Progress', 'Completed', 'Cancelled'), RequestTime DESC";
$result = $conn->query($sql);
$guestRequests = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $guestRequests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Guest Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* BASE LAYOUT */
        :root {
            --sidebar-width: 200px;
            --primary-color: #008000;
            --light-grey: #f0f2f5;
            --text-primary: #1a202c;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --success-bg: #c6f6d5; --success-text: #2f855a;
            --error-bg: #fed7d7; --error-text: #c53030;
            --pending-bg: #feebc8; --pending-text: #975a16;
            --completed-bg: #c6f6d5; --completed-text: #2f855a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--light-grey); display: flex; color: var(--text-primary); }

        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: var(--primary-color); min-height: 100vh; padding: 0.5rem; color: white; position: fixed; left: 0; top: 0; bottom: 0; transition: left 0.3s; z-index: 1000; }
        .sidebar-title { color: white; font-size: 1.4rem; font-weight: 600; margin-bottom: 1.5rem; padding: 1rem; text-align: center; }
        .nav-link { display: flex; align-items: center; padding: 0.6rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; transition: background-color 0.2s; border-radius: 6px; }
        .nav-link:hover, .nav-link.active { background-color: rgba(255, 255, 255, 0.15); }
        .nav-link i { margin-right: 0.85rem; width: 20px; text-align: center; font-size: 1.1em; }

        .sidebar-logo {
            display: block;
            margin: 1.5rem auto;
            width: 80px;
            height: auto;
        }

        /* MAIN CONTENT & HEADER */
        .main-content { flex: 1; padding: 2rem; margin-left: var(--sidebar-width); transition: margin-left 0.3s; }
        .request-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .request-header h1 { font-size: 2.1rem; font-weight: 700; }
        .header-controls { display: flex; align-items: center; gap: 0.8rem; }
        .search-wrapper { position: relative; }
        .search-input { padding: 0.6rem 1rem 0.6rem 2.2rem; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; font-size: 0.9rem; width: 200px; outline: none; transition: border-color 0.2s; }
        .search-input:focus { border-color: var(--primary-color); }
        .search-icon { position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }

        /* BUTTONS */
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-primary:hover { background: #006600; }
        .btn-secondary { background: #fff; color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-secondary:hover { background: #f7fafc; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
        .btn-confirm { background-color: var(--success-bg); color: var(--success-text); }
        .btn-confirm:hover { background-color: #9ae6b4; }

        /* TABLE CONTAINER */
        .table-container { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 1px 2px rgba(0,0,0,0.04); overflow: hidden; }
        .request-table { width: 100%; border-collapse: collapse; }
        .request-table th, .request-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .request-table th { background: #f9fafb; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
        .request-table tr:last-child td { border-bottom: none; }
        .request-table tr:hover { background-color: #f9fafb; }

        /* BADGES */
        .badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .priority-high { background: var(--error-bg); color: var(--error-text); }
        .priority-low { background: #bee3f8; color: #2c5282; }
        .status-pending { background: var(--pending-bg); color: var(--pending-text); }
        .status-completed { background: var(--completed-bg); color: var(--completed-text); }
        .status-in-progress { background: #faf089; color: #975a16; }

        /* MODAL - Modern Glassmorphism Style */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            overflow: auto;
            background: rgba(30, 41, 59, 0.25); /* darker overlay */
            backdrop-filter: blur(2.5px);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            margin: 5% auto;
            padding: 2.5rem 2rem 2.5rem 2rem;
            border-radius: 22px;
            width: 95%;
            max-width: 480px;
            max-height: 85vh;
            overflow: auto;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18), 0 1.5px 8px rgba(0,0,0,0.08);
            animation: modalPopIn 0.35s cubic-bezier(.23,1.01,.32,1);
            border: 1.5px solid rgba(200, 200, 200, 0.18);
            position: relative;
        }
        .modal-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            position: relative;
        }
        .modal-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a202c;
            flex: 1;
            text-align: center;
        }
        .close-btn {
            position: absolute;
            right: 0.5rem;
            top: 0.2rem;
            color: #64748b;
            font-size: 2.1rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            transition: color 0.18s, transform 0.18s;
            z-index: 2;
        }
        .close-btn:hover {
            color: #008000;
            transform: scale(1.18) rotate(90deg);
        }
        #requestForm, #filterForm {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.45rem;
            font-size: 1rem;
            color: #334155;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(245, 245, 250, 0.85);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #008000;
            box-shadow: 0 0 0 2px rgba(0,128,0,0.10);
            background: #fff;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 90px;
        }
        .modal-footer {
            margin-top: 2.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 0.85rem;
            flex-shrink: 0;
        }
        .modal-footer .btn {
            padding: 0.55rem 1.3rem;
            font-size: 1rem;
            border-radius: 10px;
            font-weight: 700;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .modal-footer .btn-primary {
            background: linear-gradient(90deg, #008000 60%, #38b000 100%);
            color: #fff;
            border: none;
        }
        .modal-footer .btn-primary:hover {
            background: linear-gradient(90deg, #006600 60%, #008000 100%);
            color: #fff;
        }
        .modal-footer .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1.5px solid #e2e8f0;
        }
        .modal-footer .btn-secondary:hover {
            background: #e2e8f0;
            color: #008000;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalPopIn {
            0% { opacity: 0; transform: scale(0.92) translateY(40px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        /* Responsive Modal */
        @media (max-width: 600px) {
            .modal-content {
                padding: 1.2rem 0.5rem 2.2rem 0.5rem;
                max-width: 98vw;
                max-height: 95vh;
            }
            .modal-title { font-size: 1.15rem; }
        }
        
        /* NOTIFICATION */
        #notification { position: fixed; bottom: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 8px; color: white; font-weight: 600; z-index: 2000; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s, transform 0.3s; transform: translateY(20px); }
        #notification.show { opacity: 1; visibility: visible; transform: translateY(0); }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { left: calc(-1 * var(--sidebar-width) - 20px); }
            .sidebar.active { left: 0; box-shadow: 2px 0 8px rgba(0,0,0,0.1); }
            .hamburger { display: flex; position: fixed; top: 1rem; left: 1rem; z-index: 1100; background: var(--primary-color); border: none; border-radius: 6px; cursor: pointer; padding: 0.5rem; }
            .hamburger span { display: block; width: 22px; height: 3px; background: #fff; margin: 4px 0; border-radius: 2px; }
            .request-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <button class="hamburger" id="sidebarToggle" aria-label="Open sidebar"><span></span><span></span><span></span></button>
    <div class="sidebar">
        <img src="images/villavalorelogo.png" alt="Villa Valore Logo" class="sidebar-logo">
        <h4 class="sidebar-title">Villa Valore</h4>
        <div class="nav-section">
            <a class="nav-link" href="staff_dashboard.php"><i class="fas fa-th-large"></i>Dashboard</a>
            <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i>Reservation</a>
            <a class="nav-link" href="booking.php"><i class="fas fa-book"></i>Booking</a>
            <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i>Room</a>
            <a class="nav-link active" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
        </div>
        <div class="nav-section">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="request-header">
            <h1>Guest Requests</h1>
            <div class="header-controls">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search requests...">
                </div>
                <button class="btn btn-secondary" id="filterBtn"><i class="fas fa-filter"></i> Filter</button>
                <button class="btn btn-primary" id="newRequestBtn"><i class="fas fa-plus"></i> New Request</button>
            </div>
        </div>
        <div class="table-container">
            <table class="request-table" id="requestTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Request Details</th>
                        <th>Priority</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="requestTableBody">
                    <?php if (!empty($guestRequests)): ?>
                        <?php foreach ($guestRequests as $request): ?>
                            <tr data-request-id="<?php echo $request['RequestID']; ?>">
                                <td><?php echo htmlspecialchars($request['RequestID']); ?></td>
                                <td><?php echo htmlspecialchars($request['GuestName']); ?></td>
                                <td><?php echo htmlspecialchars($request['RoomNumber']); ?></td>
                                <td><?php echo htmlspecialchars($request['RequestDetails']); ?></td>
                                <td><span class="badge priority-<?php echo strtolower(htmlspecialchars($request['Priority'])); ?>"><?php echo htmlspecialchars($request['Priority']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('M d, g:i A', strtotime($request['RequestTime']))); ?></td>
                                <td><span class="badge status-<?php echo str_replace(' ', '-', strtolower(htmlspecialchars($request['Status']))); ?>"><?php echo htmlspecialchars($request['Status']); ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary edit-btn"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-confirm complete-btn"><i class="fas fa-check"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; padding: 2rem;">No guest requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit/New Request Modal -->
    <div id="requestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Edit Request</h2>
                <span class="close-btn">&times;</span>
            </div>
            <form id="requestForm">
                <input type="hidden" id="requestID" name="requestID">
                <div class="form-group">
                    <label for="guestName">Guest Name</label>
                    <input type="text" id="guestName" name="guestName" required>
                </div>
                <div class="form-group">
                    <label for="roomNumber">Room Number</label>
                    <input type="number" id="roomNumber" name="roomNumber" required>
                </div>
                <div class="form-group">
                    <label for="requestDetails">Request Details</label>
                    <textarea id="requestDetails" name="requestDetails" required></textarea>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="Low">Low</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-btn-footer">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Filter Requests</h2>
                <span class="close-btn">&times;</span>
            </div>
            <form id="filterForm">
                <div class="form-group">
                    <label for="filterPriority">Priority</label>
                    <select id="filterPriority">
                        <option value="">All</option>
                        <option value="High">High</option>
                        <option value="Low">Low</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterStatus">Status</label>
                    <select id="filterStatus">
                        <option value="">All</option>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="clearFilterBtn">Clear</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('requestModal');
        const filterModal = document.getElementById('filterModal');
        const newRequestBtn = document.getElementById('newRequestBtn');
        const filterBtn = document.getElementById('filterBtn');
        const closeModalBtns = document.querySelectorAll('.close-btn, .close-btn-footer');
        const requestForm = document.getElementById('requestForm');
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');

        // Sidebar toggle
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
        }
        
        // --- MODAL HANDLING ---
        const openModal = (targetModal) => targetModal.style.display = 'flex';
        const closeModal = (targetModal) => targetModal.style.display = 'none';

        if (newRequestBtn) {
            newRequestBtn.addEventListener('click', () => {
                document.getElementById('modalTitle').textContent = 'New Request';
                requestForm.reset();
                document.getElementById('requestID').value = '';
                openModal(modal);
            });
        }
        if (filterBtn) filterBtn.addEventListener('click', () => openModal(filterModal));
        
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                closeModal(modal);
                closeModal(filterModal);
            });
        });
        
        window.addEventListener('click', (event) => {
            if (event.target == modal) closeModal(modal);
            if (event.target == filterModal) closeModal(filterModal);
        });

        // --- EDIT & COMPLETE ACTIONS ---
        document.getElementById('requestTableBody').addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-btn');
            const completeBtn = e.target.closest('.complete-btn');

            if (editBtn) {
                const row = editBtn.closest('tr');
                // Populate modal with row data
                document.getElementById('modalTitle').textContent = 'Edit Request';
                document.getElementById('requestID').value = row.dataset.requestId;
                document.getElementById('guestName').value = row.cells[1].textContent;
                document.getElementById('roomNumber').value = row.cells[2].textContent;
                document.getElementById('requestDetails').value = row.cells[3].textContent;
                document.getElementById('priority').value = row.cells[4].textContent;
                document.getElementById('status').value = row.cells[6].textContent;
                openModal(modal);
            }

            if (completeBtn) {
                const requestId = completeBtn.closest('tr').dataset.requestId;
                if (confirm('Are you sure you want to mark this request as complete?')) {
                    fetch('guest_request.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `action=complete_request&request_id=${requestId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        showNotification(data.message, data.success);
                        if(data.success) setTimeout(() => location.reload(), 1500);
                    })
                    .catch(err => console.error(err));
                }
            }
        });

        // --- SAVE FORM ---
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'save_request');

            fetch('guest_request.php', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(res => res.json())
            .then(data => {
                showNotification(data.message, data.success);
                if (data.success) {
                    closeModal(modal);
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(err => console.error(err));
        });

        // --- FILTER & SEARCH ---
        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const priorityFilter = document.getElementById('filterPriority').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value.toLowerCase();

            Array.from(document.getElementById('requestTableBody').rows).forEach(row => {
                const guestName = row.cells[1].textContent.toLowerCase();
                const roomNumber = row.cells[2].textContent.toLowerCase();
                const details = row.cells[3].textContent.toLowerCase();
                const priority = row.cells[4].textContent.toLowerCase();
                const status = row.cells[6].textContent.toLowerCase();

                const searchMatch = (
                    guestName.includes(searchTerm) ||
                    roomNumber.includes(searchTerm) ||
                    details.includes(searchTerm)
                );
                const priorityMatch = priorityFilter ? priority === priorityFilter : true;
                const statusMatch = statusFilter ? status === statusFilter : true;

                row.style.display = (searchMatch && priorityMatch && statusMatch) ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', applyFilters);
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            applyFilters();
            closeModal(filterModal);
        });
        document.getElementById('clearFilterBtn').addEventListener('click', () => {
            filterForm.reset();
            applyFilters();
        });

        // --- NOTIFICATION ---
        function showNotification(message, success) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.style.backgroundColor = success ? 'var(--success-bg)' : 'var(--error-bg)';
            notification.style.color = success ? 'var(--success-text)' : 'var(--error-text)';
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
    });
    </script>
</body>
</html> 

<?php include 'connections.php';

// --- STATISTICS QUERIES ---
// Cancellation Rate
$totalBookingsQuery = "SELECT COUNT(*) as total FROM booking";
$cancelledBookingsQuery = "SELECT COUNT(*) as cancelled FROM booking WHERE BookingStatus = 'Cancelled'";
$totalBookings = $conn->query($totalBookingsQuery)->fetch_assoc()['total'] ?? 0;
$cancelledBookings = $conn->query($cancelledBookingsQuery)->fetch_assoc()['cancelled'] ?? 0;
$cancellationRate = $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100) : 0;

// Occupancy Rate
$occupiedRoomsQuery = "SELECT COUNT(*) as occupied FROM room WHERE RoomStatus = 'Occupied'";
$totalRoomsQuery = "SELECT COUNT(*) as total FROM room";
$occupiedRooms = $conn->query($occupiedRoomsQuery)->fetch_assoc()['occupied'] ?? 0;
$totalRooms = $conn->query($totalRoomsQuery)->fetch_assoc()['total'] ?? 0;
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

// Customer Rating (Placeholder, since no table exists)
$customerRating = 'N/A'; // Set to static value or N/A

// --- SALES & REVENUE PER MONTH ---
$sales = [];
$revenue = [];
$months = [];
for ($m = 1; $m <= 12; $m++) {
    $monthName = date('F', mktime(0, 0, 0, $m, 10));
    $months[] = $monthName;
    // Total Sales: Completed bookings per month
    $salesQuery = "SELECT COUNT(*) as count FROM booking WHERE BookingStatus = 'Completed' AND MONTH(CheckInDate) = $m";
    $sales[] = (int)($conn->query($salesQuery)->fetch_assoc()['count'] ?? 0);
    // Total Revenue: Sum of TotalBill from payment table for Paid payments per month
    $revenueQuery = "SELECT SUM(TotalBill) as total FROM payment WHERE PaymentStatus = 'Paid' AND MONTH(PaymentDate) = $m";
    $revenue[] = (float)($conn->query($revenueQuery)->fetch_assoc()['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Villa Valore Hotel</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f5f6fa;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 180px;
            background: #008000;
            min-height: 100vh;
            padding: 0.5rem 0;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            transition: left 0.3s, width 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar-logo {
            width: 90px;
            height: 90px;
            margin: 1.5rem auto 1rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sidebar-logo img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            border-radius: 0;
            border: none;
            background: transparent;
            box-shadow: none;
        }

        .sidebar-title {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
            letter-spacing: 1px;
            /* Professional font styling */
            font-family: 'Montserrat', 'Segoe UI', Arial, sans-serif;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        .sidebar .nav-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-left: 1rem;
            gap: 0.5rem;
            margin-bottom: 0;
        }

        .sidebar .nav-section:not(:last-child) {
            margin-bottom: 1rem;
        }

        .sidebar .nav-link {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            padding: 0.35rem 0.6rem;
            color: white;
            text-decoration: none;
            font-size: 0.93rem;
            margin-bottom: 0.15rem;
            border-radius: 5px;
            width: 90%;
            transition: background-color 0.2s;
            height: 36px;
            gap: 0.5rem;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.13);
        }

        .sidebar .nav-link i {
            margin: 0;
            width: 22px;
            text-align: center;
            font-size: 1.08rem;
            opacity: 0.95;
        }

        .sidebar .nav-link span {
            font-size: 0.93rem;
            margin-top: 0;
            display: block;
            text-align: left;
            letter-spacing: 0.5px;
        }

        .sidebar .management-label {
            display: none;
        }

        .sidebar .toggle-btn {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            cursor: pointer;
            width: 90%;
            padding: 0 0.6rem;
            height: 36px;
            gap: 0.5rem;
        }

        .sidebar .toggle-btn::after {
            display: none;
        }

        .sidebar .submenu {
            margin-left: 0.3rem;
            display: none;
            width: 100%;
        }

        .sidebar .submenu.active {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .sidebar-nav-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            align-items: flex-start;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 200px; /* Match new sidebar width */
            overflow-x: hidden;
        }

        .dashboard {
            max-width: 1400px;
            margin: 0 auto;
        }

        h1 {
            color: #333;
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .stats-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); display: flex; flex-direction: column; align-items: center; }
        .stat-title { font-size: 1.1rem; color: #333; margin-bottom: 0.5rem; font-weight: 600; }
        .stat-value { font-size: 2.2rem; font-weight: bold; color: #147219; margin-bottom: 0.2rem; }
        .stat-label { color: #666; font-size: 1rem; }
        .chart-container { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 2rem; }
        .print-btn {
            background: #147219;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .print-btn i { margin-right: 0.5rem; }
        .print-btn:hover { background: #0e5a15; }
        @media print {
            body { background: white !important; }
            .sidebar, .print-btn { display: none !important; }
            .main-content { margin: 0 !important; padding: 0.5in !important; box-shadow: none !important; }
            .chart-container { page-break-inside: avoid; }
        }
        @media (max-width: 700px) {
            .sidebar {
                left: -200px;
                width: 180px;
            }
            .sidebar.active {
                left: 0;
            }
            .top-bar {
                left: 0;
                padding-left: 0.5rem;
            }
            .main-content {
                margin-left: 0;
            }
            .top-bar-toggle {
                display: block;
            }
        }
        @media (max-width: 600px) {
            .main-content {
                padding: 0.5rem;
            }
        }
        .section-toggle {
            background: none;
            border: none;
            color: #e6e6e6;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 0.1rem;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 0.35rem 0.6rem 0.35rem 0;
            outline: none;
            border-radius: 5px;
            transition: background 0.18s, color 0.18s;
        }
        .section-toggle:focus, .section-toggle:hover {
            color: #fff;
            background: rgba(255,255,255,0.10);
        }
        .section-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.08rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .section-label i {
            font-size: 1.15rem;
            opacity: 0.95;
        }
        .chevron {
            margin-left: auto;
            font-size: 1.1rem;
            transition: transform 0.25s cubic-bezier(.4,2,.6,1), color 0.18s;
        }
        .section-toggle[aria-expanded="false"] .chevron {
            transform: rotate(-90deg);
        }
        .section-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
            transition: max-height 0.2s, opacity 0.2s;
            overflow: hidden;
            opacity: 1;
            max-height: 500px;
            margin-bottom: 0.2rem;
        }
        .section-links.collapsed {
            opacity: 0;
            max-height: 0;
            pointer-events: none;
        }
        .sidebar .nav-section {
            margin-bottom: 0.2rem;
        }
        .sidebar-section-label {
            display: block;
            color: #fff;
            font-size: 0.93rem;
            font-weight: 400;
            opacity: 0.85;
            margin: 0.5rem 0 0.1rem 0.1rem;
            padding-left: 0.2rem;
            letter-spacing: 0.5px;
            cursor: default;
            user-select: none;
        }
        .search-filter-bar { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .search-input { padding: 0.7rem 2.5rem 0.7rem 2.5rem; border-radius: 1.2rem; border: none; background: #ededed; font-size: 1rem; width: 260px; outline: none; }
        .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #888; }
        .create-btn { padding: 0.7rem 1.5rem; border-radius: 1rem; border: 2px solid #222; background: #f5f6fa; font-size: 1rem; cursor: pointer; margin-left: 0.5rem; transition: background 0.2s, color 0.2s; }
        .create-btn:hover { background: #222; color: #fff; }
        .reservation-table { width: 100%; border-collapse: collapse; }
        .reservation-table th, .reservation-table td { padding: 1rem; border-bottom: 1px solid #f0f2f5; text-align: left; }
        .reservation-table th { background: #f8f9fa; color: #666; font-weight: 600; }
        .reservation-table td { color: #222; font-weight: 500; }
        .action-group { display: flex; gap: 0.3rem; justify-content: center; align-items: center; }
        .action-btn { display: inline-flex; align-items: center; justify-content: center; border: none; outline: none; border-radius: 50%; padding: 0.3rem; font-size: 1.1rem; background: none; cursor: pointer; transition: background 0.18s, color 0.18s; box-shadow: none; }
        .action-btn.edit-btn i { color: #008000; }
        .action-btn.view-btn i { color: #00b894; }
        .action-btn.delete-btn i { color: #e74c3c; }
        .action-btn:hover, .action-btn:focus { background: #e6f5ea; }
        .action-btn.delete-btn:hover, .action-btn.delete-btn:focus { background: #fbeaea; }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <img src="images/villavalorelogo.png" alt="Villa Valore Logo">
        </div>
        <div class="sidebar-title">Villa Valore</div>
        <div class="sidebar-nav-center">
            <div class="nav-section">
                <a class="nav-link" href="index.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Management</span>
                <a class="nav-link" href="student.php"><i class="fas fa-user"></i><span>Guest</span></a>
                <a class="nav-link" href="booking.php"><i class="fas fa-book"></i><span>Booking</span></a>
                <a class="nav-link" href="reservation.php"><i class="fas fa-calendar-check"></i><span>Reservation</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Resources</span>
                <a class="nav-link" href="room.php"><i class="fas fa-door-open"></i><span>Room</span></a>
                <a class="nav-link" href="inventory.php"><i class="fas fa-box"></i><span>Inventory</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Administration</span>
                <a class="nav-link" href="account.php"><i class="fas fa-user"></i><span>Account</span></a>
            </div>
            <div class="nav-section">
                <span class="sidebar-section-label">Finance & Analytics</span>
                <a class="nav-link" href="payment.php"><i class="fas fa-credit-card"></i><span>Invoices</span></a>
                <a class="nav-link" href="statistics.php"><i class="fas fa-chart-line"></i><span>Statistics</span></a>
            </div>
        </div>
    </div>
    <div class="top-bar" id="topBar">
        <button class="top-bar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar"><i class="fas fa-bars"></i></button>
        <div class="top-bar-right">
            <div class="top-bar-icon" title="Email"><i class="fas fa-envelope"></i></div>
            <div class="top-bar-icon" title="Notifications"><i class="fas fa-bell"></i></div>
            <div class="top-bar-account" title="Account">PB</div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard">
            <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Print</button>
            <h1>Statistics</h1>
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-title">Cancellation Rate</div>
                    <div class="stat-value"><?php echo $cancellationRate; ?>%</div>
                    <div class="stat-label">of all bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Occupancy Rate</div>
                    <div class="stat-value"><?php echo $occupancyRate; ?>%</div>
                    <div class="stat-label">of all rooms</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Customer Rating</div>
                    <div class="stat-value"><?php echo $customerRating; ?></div>
                    <div class="stat-label">(No data yet)</div>
                </div>
            </div>
            <div class="chart-container">
                <h2 style="margin-bottom: 1rem; color: #333;">Total Sales & Revenue (Monthly)</h2>
                <canvas id="salesRevenueChart" height="100"></canvas>
            </div>
    <script>
        // Sidebar toggle
        function toggleMenu(menuId) {
            const submenu = document.getElementById(menuId);
            submenu.classList.toggle('active');
        }
        // Chart.js Bar Graph
        const ctx = document.getElementById('salesRevenueChart').getContext('2d');
        const salesData = <?php echo json_encode($sales); ?>;
        const revenueData = <?php echo json_encode($revenue); ?>;
        const months = <?php echo json_encode($months); ?>;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Total Sales',
                        data: salesData,
                        backgroundColor: 'rgba(20, 114, 25, 0.7)',
                        borderColor: 'rgba(20, 114, 25, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Revenue',
                        data: revenueData,
                        backgroundColor: 'rgba(0, 128, 0, 0.4)',
                        borderColor: 'rgba(0, 128, 0, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#333' }
                    },
                    x: {
                        ticks: { color: '#333' }
                    }
                },
                plugins: {
                    legend: { labels: { color: '#333' } }
                }
            }
        });
    
    </script>
</body>
</html> 

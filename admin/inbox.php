<?php
include 'connections.php';

$sql = "SELECT RequestID, RoomNumber, RequestTimeStamp, RequestStatus, SpecificRequest FROM guest_request";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            width: 200px;
            background: #008000;
            min-height: 100vh;
            padding: 0.5rem;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
        }

        .sidebar-title {
            color: white;
            font-size: 1.4rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            padding: 1rem;
        }

        .nav-section {
            margin-bottom: 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            transition: background-color 0.2s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            opacity: 0.9;
        }

        .management-label {
            color: #90EE90;
            font-size: 0.8em;
            margin: 1rem 0 0.5rem 1rem;
        }

        .toggle-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }

        .toggle-btn::after {
            content: '▼';
            font-size: 0.7rem;
            margin-left: 0.5rem;
        }

        .submenu {
            margin-left: 1.5rem;
            display: none;
        }

        .submenu.active {
            display: block;
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
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #999;
      padding: 8px 12px;
      text-align: center;
    }
    th {
      background-color: #f2f2f2;
    }
    .add-btn {
      margin: 20px 0 10px;
      display: inline-block;
      padding: 10px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
    }
    .add-btn:hover {
      background-color: #45a049;
    }
    .modal-content input,
.modal-content select {
  display: block;
  width: 100%;
  margin-bottom: 10px;
  padding: 8px;
}
.modal-content button {
  padding: 10px 15px;
  margin-right: 10px;
  border: none;
  background-color: #4CAF50;
  color: white;
  border-radius: 5px;
  cursor: pointer;
}
.modal-content button:hover {
  background-color: #45a049;
}

  </style>
</head>
<body>

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

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard">
            <h1>Inbox</h1>
            <p>Manage your inbox messages from guest's guest here.</p>
  <br>
  
  <table>
    <tr>
      <th>Request ID</th>
      <th>Room Number</th>
      <th>Request Time</th>
      <th>Specific Request</th>
      <th>Status</th>
    </tr>

    <?php if ($result->num_rows > 0): ?>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row["RequestID"] ?></td>
          <td><?= $row["RoomNumber"] ?></td>
          <td><?= $row["RequestTimeStamp"] ?></td>
          <td><?= $row["SpecificRequest"] ?></td>
          <td><?= $row["RequestStatus"] ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="5">No records found</td></tr>
    <?php endif; ?>
  </table>
</div>

<script>
  function toggleMenu(id) {
    const submenu = document.getElementById(id);
    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
  }
</script>

</body>
</html>

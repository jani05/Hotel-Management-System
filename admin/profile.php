<?php
include 'connections.php';

// Fetch guest (account) info
$guest_sql = "SELECT * FROM account LIMIT 1";
$guest_result = $conn->query($guest_sql);

// Fetch booking info
$booking_sql = "SELECT * FROM booking LIMIT 1";
$booking_result = $conn->query($booking_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile Information</title>
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
        .container {
            display: flex;
            gap: 40px;
            flex-wrap: wrap;
        }
        .card {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            width: 45%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        p {
            margin: 8px 0;
        }
        span.label {
            font-weight: bold;
        }

        body {
          margin: 0;
          font-family: 'Segoe UI', sans-serif;
          background: #fff;
          color: #000;
        }

      .container {
          padding: 30px;
          max-width: 900px;
          margin: auto;
        }

      .header {
          display: flex;
          justify-content: space-between;
          align-items: center;
      }

      .small-title {
          margin: 0;
          font-size: 14px;
          color: #555;
      }

.section-title {
  margin-top: 30px;
  font-size: 16px;
  color: #333;
}

.update-btn {
  padding: 10px 20px;
  border: none;
  background: #e0e0e0;
  border-radius: 20px;
  cursor: pointer;
}

.account-info {
  display: flex;
  gap: 50px;
  align-items: flex-start;
  margin-top: 20px;
}

.profile {
  text-align: center;
}

.avatar {
  width: 120px;
  height: 120px;
  background: #d3d3d3;
  border-radius: 50%;
  margin: auto;
  background-image: url('https://via.placeholder.com/120');
  background-size: cover;
  background-position: center;
}

.info p {
  margin: 10px 0;
  font-size: 16px;
}

.password-btn {
  margin-top: 30px;
  padding: 10px 20px;
  background: #d3d3d3;
  border: none;
  border-radius: 20px;
  cursor: pointer;
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
            <h1>Profile Account</h1>
            <p>Manage your inbox messages from guest's guest here.</p>

         <button class="update-btn">+ Update Info</button>

         <h3 class="section-title">Account Information</h3>

    <div class="account-info">
      <div class="profile">
        <div class="avatar"></div>
        <p>Bio</p>
      </div>
      <div class="info">
        <p><strong>First Name</strong></p>
        <p><strong>Last Name</strong></p>
        <p><strong>Email</strong></p>
        <p><strong>Phone Number</strong></p>
        <p><strong>Position</strong></p>
        <p><strong>Status</strong></p>
      </div>
    </div>
        
<button class="password-btn">Change Password</button>
    </div>
    <script>
  function toggleMenu(id) {
    const submenu = document.getElementById(id);
    const toggle = submenu.previousElementSibling;
    submenu.style.display = submenu.style.display === 'block' ? 'none' : 'block';
    toggle.classList.toggle('expanded');
  }
</script>
</body>
</html>

<?php include 'connections.php';

// Create 'inventory' table if it doesn't exist
$inventoryTableSQL = "
CREATE TABLE IF NOT EXISTS inventory (
    ItemID INT AUTO_INCREMENT PRIMARY KEY,
    ItemName VARCHAR(100) NOT NULL,
    DateReceived DATE,
    DateExpiry DATE,
    Quantity INT NOT NULL,
    Price DECIMAL(10, 2) NOT NULL,
    CurrentStocks INT NOT NULL,
    Status ENUM ('In Stock', 'Low Stock', 'Out of Stock'),
    UNIQUE KEY (ItemName)
)";
if (!$conn->query($inventoryTableSQL)) {
    die("Error creating inventory table: " . $conn->error);
}

// Create 'stock_requests' table if it doesn't exist
$requestsTableSQL = "
CREATE TABLE IF NOT EXISTS stock_requests (
    RequestID INT AUTO_INCREMENT PRIMARY KEY,
    RequestedBy VARCHAR(100),
    Department VARCHAR(100),
    ProductName VARCHAR(100),
    RequestedQuantity INT,
    Reason TEXT,
    Priority ENUM('Low', 'Medium', 'High'),
    Notes TEXT,
    Status ENUM('Pending', 'Approved', 'Declined') DEFAULT 'Pending',
    RequestDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($requestsTableSQL)) {
    die("Error creating stock_requests table: " . $conn->error);
}

// Add Status column if it doesn't exist
$checkStatusColumn = "SHOW COLUMNS FROM stock_requests LIKE 'Status'";
$statusResult = $conn->query($checkStatusColumn);
if ($statusResult->num_rows == 0) {
    $addStatusColumn = "ALTER TABLE stock_requests ADD COLUMN Status ENUM('Pending', 'Approved', 'Declined') DEFAULT 'Pending' AFTER Notes";
    if (!$conn->query($addStatusColumn)) {
        die("Error adding Status column: " . $conn->error);
    }
}

// Insert sample data into 'inventory' if it's empty
$checkDataSQL = "SELECT COUNT(*) as count FROM inventory";
$result = $conn->query($checkDataSQL);
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $sampleDataSQL = "
    INSERT INTO inventory (ItemName, DateReceived, DateExpiry, Quantity, Price, CurrentStocks, Status) VALUES
    ('Shampoo', '2024-05-01', '2025-12-31', 100, 50.00, 150, 'In Stock'),
    ('Coffee Beans', '2024-04-15', '2024-11-15', 50, 100.00, 75, 'Low Stock'),
    ('Bed Linens', '2024-06-10', NULL, 200, 250.00, 200, 'In Stock'),
    ('Hand Soap', '2024-05-01', '2025-12-31', 300, 20.00, 15, 'Low Stock'),
    ('Light Bulbs', '2024-01-20', NULL, 500, 15.00, 450, 'In Stock'),
    ('Disinfectant', '2024-06-20', '2025-06-20', 40, 120.00, 'Out of Stock')
    ";
    if (!$conn->query($sampleDataSQL)) {
        die("Error inserting sample inventory data: " . $conn->error);
    }
}

// Insert sample data into 'stock_requests' if it's empty
$checkRequestsSQL = "SELECT COUNT(*) as count FROM stock_requests";
$requestsResult = $conn->query($checkRequestsSQL);
$requestsRow = $requestsResult->fetch_assoc();
if ($requestsRow['count'] == 0) {
    $sampleRequestsSQL = "
    INSERT INTO stock_requests (RequestedBy, Department, ProductName, RequestedQuantity, Reason, Priority, Notes, Status) VALUES
    ('John Smith', 'Housekeeping', 'Shampoo', 50, 'Running low on supplies', 'Medium', 'Need for guest rooms', 'Pending'),
    ('Maria Garcia', 'Kitchen', 'Coffee Beans', 25, 'High demand during breakfast', 'High', 'Urgent for morning service', 'Pending'),
    ('David Johnson', 'Maintenance', 'Light Bulbs', 100, 'Regular replacement schedule', 'Low', 'Preventive maintenance', 'Pending'),
    ('Sarah Wilson', 'Housekeeping', 'Hand Soap', 75, 'Guest bathroom supplies', 'Medium', 'Standard restocking', 'Pending'),
    ('Mike Brown', 'Kitchen', 'Disinfectant', 30, 'Kitchen cleaning supplies', 'High', 'Food safety requirement', 'Pending')
    ";
    if (!$conn->query($sampleRequestsSQL)) {
        die("Error inserting sample stock requests data: " . $conn->error);
    }
}

// ============================================================================
// AJAX HANDLER FOR STOCK REQUESTS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_request') {
    header('Content-Type: application/json');
    
    $requestedBy = $conn->real_escape_string($_POST['requestedBy']);
    $department = $conn->real_escape_string($_POST['department']);
    $priority = $conn->real_escape_string($_POST['priority']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $products = isset($_POST['products']) ? $_POST['products'] : [];

    if (empty($products)) {
        echo json_encode(['success' => false, 'message' => 'Please add at least one product to the request.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO stock_requests (RequestedBy, Department, ProductName, RequestedQuantity, Reason, Priority, Notes) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $insertedCount = 0;
    foreach ($products as $product) {
        if (!empty($product['name']) && !empty($product['quantity'])) {
            $stmt->bind_param("sssisss", $requestedBy, $department, $product['name'], $product['quantity'], $product['reason'], $priority, $notes);
            $stmt->execute();
            $insertedCount++;
        }
    }

    if ($insertedCount > 0) {
        echo json_encode(['success' => true, 'message' => 'Request submitted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit request. Please ensure you have filled out at least one product line.']);
    }
    $stmt->close();
    exit;
}

// ============================================================================
// AJAX HANDLER FOR APPROVING/DECLINING STOCK REQUESTS
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_request_status') {
    header('Content-Type: application/json');
    
    $requestID = intval($_POST['requestID']);
    $status = $conn->real_escape_string($_POST['status']);
    
    if (!in_array($status, ['Approved', 'Declined'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE stock_requests SET Status = ? WHERE RequestID = ?");
    $stmt->bind_param("si", $status, $requestID);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request ' . strtolower($status) . ' successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// UPDATE EDIT 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
      header('Content-Type: application/json');
      $id = intval($_POST['ItemID']);
      $name = $conn->real_escape_string($_POST['ItemName']);
      $dateReceived = !empty($_POST['DateReceived']) ? $conn->real_escape_string($_POST['DateReceived']) : null;
      $dateExpiry = !empty($_POST['DateExpiry']) ? $conn->real_escape_string($_POST['DateExpiry']) : null;
      $quantity = intval($_POST['Quantity']);
      $price = floatval($_POST['Price']);
      $currentStocks = intval($_POST['CurrentStocks']);
      $status = $conn->real_escape_string($_POST['Status']);

      $stmt = $conn->prepare("UPDATE inventory SET ItemName=?, DateReceived=?, DateExpiry=?, Quantity=?, Price=?, CurrentStocks=?, Status=? WHERE ItemID=?");
      $stmt->bind_param("sssidsii", $name, $dateReceived, $dateExpiry, $quantity, $price, $currentStocks, $status, $id);

      if ($stmt->execute()) {
        echo json_encode(['success' => true]);
      } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $conn->error]);
      }
      $stmt->close();
      exit;
    }

// ============================================================================
// FETCH DATA FOR PAGE LOAD
// ============================================================================
$sql = "SELECT ItemID, ItemName, DateReceived, DateExpiry, Quantity, Price, (Quantity * Price) as Total, CurrentStocks, Status FROM inventory";
$inventoryItems = [];
$result = $conn->query($sql);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $inventoryItems[] = $row;
    }
}

// Fetch stock requests data
$stockRequestsSQL = "SELECT RequestID, RequestedBy, Department, ProductName, RequestedQuantity, Reason, Priority, Notes, Status, RequestDate FROM stock_requests ORDER BY RequestDate DESC";
$stockRequests = [];
$stockRequestsResult = $conn->query($stockRequestsSQL);
if ($stockRequestsResult) {
    while($row = $stockRequestsResult->fetch_assoc()) {
        $stockRequests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 200px;
            --primary-color: #008000;
            --light-grey: #f0f2f5;
            --text-primary: #1a202c;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--light-grey); display: flex; color: var(--text-primary); }
        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 0.3rem;
            justify-content: center;
            align-items: center;
        }
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            outline: none;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            font-size: 1.05rem;
            color: #008000;
            background: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            box-shadow: none;
            padding: 0;
        }
        .action-btn.edit-btn {
            color: var(--action-edit);
        }
        .action-btn.edit-btn:hover {
            background: #e6f5ea;
            color: var(--theme-green-dark);
        }
        .action-btn.view-btn {
            color: var(--action-view);
        }
        .action-btn.view-btn:hover {
            background: #e6f5ea;
            color: #00916e;
        }
        .action-btn.delete-btn {
            color: var(--action-delete);
        }
        .action-btn.delete-btn:hover {
            background: #fbeaea;
            color: #c0392b;
        }
        .action-btn i {
            font-size: 1.1em;
        }
        /* Center the action group in the table cell */
        .reservation-table td:nth-child(7) {
            text-align: center;
            vertical-align: middle;
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

        /* Top Bar Styles */
        .top-bar {
            position: fixed;
            left: 180px;
            right: 0;
            top: 0;
            height: 60px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            z-index: 1001;
            padding: 0 2rem;
            transition: left 0.3s;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }
        .top-bar-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #333;
            cursor: pointer;
            position: relative;
        }
        .top-bar-account {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #bbb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        .top-bar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.7rem;
            color: #147219;
            margin-right: 1rem;
            cursor: pointer;
        }

        /* MAIN CONTENT & HEADER */
        .main-content { 
            flex: 1; 
            padding: 2rem; 
            margin-left: 180px; 
            transition: margin-left 0.3s; 
        }
        .inventory-header { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 1.5rem; 
        }
        .inventory-header h1 { 
            font-size: 2.1rem; 
            font-weight: 700; 
        }
        .header-controls { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
        }
        .search-wrapper { 
            position: relative; 
        }

        /* Logout Button Styles */
        .sidebar-logout {
            margin-top: auto;
            padding: 1rem;
            width: 100%;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .logout-btn i {
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                left: -180px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        /* BUTTONS */
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-primary:hover { background: #006600; }
        .btn-secondary { background: #fff; color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-secondary:hover { background: #f7fafc; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
        .btn-danger { background-color: #fed7d7; color: #c53030; }

        /* TABLE */
        .table-container { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 1px 2px rgba(0,0,0,0.04); overflow-x: auto; }
        .inventory-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .inventory-table th, .inventory-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
        .inventory-table th { background: #f9fafb; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
        .inventory-table tr:last-child td { border-bottom: none; }
        .inventory-table tr:hover { background-color: #f9fafb; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); animation: fadeIn 0.3s; align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 2rem; border-radius: 12px; width: 90%; max-width: 700px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: slideIn 0.3s; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-title { font-size: 1.5rem; font-weight: 600; }
        .close-btn { color: var(--text-secondary); font-size: 1.8rem; font-weight: bold; cursor: pointer; border: none; background: none; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; }
        .form-group input, .form-group select, .form-group textarea, .request-info input { 
            width: 100%; 
            padding: 0.7rem; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            transition: all 0.2s ease; 
            background-color: #fff;
        }
        .modal-content input:focus, 
        .modal-content select:focus, 
        .modal-content textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 128, 0, 0.1);
        }
        .modal-footer { display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem; margin-top: 2rem; }
        .modal-footer .btn {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }
        .modal-footer .btn.close-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
        }
        .modal-footer .btn.close-btn:hover {
            background-color: var(--light-grey);
        }

        /* REQUEST MODAL SPECIFIC STYLES */
        .request-info { display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem; align-items: center; margin-bottom: 1.5rem; }
        .product-details-table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .product-details-table th, .product-details-table td { padding: 0.5rem; border: 1px solid var(--border-color); text-align: center; }
        .product-details-table input { width: 100%; border: none; padding: 0.5rem; background: transparent; }
        .product-details-table input:focus { outline: 1px solid var(--primary-color); }
        .notes-textarea { width: 100%; padding: 0.7rem; border: 1px solid var(--border-color); border-radius: 8px; resize: vertical; }

        /* NOTIFICATION */
        #notification { position: fixed; bottom: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 8px; color: white; font-weight: 600; z-index: 2000; opacity: 0; visibility: hidden; transition: all 0.3s; transform: translateY(20px); }
        #notification.show { opacity: 1; visibility: visible; transform: translateY(0); }

        /* SEARCH HIGHLIGHTING */
        .search-highlight { 
            background: linear-gradient(135deg, #ffd700, #ffed4e); 
            color: #1a202c; 
            padding: 2px 4px; 
            border-radius: 3px; 
            font-weight: 600; 
            box-shadow: 0 1px 3px rgba(255, 215, 0, 0.3);
            animation: highlightPulse 0.6s ease-in-out;
        }
        
        @keyframes highlightPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* HOVER EFFECT FOR HIGHLIGHTED ROWS */
        .inventory-table tr:hover .search-highlight {
            background: linear-gradient(135deg, #ffed4e, #ffd700);
            box-shadow: 0 2px 6px rgba(255, 215, 0, 0.4);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(-50px) scale(0.95); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }
        
        /* Download icon button in table cell */
        .download-table-btn {
            background: none;
            border: none;
            color: #008000;
            border-radius: 50%;
            padding: 0.3rem;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
            margin: 0 auto; /* Center horizontally */
        }
        .download-table-btn i {
            font-size: 1.05em;
            color: #008000;
            transition: color 0.2s;
        }
        .download-table-btn:hover, .download-table-btn:focus {
            background: #e6f5ea;
        }
        .download-table-btn:hover i, .download-table-btn:focus i {
            color: #005c00;
        }
        /* Center the download button in the table cell */
        .reservation-table td:last-child {
            text-align: center;
            vertical-align: middle;
        }
        /* Delete Modal Buttons */
        .confirm-delete {
            background: var(--action-delete);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1.3rem;
            font-size: 1rem;
            font-weight: 600;
            margin-right: 0.7rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .confirm-delete:hover {
            background: #c0392b;
        }
        .cancel-delete {
            background: #f5f6fa;
            color: #222;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            padding: 0.6rem 1.3rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .cancel-delete:hover {
            background: #ededed;
            color: var(--theme-green);
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
        .sidebar .nav-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-left: 1rem;
            gap: 0.5rem;
            margin-bottom: 0;
        }
        .top-bar {
            position: fixed;
            left: 180px;
            right: 0;
            top: 0;
            height: 60px;
            background: #fff;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            z-index: 1001;
            padding: 0 2rem;
            transition: left 0.3s;
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }
        .top-bar-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #333;
            cursor: pointer;
            position: relative;
        }
        .top-bar-account {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #bbb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
        }
        .top-bar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.7rem;
            color: #147219;
            margin-right: 1rem;
            cursor: pointer;
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
        
        /* Priority Badge Styles */
        .priority-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .priority-low {
            background: #e8f5e8;
            color: #2d5a2d;
        }
        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }
        .priority-high {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Status Badge Styles */
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-declined {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Approve/Decline Action Buttons */
        .action-btn.approve-btn {
            color: #28a745;
        }
        .action-btn.approve-btn:hover {
            background: #d4edda;
            color: #155724;
        }
        .action-btn.decline-btn {
            color: #dc3545;
        }
        .action-btn.decline-btn:hover {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-text {
            color: #666;
            font-style: italic;
            font-size: 0.9rem;
        }
        
        /* Stock Requests Table Styles */
        .stock-requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .stock-requests-table th,
        .stock-requests-table td {
            padding: 0.8rem;
            border-bottom: 1px solid #f0f2f5;
            text-align: left;
            font-size: 0.9rem;
        }
        .stock-requests-table th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .stock-requests-table td {
            color: #222;
            font-weight: 500;
        }
        .stock-requests-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Modal Header Actions */
        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
        
        /* Stock Requests Modal Specific Styles */
        #stockRequestsModal .modal-content {
            width: 96vw !important;
            max-width: 1800px !important;
            height: 90vh !important;
            max-height: 95vh !important;
            border-radius: 12px !important;
            margin: 2vh auto !important;
            padding: 2rem 2vw !important;
            overflow-x: auto !important;
            overflow-y: auto !important;
            box-sizing: border-box;
        }
        
        #stockRequestsModal .table-container {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        #stockRequestsModal .inventory-table {
            margin: 0;
        }

        .header-content {
            max-width: 98vw;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        
        .full-window-content {
            padding: 2rem 2vw;
            max-width: 98vw;
            margin: 0 auto;
        }
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
                <div class="nav-section sidebar-logout">
                    <span class="sidebar-section-label">Logout</span>
                    <a class="logout-btn" href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
                </div>
            </div>
        </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
            <div class="inventory-header">
                <h1>Inventory</h1>
                <div class="header-controls">
                    <div class="search-filter-bar">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Search Inventory">
                        </div>                    </div>
                    <button class="btn btn-secondary" id="filterBtn"><i class="fas fa-filter"></i> Filter</button>
                    <button class="btn btn-primary" id="viewStockRequestsBtn"><i class="fas fa-list"></i> View Stock Requests</button>
                    <button class="btn btn-success" id="newRequestBtn"><i class="fas fa-plus"></i> New Item</button>
                </div>
            </div>
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Date Received</th>
                            <th>Date Expiry</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total Value</th>
                            <th>Current Stocks</th>
                            <th>Status</th>
                            <th>Actions</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        <?php if (!empty($inventoryItems)): ?>
                        <?php foreach ($inventoryItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['ItemID']); ?></td>
                                <td><?php echo htmlspecialchars($item['ItemName']); ?></td>
                                <td><?php echo htmlspecialchars($item['DateReceived']); ?></td>
                                <td><?php echo htmlspecialchars($item['DateExpiry']); ?></td>
                                <td><?php echo htmlspecialchars($item['Quantity']); ?></td>
                                <td>$<?php echo htmlspecialchars(number_format($item['Price'], 2)); ?></td>
                                <td>$<?php echo htmlspecialchars(number_format($item['Total'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($item['CurrentStocks']); ?></td>
                                <td><?php echo htmlspecialchars($item['Status']); ?></td>
                                <td>
                                    <div class="action-group">
                                        <button type="button" class="action-btn edit-btn"
                                            data-id="<?php echo $item['ItemID']; ?>"
                                            data-itemname="<?php echo htmlspecialchars($item['ItemName']); ?>"
                                            data-datereceived="<?php echo htmlspecialchars($item['DateReceived']); ?>"
                                            data-dateexpiry="<?php echo htmlspecialchars($item['DateExpiry']); ?>"
                                            data-quantity="<?php echo htmlspecialchars($item['Quantity']); ?>"
                                            data-price="<?php echo htmlspecialchars(number_format($item['Price'], 2)); ?>"
                                            data-total="<?php echo htmlspecialchars(number_format($item['Total'], 2)); ?>"
                                        ><i class="fas fa-edit"></i></button>
                                        <button type="button" class="action-btn view-btn"
                                            data-id="<?php echo $item['ItemID']; ?>"
                                            data-itemname="<?php echo htmlspecialchars($item['ItemName']); ?>"
                                            data-datereceived="<?php echo htmlspecialchars($item['DateReceived']); ?>"
                                            data-dateexpiry="<?php echo htmlspecialchars($item['DateExpiry']); ?>"
                                            data-quantity="<?php echo htmlspecialchars($item['Quantity']); ?>"
                                            data-price="<?php echo htmlspecialchars(number_format($item['Price'], 2)); ?>"
                                            data-total="<?php echo htmlspecialchars(number_format($item['Total'], 2)); ?>"
                                        ><i class="fas fa-eye"></i></button>
                                        <button type="button" class="action-btn delete-btn"
                                            data-id="<?php echo $item['ItemID']; ?>"
                                        ><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                                <td>
                                    <button class="download-table-btn" title="Download Row" onclick="showDownloadModal(event)">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr><td colspan="11" style="text-align:center; padding: 2rem;">No inventory items found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  
  <!-- Edit Modal -->
  <div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h2 class="modal-title">Edit Inventory Item</h2>
        <button class="close-btn" id="closeEditModal"><i class="fas fa-times"></i></button>
      </div>
      <form id="editForm">
        <input type="hidden" name="ItemID" id="editItemID">
        <div class="form-group">
          <label for="editItemName">Item Name:</label>
          <input type="text" name="ItemName" id="editItemName" required>
        </div>
        <div class="form-group">
          <label for="editDateReceived">Date Received:</label>
          <input type="date" name="DateReceived" id="editDateReceived">
        </div>
        <div class="form-group">
          <label for="editDateExpiry">Date Expiry:</label>
          <input type="date" name="DateExpiry" id="editDateExpiry">
        </div>
        <div class="form-group">
          <label for="editQuantity">Quantity:</label>
          <input type="number" name="Quantity" id="editQuantity" min="0" required>
        </div>
        <div class="form-group">
          <label for="editPrice">Price:</label>
          <input type="number" step="0.01" name="Price" id="editPrice" min="0" required>
        </div>
        <div class="form-group">
          <label for="editCurrentStocks">Current Stocks:</label>
          <input type="number" name="CurrentStocks" id="editCurrentStocks" min="0" required>
        </div>
        <div class="form-group">
          <label for="editStatus">Status:</label>
          <select name="Status" id="editStatus" required>
            <option value="In Stock">In Stock</option>
            <option value="Low Stock">Low Stock</option>
            <option value="Out of Stock">Out of Stock</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-btn">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
  <!-- View Modal -->
  <div id="viewModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h2 class="modal-title">View Inventory Item</h2>
        <button class="close-btn" id="closeViewModal"><i class="fas fa-times"></i></button>
      </div>
      <div id="viewDetails"></div>
    </div>
  </div>
  <!-- Download Modal -->
  <div id="downloadModal" class="modal">
    <div class="modal-content" style="width: 350px;">
      <button class="close-btn" id="closeDownloadModal" style="float:right;"><i class="fas fa-times"></i></button>
      <h2>Download Table</h2>
      <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
        <button class="filter-btn" id="copyTableBtn"><i class="fas fa-copy"></i> Copy </button>
        <button class="filter-btn" id="csvTableBtn"><i class="fas fa-file-csv"></i> CSV File</button>
        <button class="filter-btn" id="excelTableBtn"><i class="fas fa-file-excel"></i> Excel File</button>
        <button class="filter-btn" id="pdfTableBtn"><i class="fas fa-file-pdf"></i> PDF File</button>
        <button class="filter-btn" id="printTableBtn"><i class="fas fa-print"></i> Print File</button>
      </div>
    </div>
  </div>
  <script>
    // Download Modal logic
    const downloadModal = document.getElementById('downloadModal');
    const closeDownloadModal = document.getElementById('closeDownloadModal');

    function showDownloadModal(e) {
      e.preventDefault();
      downloadModal.style.display = 'flex';
    }

    closeDownloadModal.onclick = function() {
      downloadModal.style.display = 'none';
    };
    window.addEventListener('click', function(e) {
      if (e.target == downloadModal) downloadModal.style.display = 'none';
    });

    // Helper: get table data as array (optionally exclude actions/download columns)
    function getTableData(excludeActions = false) {
      const rows = Array.from(document.querySelectorAll('.inventory-table tbody tr'))
        .filter(row => row.style.display !== 'none');
      let headers = Array.from(document.querySelectorAll('.inventory-table thead th'));
      let colCount = headers.length;
      if (excludeActions) {
        // Remove last two columns: Actions and Download
        headers = headers.slice(0, -2);
        colCount = headers.length;
      } else {
        // Remove only Download column
        headers = headers.slice(0, -1);
        colCount = headers.length;
      }
      headers = headers.map(th => th.innerText.trim());
      const data = rows.map(row =>
        Array.from(row.querySelectorAll('td')).slice(0, colCount).map(td => td.innerText.trim())
      );
      return { headers, data };
    }

    // Copy Table
    document.getElementById('copyTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const text = [headers.join('\t'), ...data.map(row => row.join('\t'))].join('\n');
      navigator.clipboard.writeText(text).then(() => {
        alert('Table copied to clipboard!');
        downloadModal.style.display = 'none';
      });
    };

    // Download CSV
    document.getElementById('csvTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const csv = [headers.join(','), ...data.map(row => row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(','))].join('\r\n');
      const blob = new Blob([csv], {type: 'text/csv'});
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'inventory.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      downloadModal.style.display = 'none';
    };

    // Download Excel
    document.getElementById('excelTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Inventory");
      XLSX.writeFile(wb, "inventory.xlsx");
      downloadModal.style.display = 'none';
    };

    // Download PDF
    document.getElementById('pdfTableBtn').onclick = function() {
      const { headers, data } = getTableData();
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.autoTable({
        head: [headers],
        body: data,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [0,128,0] }
      });
      doc.save('inventory.pdf');
      downloadModal.style.display = 'none';
    };

    // Print Table (exclude actions/download columns)
    document.getElementById('printTableBtn').onclick = function() {
      const { headers, data } = getTableData(true);
      let html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">';
      html += '<thead><tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr></thead>';
      html += '<tbody>' + data.map(row => '<tr>' + row.map(cell => `<td>${cell}</td>`).join('') + '</tr>').join('') + '</tbody></table>';
      const win = window.open('', '', 'width=900,height=700');
      win.document.write('<html><head><title>Print Inventory</title></head><body>' + html + '</body></html>');
      win.document.close();
      win.print();
      downloadModal.style.display = 'none';
    };
  </script>
  <!-- jsPDF autotable plugin -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  
    <!-- Filter Modal -->
    <div id="filterModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 class="modal-title">Filter Inventory</h2>
                <button class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            <form id="filterForm">
                <div class="form-group">
                    <label for="filterStatus">Status</label>
                    <select id="filterStatus" class="form-group-input">
                        <option value="">All</option>
                        <option value="In Stock">In Stock</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="clearFilterBtn">Clear</button>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- New Request Modal -->
    <div id="newRequestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">New Item</h2>
                <button class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            <form id="requestForm">
                <div class="request-info">
                    <label>Date:</label><span><?php echo date('Y-m-d'); ?></span>
                    <label for="itemName">Item Name:</label><input type="text" id="itemName" required>
                    <label for="dateReceived">Date Received:</label><input type="date" id="dateReceived" required>
                    <label for="dateExpiry">Date Expiry:</label><input type="date" id="dateExpiry" required>
                    <label for="quantity">Quantity:</label><input type="number" id="quantity" required>
                    <label for="price">Price:</label><input type="number" id="price" required>
                    <label for="currentStocks">Current Stocks:</label><input type="number" id="currentStocks" required>
                    <label for="status">Status:</label><select id="status" class="form-group-input" required>
                        <option value="In Stock">In Stock</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <h4>Notes:</h4>
                    <textarea class="notes-textarea" id="notes" rows="3"></textarea>
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Modal -->
  <div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeDeleteModal">&times;</span>
    <h2>Delete Room</h2>
    <p>Are you sure you want to delete this item?</p>
    <div style="margin-top:1.5rem;">
    <button class="confirm-delete">Delete</button>
    <button class="cancel-delete">Cancel</button>
    </div>
  </div>
  </div>

    <!-- Stock Requests Modal -->
    <div id="stockRequestsModal" class="modal">
        <div class="modal-content" style="max-width: 95%; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2 class="modal-title">Stock Requests</h2>
                <div class="modal-header-actions">
                    <button class="btn btn-secondary btn-sm" id="refreshStockRequestsBtn" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="close-btn"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Requested By</th>
                            <th>Department</th>
                            <th>Product Name</th>
                            <th>Requested Quantity</th>
                            <th>Reason</th>
                            <th>Priority</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="stockRequestsTableBody">
                        <?php if (!empty($stockRequests)): ?>
                        <?php foreach ($stockRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['RequestID']); ?></td>
                                <td><?php echo htmlspecialchars($request['RequestedBy']); ?></td>
                                <td><?php echo htmlspecialchars($request['Department']); ?></td>
                                <td><?php echo htmlspecialchars($request['ProductName']); ?></td>
                                <td><?php echo htmlspecialchars($request['RequestedQuantity']); ?></td>
                                <td><?php echo htmlspecialchars($request['Reason']); ?></td>
                                <td>
                                    <span class="priority-badge priority-<?php echo strtolower($request['Priority']); ?>">
                                        <?php echo htmlspecialchars($request['Priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($request['Notes']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($request['Status']); ?>">
                                        <?php echo htmlspecialchars($request['Status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($request['RequestDate'])); ?></td>
                                <td>
                                    <?php if ($request['Status'] === 'Pending'): ?>
                                    <div class="action-group">
                                        <button class="action-btn approve-btn" 
                                                data-request-id="<?php echo $request['RequestID']; ?>" 
                                                title="Approve Request">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="action-btn decline-btn" 
                                                data-request-id="<?php echo $request['RequestID']; ?>" 
                                                title="Decline Request">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <span class="status-text"><?php echo $request['Status']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 2rem; color: #666;">
                                    No stock requests found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="notification"></div>

    <script>
          // Sidebar submenu toggle
          function toggleMenu(id) {
              var submenu = document.getElementById(id);
              submenu.classList.toggle('active');
          }

          // --- EDIT MODAL LOGIC ---
          const editModal = document.getElementById('editModal');
          const closeEditModal = document.getElementById('closeEditModal');
          function bindEditBtns() {
              document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.onclick = function() {
                editModal.style.display = 'flex';
                document.getElementById('editItemID').value = this.dataset.id;
                document.getElementById('editItemName').value = this.dataset.itemname;
                document.getElementById('editDateReceived').value = this.dataset.datereceived;
                document.getElementById('editDateExpiry').value = this.dataset.dateexpiry;
                document.getElementById('editQuantity').value = this.dataset.quantity;
                document.getElementById('editPrice').value = this.dataset.price.replace('$','');
                document.getElementById('editCurrentStocks').value = this.closest('tr').querySelector('td:nth-child(8)').textContent.trim();
                document.getElementById('editStatus').value = this.closest('tr').querySelector('td:nth-child(9)').textContent.trim();
            }
              });
          }
          bindEditBtns();
          closeEditModal.onclick = function() { editModal.style.display = 'none'; }
          // Save Edit
          const editForm = document.getElementById('editForm');
          editForm.onsubmit = function(e) {
              e.preventDefault();
              const formData = new FormData(editForm);
              formData.append('action', 'edit');
              fetch('inventory.php', {
            method: 'POST',
            body: formData
              })
              .then(res => res.json())
              .then(data => {
            if (data.success) {
                editModal.style.display = 'none';
                setTimeout(() => location.reload(), 200);
            } else {
                alert(data.message || 'Update failed.');
            }
              });
          }

          // --- VIEW MODAL LOGIC ---
          const viewModal = document.getElementById('viewModal');
          const closeViewModal = document.getElementById('closeViewModal');
          function bindViewBtns() {
              document.querySelectorAll('.view-btn').forEach(btn => {
            btn.onclick = function() {
                viewModal.style.display = 'flex';
                document.getElementById('viewDetails').innerHTML = `
              <p><label>Item ID:</label> <span>${this.dataset.id}</span></p>
              <p><label>Item Name:</label> <span>${this.dataset.itemname}</span></p>
              <p><label>Date Received:</label> <span>${this.dataset.datereceived}</span></p>
              <p><label>Date Expiry:</label> <span>${this.dataset.dateexpiry}</span></p>
              <p><label>Quantity:</label> <span>${this.dataset.quantity}</span></p>
              <p><label>Price:</label> <span>${this.dataset.price}</span></p>
              <p><label>Total Value:</label> <span>${this.dataset.total}</span></p>
                `;
            }
              });
          }
          bindViewBtns();
          closeViewModal.onclick = function() { viewModal.style.display = 'none'; }
          window.onclick = function(event) {
              if (event.target == editModal) editModal.style.display = 'none';
              if (event.target == viewModal) viewModal.style.display = 'none';
          }
        document.addEventListener('DOMContentLoaded', function() {
        // --- MODAL & SIDEBAR ---
        const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.onclick = () => sidebar.classList.toggle('active');
        }

        const modals = document.querySelectorAll('.modal');
        const openModalBtns = {
            'viewStockRequestsBtn': 'stockRequestsModal',
            'filterBtn': 'filterModal',
            'newRequestBtn': 'newRequestModal'
        };

        const openModal = (id) => {
            const modal = document.getElementById(id);
            if(modal) modal.style.display = 'flex';
        };
        const closeModal = (modal) => {
            if(modal) modal.style.display = 'none';
        };

        Object.entries(openModalBtns).forEach(([btnId, modalId]) => {
            const btn = document.getElementById(btnId);
            if(btn) btn.onclick = () => openModal(modalId);
        });
        
        document.body.addEventListener('click', function(e) {
            if (e.target.matches('.modal') || e.target.closest('.close-btn')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    closeModal(modal);
                }
            }
        });

        // --- FILTER & SEARCH ---
        const searchInput = document.getElementById('searchInput');
        const filterForm = document.getElementById('filterForm');
        const inventoryTableBody = document.getElementById('inventoryTableBody');

        function applyFilters() {
            console.log('applyFilters called'); // Debug log
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const statusFilterEl = document.getElementById('filterStatus');
            const statusFilter = statusFilterEl ? statusFilterEl.value.toLowerCase() : '';
            
            console.log('Search term:', searchTerm); // Debug log
            console.log('Status filter:', statusFilter); // Debug log
            
            if (!inventoryTableBody) {
                console.log('No inventory table body found'); // Debug log
                return;
            }

            const rows = Array.from(inventoryTableBody.rows);
            console.log('Number of rows to filter:', rows.length); // Debug log

            rows.forEach((row, index) => {
                // Get specific column values
                const itemId = row.cells[0] ? row.cells[0].textContent.toLowerCase().trim() : '';
                const itemName = row.cells[1] ? row.cells[1].textContent.toLowerCase().trim() : '';
                const status = row.cells[8] ? row.cells[8].textContent.toLowerCase().trim() : '';
                
                // Check if search term matches any of the specific columns
                const searchMatch = searchTerm === '' || 
                    itemId.includes(searchTerm) || 
                    itemName.includes(searchTerm) || 
                    status.includes(searchTerm);
                
                const statusMatch = statusFilter === '' || status === statusFilter;
                
                const shouldShow = searchMatch && statusMatch;
                row.style.display = shouldShow ? '' : 'none';
                
                // Highlight matching text if search term is not empty
                if (searchTerm !== '' && shouldShow) {
                    highlightMatchingText(row, searchTerm);
                } else {
                    removeHighlighting(row);
                }
                
                console.log(`Row ${index}: ID="${itemId}", Name="${itemName}", Status="${status}", searchMatch=${searchMatch}, statusMatch=${statusMatch}, show=${shouldShow}`); // Debug log
            });
        }

        function highlightMatchingText(row, searchTerm) {
            // Remove any existing highlighting
            removeHighlighting(row);
            
            // Highlight matching text in Item ID, Item Name, and Status columns
            const columnsToHighlight = [0, 1, 8]; // Item ID, Item Name, Status columns
            
            columnsToHighlight.forEach(colIndex => {
                const cell = row.cells[colIndex];
                if (cell && cell.textContent.toLowerCase().includes(searchTerm)) {
                    const originalText = cell.textContent;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    cell.innerHTML = originalText.replace(regex, '<mark class="search-highlight">$1</mark>');
                    
                    // Add a subtle animation effect
                    cell.style.transition = 'all 0.3s ease';
                    cell.style.backgroundColor = '#f8f9fa';
                    cell.style.borderRadius = '4px';
                    cell.style.padding = '8px 12px';
                    cell.style.margin = '2px 0';
                    cell.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                }
            });
        }

        function removeHighlighting(row) {
            const cells = row.cells;
            for (let i = 0; i < cells.length; i++) {
                const cell = cells[i];
                if (cell.innerHTML.includes('<mark class="search-highlight">')) {
                    cell.innerHTML = cell.textContent;
                    // Remove the styling
                    cell.style.backgroundColor = '';
                    cell.style.borderRadius = '';
                    cell.style.padding = '';
                    cell.style.margin = '';
                    cell.style.boxShadow = '';
                    cell.style.transition = '';
                }
            }
        }
        
        if (searchInput) {
            console.log('Search input found, adding event listener'); // Debug log
            searchInput.addEventListener('input', applyFilters);
            searchInput.addEventListener('keyup', applyFilters);
        } else {
            console.log('Search input not found!'); // Debug log
        }
        
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                applyFilters();
                closeModal(document.getElementById('filterModal'));
            });
        }
        
        const clearFilterBtn = document.getElementById('clearFilterBtn');
        if(clearFilterBtn) {
            clearFilterBtn.onclick = () => {
                if (filterForm) filterForm.reset();
                if (searchInput) searchInput.value = '';
                applyFilters();
            };
        }

        // Initial filter application
        setTimeout(() => {
            console.log('Applying initial filters'); // Debug log
            applyFilters();
        }, 100);

        // --- NEW REQUEST FORM ---
        const requestForm = document.getElementById('requestForm');
        const addProductBtn = document.getElementById('addProductBtn');
        const productDetailsTableBody = document.querySelector('#productDetailsTable tbody');

        const addProductRow = () => {
            if (!productDetailsTableBody) return;
            const row = productDetailsTableBody.insertRow();
            row.innerHTML = `
                <td><input type="text" class="itemname-input" name="ItemName[]" required></td>
                <td><input type="date" class="datereceived-input" name="DateReceived[]"></td>
                <td><input type="date" class="dateexpiry-input" name="DateExpiry[]"></td>
                <td><input type="number" class="quantity-input" name="Quantity[]" min="1" required></td>
                <td><input type="number" class="price-input" name="Price[]" min="0" step="0.01"></td>
                <td><input type="number" class="currentstocks-input" name="CurrentStocks[]" min="0"></td>
                <td>
                    <select class="status-input" name="Status[]">
                        <option value="In Stock">In Stock</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </td>
                <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()"><i class="fas fa-trash-alt"></i></button></td>
            `;
        };
        if (addProductBtn) {
            addProductBtn.onclick = addProductRow;
            addProductRow(); // Start with one row
        }

        if (requestForm) {
            requestForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const products = Array.from(productDetailsTableBody.rows).map(row => ({
                    name: row.querySelector('.itemname-input').value,
                    quantity: row.querySelector('.quantity-input').value,
                    reason: row.querySelector('.reason-input').value,
                }));

                const formData = new URLSearchParams();
                formData.append('action', 'submit_request');
                formData.append('requestedBy', document.getElementById('requestedBy').value);
                formData.append('department', document.getElementById('department').value);
                formData.append('priority', document.getElementById('priority').value);
                formData.append('notes', document.getElementById('notes').value);
                products.forEach((p, i) => {
                    if (p.name && p.quantity) {
                        formData.append(`products[${i}][name]`, p.name);
                        formData.append(`products[${i}][quantity]`, p.quantity);
                        formData.append(`products[${i}][reason]`, p.reason);
                    }
                });
                
                fetch('inventory.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    showNotification(data.message, data.success ? 'success' : 'error');
                    if (data.success) {
                        closeModal(document.getElementById('newRequestModal'));
                        requestForm.reset();
                        productDetailsTableBody.innerHTML = '';
                        addProductRow();
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showNotification('An unexpected error occurred.', 'error');
                });
            });
        }

        // --- NOTIFICATION ---
        function showNotification(message, type = 'success') {
            const notificationEl = document.getElementById('notification');
            if (!notificationEl) { // Create if it doesn't exist
                const el = document.createElement('div');
                el.id = 'notification';
                document.body.appendChild(el);
            }
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.style.backgroundColor = type === 'success' ? '#2f855a' : '#c53030';
            notification.classList.add('show');
            setTimeout(() => notification.classList.remove('show'), 3000);
            }
        });
        // Delete Modal
          const deleteModal = document.getElementById('deleteModal');
          const closeDeleteModal = document.getElementById('closeDeleteModal');
          let deleteBookingId = null;
          function bindDeleteBtns() {
            document.querySelectorAll('.delete-btn').forEach(btn => {
              btn.onclick = function() {
                deleteBookingId = this.dataset.id;
                deleteModal.style.display = 'block';
              }
            });
          }
          bindDeleteBtns();
          closeDeleteModal.onclick = function() { deleteModal.style.display = 'none'; }
          document.querySelector('#deleteModal .cancel-delete').onclick = function() {
            deleteModal.style.display = 'none';
            deleteBookingId = null;
          }
          document.querySelector('#deleteModal .confirm-delete').onclick = function() {
            if (!deleteBookingId) return;
            const formData = new FormData();
            formData.append('deleteRoom', 1);
            formData.append('RoomID', deleteBookingId);
            fetch('room.php', {
              method: 'POST',
              body: formData
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                deleteModal.style.display = 'none';
                setTimeout(() => location.reload(), 200);
              } else {
                alert('Delete failed.');
              }
            });
          }

        // --- STOCK REQUESTS REFRESH ---
        const refreshStockRequestsBtn = document.getElementById('refreshStockRequestsBtn');
        if (refreshStockRequestsBtn) {
            refreshStockRequestsBtn.addEventListener('click', function() {
                // Show loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                this.disabled = true;
                
                // Reload the page to refresh data
                setTimeout(() => {
                    location.reload();
                }, 500);
            });
        }

        // --- APPROVE/DECLINE STOCK REQUESTS ---
        function bindStockRequestActions() {
            // Approve buttons
            document.querySelectorAll('.approve-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestID = this.dataset.requestId;
                    updateRequestStatus(requestID, 'Approved');
                });
            });

            // Decline buttons
            document.querySelectorAll('.decline-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestID = this.dataset.requestId;
                    updateRequestStatus(requestID, 'Declined');
                });
            });
        }

        function updateRequestStatus(requestID, status) {
            const formData = new FormData();
            formData.append('action', 'update_request_status');
            formData.append('requestID', requestID);
            formData.append('status', status);

            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Reload the page to update the table
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Failed to update request status', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showNotification('An unexpected error occurred.', 'error');
            });
        }

        // Bind stock request actions when page loads
        document.addEventListener('DOMContentLoaded', function() {
            bindStockRequestActions();
        });
    </script>
</body>
</html>

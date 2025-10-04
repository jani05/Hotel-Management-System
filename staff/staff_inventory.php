<?php

// ============================================================================
// DATABASE CONNECTION & SETUP
// ============================================================================
$host = "localhost";
$user = "root";
$password = "";
$dbname = "hotel_reservation_systemdb";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create 'inventory' table if it doesn't exist
$inventoryTableSQL = "CREATE TABLE IF NOT EXISTS inventory (
    ItemID INT AUTO_INCREMENT PRIMARY KEY,
    ItemName VARCHAR(100) NOT NULL,
    DateReceived DATE,
    DateExpiry DATE,
    Quantity INT NOT NULL,
    Price DECIMAL(10, 2) NOT NULL,
    CurrentStocks INT NOT NULL,
    Status VARCHAR(50) DEFAULT 'In Stock',
    UNIQUE KEY (ItemName)
)";
if (!$conn->query($inventoryTableSQL)) {
    die("Error creating inventory table: " . $conn->error);
}

// Create 'stock_requests' table if it doesn't exist
$requestsTableSQL = "CREATE TABLE IF NOT EXISTS stock_requests (
    RequestID INT AUTO_INCREMENT PRIMARY KEY,
    RequestedBy VARCHAR(100),
    Department VARCHAR(100),
    ProductName VARCHAR(100),
    RequestedQuantity INT,
    Reason TEXT,
    Priority ENUM('Low', 'Medium', 'High'),
    Notes TEXT,
    RequestDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if (!$conn->query($requestsTableSQL)) {
    die("Error creating stock_requests table: " . $conn->error);
}

// Insert sample data into 'inventory' if it's empty
$checkDataSQL = "SELECT COUNT(*) as count FROM inventory";
$result = $conn->query($checkDataSQL);
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $sampleDataSQL = "INSERT INTO inventory (ItemName, DateReceived, DateExpiry, Quantity, Price, CurrentStocks, Status) VALUES
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
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: var(--primary-color); min-height: 100vh; padding: 0.5rem; color: white; position: fixed; left: 0; top: 0; bottom: 0; transition: left 0.3s; z-index: 1000; }
        .sidebar-logo {
            display: block;
            margin: 1.5rem auto;
            width: 80px;
            height: auto;
        }
        .sidebar-title { color: white; font-size: 1.4rem; font-weight: 600; margin-bottom: 1.5rem; padding: 1rem; text-align: center; }
        .nav-link { display: flex; align-items: center; padding: 0.6rem 1rem; color: white; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.25rem; transition: background-color 0.2s; border-radius: 6px; }
        .nav-link:hover, .nav-link.active { background-color: rgba(255, 255, 255, 0.15); }
        .nav-link i { margin-right: 0.85rem; width: 20px; text-align: center; font-size: 1.1em; }

        /* MAIN CONTENT & HEADER */
        .main-content { flex: 1; padding: 2rem; margin-left: var(--sidebar-width); transition: margin-left 0.3s; }
        .inventory-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .inventory-header h1 { font-size: 2.1rem; font-weight: 700; }
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
        
        /* RESPONSIVE */
        @media (max-width: 900px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar { left: calc(-1 * var(--sidebar-width) - 20px); }
            .sidebar.active { left: 0; }
            .hamburger { display: flex; position: fixed; top: 1rem; left: 1rem; z-index: 1100; background: var(--primary-color); border: none; border-radius: 6px; cursor: pointer; padding: 0.5rem; }
            .hamburger span { display: block; width: 22px; height: 3px; background: #fff; margin: 4px 0; border-radius: 2px; }
            .inventory-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
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
            <a class="nav-link" href="guest_request.php"><i class="fas fa-comment-dots"></i>Guest Request</a>
            <a class="nav-link active" href="staff_inventory.php"><i class="fas fa-box"></i>Inventory</a>
        </div>
        <div class="nav-section">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
            <div class="inventory-header">
                <h1>Inventory</h1>
                <div class="header-controls">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search inventory...">
                </div>
                <button class="btn btn-secondary" id="filterBtn"><i class="fas fa-filter"></i> Filter</button>
                <button class="btn btn-primary" id="sendRequestBtn"><i class="fas fa-plus"></i> Send Request</button>
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
                    </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding: 2rem;">No inventory items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
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
                <h2 class="modal-title">New Stock Request</h2>
                <button class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            <form id="requestForm">
                <div class="request-info">
                    <label>Date:</label><span><?php echo date('Y-m-d'); ?></span>
                    <label for="requestedBy">Requested By:</label><input type="text" id="requestedBy" required>
                    <label for="department">Department:</label><input type="text" id="department" required>
                </div>

                <h4>Product Details</h4>
                <table class="product-details-table" id="productDetailsTable">
                    <thead>
                        <tr><th>Product Name</th><th>Requested Qty</th><th>Reason</th><th></th></tr>
                    </thead>
                    <tbody>
                        <!-- Dynamic rows -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-secondary btn-sm" id="addProductBtn"><i class="fas fa-plus"></i> Add Product</button>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <h4>Priority Level:</h4>
                    <select id="priority" class="form-group-input" required>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <h4>Notes:</h4>
                    <textarea class="notes-textarea" id="notes" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-btn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // --- MODAL & SIDEBAR ---
        const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.onclick = () => sidebar.classList.toggle('active');
        }

        const modals = document.querySelectorAll('.modal');
        const openModalBtns = {
            'sendRequestBtn': 'newRequestModal',
            'filterBtn': 'filterModal'
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
                <td><input type="text" class="product-input" required></td>
                <td><input type="number" class="quantity-input" min="1" required></td>
                <td><input type="text" class="reason-input"></td>
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
                    name: row.querySelector('.product-input').value,
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
                
                fetch('staff_inventory.php', { method: 'POST', body: formData })
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
    </script>
</body>
</html>

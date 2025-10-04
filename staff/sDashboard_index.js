// Sidebar toggle menu functionality
function toggleMenu(menuId) {
    const submenu = document.getElementById(menuId);
    submenu.classList.toggle('active');
}
// Hamburger menu for sidebar
const sidebar = document.querySelector('.sidebar');
const hamburger = document.getElementById('sidebarToggle');
function closeSidebarOnOverlayClick(e) {
    if (window.innerWidth <= 900 && sidebar.classList.contains('active')) {
        if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
}
hamburger.addEventListener('click', function() {
    sidebar.classList.toggle('active');
});
document.addEventListener('click', closeSidebarOnOverlayClick);
window.addEventListener('resize', function() {
    if (window.innerWidth > 900) {
        sidebar.classList.remove('active');
    }
});

// LIVE DATA FUNCTIONALITY
function updateStatValue(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    element.textContent = newValue;
}

function updateLastUpdateTime() {
    const now = new Date();
    const formatted = now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0') + ' ' +
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0') + ':' +
        String(now.getSeconds()).padStart(2, '0');
    const lastUpdate = document.getElementById('last-update-time');
    if (lastUpdate) lastUpdate.textContent = formatted;
}

function fetchLiveData() {
    const formData = new FormData();
    formData.append('get_live_data', '1');
    fetch('staff_dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const stats = data.data.stats;
            updateStatValue('new-booking', stats.newBooking);
            updateStatValue('available-room', stats.availableRoom);
            updateStatValue('check-in', stats.checkIn);
            updateStatValue('check-out', stats.checkOut);
            updateStatValue('reservation', stats.reservation);
            const inventory = data.data.inventory;
            updateStatValue('toiletries-stock', inventory.Toiletries);
            updateStatValue('amenities-stock', inventory.Amenities);
            updateStatValue('food-stock', inventory.Food);
            updateLastUpdateTime();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    fetchLiveData(); // Initial fetch
    setInterval(fetchLiveData, 1000); // Fetch every 1 second
    var refreshBtn = document.getElementById('manual-refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            fetchLiveData();
        });
    }
}); 

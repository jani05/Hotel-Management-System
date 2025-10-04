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

// Enhanced Search Functionality
const searchInput = document.getElementById('searchInput');
const clearSearchBtn = document.getElementById('clearSearchBtn');
let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    if (query.length > 0) {
        clearSearchBtn.style.display = 'block';
    } else {
        clearSearchBtn.style.display = 'none';
    }
    // Debounce search to avoid too many requests
    searchTimeout = setTimeout(() => {
        performSearch(query);
    }, 300);
});

clearSearchBtn.addEventListener('click', function() {
    searchInput.value = '';
    clearSearchBtn.style.display = 'none';
    performSearch('');
});

function performSearch(query) {
    const currentUrl = new URL(window.location);
    if (query) {
        currentUrl.searchParams.set('search', query);
    } else {
        currentUrl.searchParams.delete('search');
    }
    window.location.href = currentUrl.toString();
}

// Enhanced Filter Functionality
const filterBtn = document.getElementById('filterBtn');
const filterDropdown = document.getElementById('filterDropdown');
const applyFilterBtn = document.getElementById('applyFilterBtn');
const clearFilterBtn = document.getElementById('clearFilterBtn');
const filterForm = document.getElementById('filterForm');

filterBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    filterDropdown.classList.toggle('active');
});

document.addEventListener('click', function(e) {
    if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
        filterDropdown.classList.remove('active');
    }
});

applyFilterBtn.addEventListener('click', function() {
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    // Preserve search query if exists
    const searchQuery = new URLSearchParams(window.location.search).get('search');
    if (searchQuery) {
        params.append('search', searchQuery);
    }
    window.location.href = window.location.pathname + '?' + params.toString();
});

clearFilterBtn.addEventListener('click', function() {
    filterForm.reset();
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.delete('status');
    currentUrl.searchParams.delete('room_type');
    currentUrl.searchParams.delete('checkin_date');
    window.location.href = currentUrl.toString();
});

// Enhanced Create Reservation
const createModal = document.getElementById('createModal');
const createBtn = document.getElementById('createBtn');
const closeCreateModal = document.getElementById('closeCreateModal');
const createForm = document.getElementById('createForm');
const createFormError = document.getElementById('createFormError');

createBtn.addEventListener('click', function() {
    createModal.style.display = 'block';
    createForm.reset();
    createFormError.style.display = 'none';
});

closeCreateModal.addEventListener('click', function() {
    createModal.style.display = 'none';
});

// Cancellation Modal Functionality
const cancellationModal = document.getElementById('cancellationModal');
const closeCancellationModal = document.getElementById('closeCancellationModal');
const cancellationForm = document.getElementById('cancellationForm');
const cancelCancellationBtn = document.getElementById('cancelCancellationBtn');

// Close cancellation modal
if (closeCancellationModal) {
    closeCancellationModal.addEventListener('click', function() {
        cancellationModal.style.display = 'none';
    });
}

if (cancelCancellationBtn) {
    cancelCancellationBtn.addEventListener('click', function() {
        cancellationModal.style.display = 'none';
    });
}

// Handle cancellation form submission
if (cancellationForm) {
    cancellationForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'cancel_reservation');
        
        try {
            const response = await fetch('reservation.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showNotification(result.message, 'success');
                cancellationModal.style.display = 'none';
                // Refresh the page to show updated status
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(result.message, 'error');
            }
        } catch (error) {
            console.error('Error cancelling reservation:', error);
            showNotification('Network error. Please try again.', 'error');
        }
    });
}

// Add event listeners for cancel buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.cancel-btn')) {
        const cancelBtn = e.target.closest('.cancel-btn');
        const reservationId = cancelBtn.getAttribute('data-id');
        const guestName = cancelBtn.getAttribute('data-guest');
        
        // Populate cancellation modal
        document.getElementById('cancellationReservationId').value = reservationId;
        document.getElementById('cancellationGuestName').value = guestName;
        document.getElementById('cancellationReason').value = '';
        
        // Show cancellation modal
        cancellationModal.style.display = 'block';
    }
});

// Real-time validation for create form
const createCheckIn = document.getElementById('createCheckIn');
const createCheckOut = document.getElementById('createCheckOut');
const createRoomNumber = document.getElementById('createRoomNumber');
const createRoomType = document.getElementById('createRoomType');

function validateCreateForm() {
    const checkIn = new Date(createCheckIn.value);
    const checkOut = new Date(createCheckOut.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    createFormError.style.display = 'none';
    if (checkIn < today) {
        showFormError('Check-in date cannot be in the past');
        return false;
    }
    if (checkOut <= checkIn) {
        showFormError('Check-out date must be after check-in date');
        return false;
    }
    if (!createRoomNumber.value) {
        showFormError('Please select a room');
        return false;
    }
    if (!createRoomType.value) {
        showFormError('Please select a room type');
        return false;
    }
    return true;
}

function showFormError(message) {
    createFormError.textContent = message;
    createFormError.style.display = 'block';
}

createForm.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!validateCreateForm()) {
        return;
    }
    // Submit form
    this.submit();
});

// Dynamically update Room Number options in create modal based on selected dates
if (createCheckIn && createCheckOut && createRoomNumber && createRoomType) {
    async function fetchAvailableRoomsForCreate() {
        console.log('fetchAvailableRoomsForCreate called');
        const checkIn = createCheckIn.value;
        const checkOut = createCheckOut.value;
        const roomType = createRoomType.value;
        createRoomNumber.innerHTML = '<option value="">Loading...</option>';
        if (!checkIn || !checkOut || !roomType) {
            createRoomNumber.innerHTML = '<option value="">Select a room</option>';
            return;
        }
        try {
            const response = await fetch(`reservation.php?getRooms=1&checkIn=${encodeURIComponent(checkIn)}&checkOut=${encodeURIComponent(checkOut)}&roomType=${encodeURIComponent(roomType)}`);
            if (!response.ok) throw new Error('Failed to fetch rooms');
            const rooms = await response.json();
            let options = '<option value="">Select a room</option>';
            rooms.forEach(room => {
                const label = `Room ${room.RoomNumber} (${room.Label})`;
                if (room.Available) {
                    options += `<option value="${room.RoomNumber}">${label}</option>`;
                } else {
                    options += `<option value="${room.RoomNumber}" disabled>${label}</option>`;
                }
            });
            createRoomNumber.innerHTML = options;
        } catch (err) {
            createRoomNumber.innerHTML = '<option value="">Error loading rooms</option>';
        }
    }
    createCheckIn.addEventListener('change', fetchAvailableRoomsForCreate);
    createCheckOut.addEventListener('change', fetchAvailableRoomsForCreate);
    createRoomType.addEventListener('change', fetchAvailableRoomsForCreate);
}

// Enhanced Edit Modal
const editModal = document.getElementById('editModal');
const closeEditModal = document.getElementById('closeEditModal');
const editFormError = document.getElementById('editFormError');
const editForm = document.getElementById('editForm');
const editRoomType = document.getElementById('editRoomType');
const editRoomNumber = document.getElementById('editRoomNumber');
const editCheckIn = document.getElementById('editCheckIn');
const editCheckOut = document.getElementById('editCheckOut');

// Replace Room Number input with a select (if not already)
if (editRoomNumber && editRoomNumber.tagName === 'INPUT') {
    const select = document.createElement('select');
    select.name = 'RoomNumber';
    select.id = 'editRoomNumber';
    select.required = true;
    editRoomNumber.parentNode.replaceChild(select, editRoomNumber);
}

// Helper to fetch and populate available rooms for edit modal
async function updateEditRoomNumbers(currentRoom = null) {
    const roomType = editRoomType.value;
    const checkIn = editCheckIn.value;
    const checkOut = editCheckOut.value;
    const roomNumberSelect = document.getElementById('editRoomNumber');
    if (!roomType || !checkIn || !checkOut) {
        roomNumberSelect.innerHTML = '<option value="">Select a room</option>';
        return;
    }
    roomNumberSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        let url = `reservation.php?getRooms=1&checkIn=${encodeURIComponent(checkIn)}&checkOut=${encodeURIComponent(checkOut)}&roomType=${encodeURIComponent(roomType)}`;
        if (currentRoom) {
            url += `&currentRoom=${encodeURIComponent(currentRoom)}`;
        }
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch rooms');
        const rooms = await response.json();
        let options = '<option value="">Select a room</option>';
        let found = false;
        rooms.forEach(room => {
            if (room.RoomNumber == currentRoom) found = true;
            options += `<option value="${room.RoomNumber}">${room.RoomNumber}</option>`;
        });
        // If currentRoom is not in the list, add it at the top
        if (currentRoom && !found) {
            options = `<option value="${currentRoom}">${currentRoom} (Current)</option>` + options;
        }
        roomNumberSelect.innerHTML = options;
        // Ensure the select always has the correct name attribute
        roomNumberSelect.setAttribute('name', 'RoomNumber');
        // Ensure the value is set to the current room
        if (currentRoom) {
            roomNumberSelect.value = currentRoom;
        }
    } catch (err) {
        roomNumberSelect.innerHTML = '<option value="">Error loading rooms</option>';
    }
}

// Add event listeners for dynamic room number update
if (editRoomType && editCheckIn && editCheckOut) {
    editRoomType.addEventListener('change', () => {
        updateEditRoomNumbers();
    });
    editCheckIn.addEventListener('change', () => {
        updateEditRoomNumbers();
    });
    editCheckOut.addEventListener('change', () => {
        updateEditRoomNumbers();
    });
}

// Enhanced View Modal
const viewModal = document.getElementById('viewModal');
const closeViewModal = document.getElementById('closeViewModal');

document.addEventListener('DOMContentLoaded', function() {
    // Edit Modal Logic
    const editModal = document.getElementById('editModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const editFormError = document.getElementById('editFormError');
    const editForm = document.getElementById('editForm');
    const editButtons = document.querySelectorAll('.edit-btn');
    
    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            editModal.style.display = 'block';
            editFormError.style.display = 'none';
            // Clear previous data
            document.getElementById('editReservationID').value = '';
            document.getElementById('editGuestName').value = '';
            document.getElementById('editCheckIn').value = '';
            document.getElementById('editCheckOut').value = '';
            document.getElementById('editRoomType').value = '';
            document.getElementById('editStatus').value = '';
            // Set new data from the clicked button
            const reservationId = this.getAttribute('data-id');
            const guestName = this.getAttribute('data-guest');
            const checkIn = this.getAttribute('data-checkin');
            const checkOut = this.getAttribute('data-checkout');
            const roomNumber = this.getAttribute('data-room');
            const roomType = this.getAttribute('data-type');
            const status = this.getAttribute('data-status');
            document.getElementById('editReservationID').value = reservationId;
            document.getElementById('editGuestName').value = guestName;
            document.getElementById('editCheckIn').value = checkIn.split('T')[0];
            document.getElementById('editCheckOut').value = checkOut.split('T')[0];
            document.getElementById('editRoomType').value = roomType;
            document.getElementById('editStatus').value = status;
            // Store the current room number for use after async fetch
            const currentRoom = roomNumber;
            // Fetch and populate room numbers, then set the value
            if (typeof updateEditRoomNumbers === 'function') {
                updateEditRoomNumbers(currentRoom).then(() => {
                    const editRoomNumberSelect = document.getElementById('editRoomNumber');
                    if (editRoomNumberSelect) {
                        editRoomNumberSelect.value = currentRoom;
                    }
                });
            } else {
                const editRoomNumberSelect = document.getElementById('editRoomNumber');
                if (editRoomNumberSelect) {
                    editRoomNumberSelect.value = currentRoom;
                }
            }
        });
    });
    if (closeEditModal && editModal) {
        closeEditModal.addEventListener('click', function() {
            editModal.style.display = 'none';
            editFormError.style.display = 'none';
        });
    }
    // Cancel button in edit modal
    const cancelButtons = document.querySelectorAll('.btn.btn-secondary');
    cancelButtons.forEach(btn => {
        if (btn.textContent.trim().toLowerCase().includes('cancel')) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (editModal) {
                    editModal.style.display = 'none';
                    editFormError.style.display = 'none';
                }
            });
        }
    });
    // Edit form submission
    if (editForm) {
        editForm.onsubmit = function(e) {
            e.preventDefault();
            if (typeof validateEditForm === 'function' && !validateEditForm()) {
                return;
            }
            const formData = new FormData(editForm);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            fetch('reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    if (typeof showEditFormError === 'function') {
                        showEditFormError(data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                if (typeof showEditFormError === 'function') {
                    showEditFormError('An error occurred while updating the reservation');
                } else {
                    alert('An error occurred while updating the reservation');
                }
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
    }
    // View Modal Logic
    const viewModal = document.getElementById('viewModal');
    const closeViewModal = document.getElementById('closeViewModal');
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            viewModal.style.display = 'block';
            const status = this.getAttribute('data-status');
            const statusClass = status.toLowerCase();
            const reservationId = this.getAttribute('data-id');
            const guestName = this.getAttribute('data-guest');
            const checkIn = this.getAttribute('data-checkin');
            const checkOut = this.getAttribute('data-checkout');
            const roomNumber = this.getAttribute('data-room');
            const roomType = this.getAttribute('data-type');
            document.getElementById('viewDetails').innerHTML = `
                <div class="detail-item">
                    <span class="detail-label">Reservation ID:</span>
                    <span class="detail-value">#${reservationId}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Guest Name:</span>
                    <span class="detail-value">${guestName}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Check-in Date:</span>
                    <span class="detail-value">${formatDate(checkIn)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Check-out Date:</span>
                    <span class="detail-value">${formatDate(checkOut)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Room Number:</span>
                    <span class="detail-value">Room ${roomNumber}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Room Type:</span>
                    <span class="detail-value">${roomType}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-${statusClass}">${status}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Duration:</span>
                    <span class="detail-value">${calculateDuration(checkIn, checkOut)}</span>
                </div>
            `;
        });
    });
    if (closeViewModal && viewModal) {
        closeViewModal.addEventListener('click', function() {
            viewModal.style.display = 'none';
        });
    }
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target == editModal) {
            editModal.style.display = 'none';
            editFormError.style.display = 'none';
        }
        if (event.target == viewModal) {
            viewModal.style.display = 'none';
        }
        if (typeof createModal !== 'undefined' && event.target == createModal) {
            createModal.style.display = 'none';
        }
    });
    // Confirm & Book button logic for reservation page
    document.querySelectorAll('.confirm-book-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const reservationId = this.getAttribute('data-id');
            if (!reservationId) return;
            if (!confirm('Are you sure you want to confirm this reservation?')) return;
            fetch('reservation.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new URLSearchParams({ action: 'confirm_reservation', reservationId })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    showNotification(data.message, 'success');
                    refreshReservationsTable();
                }
            })
            .catch(() => alert('Failed to confirm reservation.'));
        });
    });
    // Remove ?success from URL after showing notification
    if (window.location.search.indexOf('success') !== -1) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function calculateDuration(checkIn, checkOut) {
    const start = new Date(checkIn);
    const end = new Date(checkOut);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return `${diffDays} day${diffDays !== 1 ? 's' : ''}`;
}

// Enhanced Edit Form Validation
function validateEditForm() {
    const checkIn = new Date(document.getElementById('editCheckIn').value);
    const checkOut = new Date(document.getElementById('editCheckOut').value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    editFormError.style.display = 'none';
    if (checkIn < today) {
        showEditFormError('Check-in date cannot be in the past');
        return false;
    }
    if (checkOut <= checkIn) {
        showEditFormError('Check-out date must be after check-in date');
        return false;
    }
    return true;
}

function showEditFormError(message) {
    editFormError.textContent = message;
    editFormError.style.display = 'block';
}

// Notification system
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        color: white;
        font-weight: 500;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    if (type === 'success') {
        notification.style.background = '#28a745';
    } else {
        notification.style.background = '#dc3545';
    }
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Filter modal popup logic
// Only declare these variables if not already declared
if (typeof window._filterModalSetup === 'undefined') {
  const filterBtn = document.getElementById('filterBtn');
  const filterModalOverlay = document.getElementById('filterDropdown');
  const closeFilterModal = document.getElementById('closeFilterModal');
  window._filterModalSetup = true;

  if (filterBtn && filterModalOverlay && closeFilterModal) {
    filterBtn.addEventListener('click', () => {
      filterModalOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    });
    closeFilterModal.addEventListener('click', () => {
      filterModalOverlay.classList.remove('active');
      document.body.style.overflow = '';
    });
    filterModalOverlay.addEventListener('click', (e) => {
      if (e.target === filterModalOverlay) {
        filterModalOverlay.classList.remove('active');
        document.body.style.overflow = '';
      }
    });
  }
}

// Create modal popup logic
if (typeof window._createModalSetup === 'undefined') {
  const createBtn = document.getElementById('createBtn');
  const createModalOverlay = document.getElementById('createModal');
  const closeCreateModal = document.getElementById('closeCreateModal');
  window._createModalSetup = true;

  if (createBtn && createModalOverlay && closeCreateModal) {
    createBtn.addEventListener('click', () => {
      createModalOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    });
    closeCreateModal.addEventListener('click', () => {
      createModalOverlay.classList.remove('active');
      document.body.style.overflow = '';
    });
    createModalOverlay.addEventListener('click', (e) => {
      if (e.target === createModalOverlay) {
        createModalOverlay.classList.remove('active');
        document.body.style.overflow = '';
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert-success, .alert-error');
    if (alert) {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 3000);
    }
});

// --- Modal navigation logic for booking/guest modals ---
const nextToGuestBtn = document.getElementById('nextToGuestBtn');
const backToBookingBtn = document.getElementById('backToBookingBtn');
const cancelBookingBtn = document.getElementById('cancelBookingBtn');
const closeBookingModal = document.getElementById('closeBookingModal');
const closeGuestModal = document.getElementById('closeGuestModal');
const bookingModal = document.getElementById('bookingModal');
const guestModal = document.getElementById('guestModal');
const bookingForm = document.getElementById('bookingForm');

// Create an error message element for booking modal if not present
let bookingFormError = document.getElementById('bookingFormError');
if (!bookingFormError && bookingForm) {
    bookingFormError = document.createElement('div');
    bookingFormError.id = 'bookingFormError';
    bookingFormError.style.color = '#dc3545';
    bookingFormError.style.margin = '0.5rem 0 1rem 0';
    bookingFormError.style.display = 'none';
    bookingForm.insertBefore(bookingFormError, bookingForm.querySelector('.modal-footer'));
}

if (nextToGuestBtn) {
    nextToGuestBtn.onclick = function() {
        console.log('Next button clicked');
        // Validate required fields in booking form
        const requiredFields = bookingForm.querySelectorAll('[required]');
        let valid = true;
        bookingFormError.style.display = 'none';
        bookingFormError.textContent = '';
        requiredFields.forEach(field => {
            if (!field.value || (field.tagName === 'SELECT' && field.value === '')) {
                valid = false;
                field.style.borderColor = '#dc3545';
            } else {
                field.style.borderColor = '';
            }
        });
        if (!valid) {
            bookingFormError.textContent = 'Please fill out all required fields before proceeding.';
            bookingFormError.style.display = 'block';
            return;
        }
        bookingModal.style.display = 'none';
        guestModal.style.display = 'block';
    };
}
if (backToBookingBtn) {
    backToBookingBtn.onclick = function() {
        console.log('Back button clicked');
        guestModal.style.display = 'none';
        bookingModal.style.display = 'block';
    };
}
if (cancelBookingBtn) {
    cancelBookingBtn.onclick = function() {
        console.log('Cancel button clicked');
        bookingModal.style.display = 'none';
    };
}
if (closeBookingModal) {
    closeBookingModal.onclick = function() {
        console.log('Close booking modal button clicked');
        bookingModal.style.display = 'none';
    };
}
if (closeGuestModal) {
    closeGuestModal.onclick = function() {
        console.log('Close guest modal button clicked');
        guestModal.style.display = 'none';
    };
}

// --- Dynamic Room Number for Booking Modal ---
const checkInInput = document.getElementById('checkInDate');
const checkOutInput = document.getElementById('checkOutDate');
const roomTypeSelect = document.getElementById('roomType');
const roomNumberSelect = document.getElementById('roomNumber');

if(roomTypeSelect && roomNumberSelect && checkInInput && checkOutInput) {
    async function fetchAvailableRoomsForBooking() {
        const roomType = roomTypeSelect.value;
        const checkIn = checkInInput.value;
        const checkOut = checkOutInput.value;
        roomNumberSelect.innerHTML = '<option value="">Loading...</option>';
        if (!roomType || !checkIn || !checkOut) {
            roomNumberSelect.innerHTML = '<option value="">Select Room Number</option>';
            return;
        }
        try {
            const response = await fetch(`reservation.php?getRooms=1&checkIn=${encodeURIComponent(checkIn)}&checkOut=${encodeURIComponent(checkOut)}&roomType=${encodeURIComponent(roomType)}`);
            if (!response.ok) throw new Error('Failed to fetch rooms');
            const rooms = await response.json();
            let options = '<option value="">Select Room Number</option>';
            if (rooms.noRooms) {
                options = '<option value="" disabled selected>No rooms available for the selected dates.</option>';
                roomNumberSelect.disabled = true;
            } else {
                roomNumberSelect.disabled = false;
                rooms.forEach(room => {
                    const label = `Room ${room.RoomNumber} (${room.Label})`;
                    if (room.Available) {
                        options += `<option value="${room.RoomNumber}">${label}</option>`;
                    } else {
                        options += `<option value="${room.RoomNumber}" disabled>${label}</option>`;
                    }
                });
            }
            roomNumberSelect.innerHTML = options;
        } catch (err) {
            roomNumberSelect.innerHTML = '<option value="">Error loading rooms</option>';
        }
    }
    roomTypeSelect.addEventListener('change', fetchAvailableRoomsForBooking);
    checkInInput.addEventListener('change', fetchAvailableRoomsForBooking);
    checkOutInput.addEventListener('change', fetchAvailableRoomsForBooking);
}

// Add this function to refresh the calendar and auto-show the new booking's modal
function refreshCalendarAndShowBooking(newBookingId) {
    // Use currentYear/currentMonth from your context, or get from DOM if needed
    let currentDate = new Date();
    let currentYear = currentDate.getFullYear();
    let currentMonth = currentDate.getMonth(); // 0-based, add 1 for PHP
    fetch('update_calendar.php?year=' + currentYear + '&month=' + (currentMonth + 1))
        .then(response => response.json())
        .then(data => {
            document.querySelector('.calendar-grid').innerHTML = data.calendarHtml;
            document.querySelector('.current-month').textContent = data.monthDisplay;
            attachBookingBarListeners();
            setTimeout(() => {
                const newBar = document.querySelector(`.booking-bar[data-type="booking"][data-id="${newBookingId}"]`);
                if (newBar) newBar.click();
            }, 100);
        });
}

// Add this function to re-attach booking bar listeners after calendar refresh
function attachBookingBarListeners() {
    document.querySelectorAll('.booking-bar').forEach(function(bar) {
        bar.addEventListener('click', function() {
            // You may want to call your modal-filling logic here
            // For now, just trigger the modal if it exists
            const type = bar.getAttribute('data-type');
            let booking;
            if (type === 'reservation') {
                const reservationId = bar.getAttribute('data-id');
                booking = (window.allBookingsData || []).find(b => String(b.ReservationID) === String(reservationId));
                if (!booking) return;
                const detailsModal = document.getElementById('detailsModal');
                if (detailsModal) detailsModal.style.display = 'flex';
                // Fill modal fields for reservation (customize as needed)
                document.getElementById('detailsBookingId').value = '';
                document.getElementById('detailsRoomNumber').value = booking.RoomNumber || '';
                document.getElementById('detailsBookingStatus').value = booking.BookingStatus || booking.Status || '';
                document.getElementById('detailsBookingIdDisplay').value = '';
                document.getElementById('detailsReservationIdDisplay').value = booking.ReservationID || '';
                document.getElementById('detailsStudentIdDisplay').value = booking.StudentID || booking.StudentIDNum || '';
                document.getElementById('detailsCheckIn').value = booking.CheckInDate || booking.PCheckInDate || '';
                document.getElementById('detailsCheckOut').value = booking.CheckOutDate || booking.PCheckOutDate || '';
                document.getElementById('detailsFirstName').value = booking.FirstName || booking.GuestName || '';
                document.getElementById('detailsLastName').value = booking.LastName || '';
                document.getElementById('detailsEmail').value = booking.Email || '';
                document.getElementById('detailsPhone').value = booking.PhoneNumber || '';
                document.getElementById('detailsNotes').value = booking.Notes || '';
            } else {
                const bookingId = bar.getAttribute('data-id');
                booking = (window.allBookingsData || []).find(b => String(b.BookingID) === String(bookingId));
                if (!booking) return;
                const detailsModal = document.getElementById('detailsModal');
                if (detailsModal) detailsModal.style.display = 'flex';
                // Fill modal fields for booking (customize as needed)
                document.getElementById('detailsBookingId').value = booking.BookingID || '';
                document.getElementById('detailsRoomNumber').value = booking.RoomNumber || '';
                document.getElementById('detailsBookingStatus').value = booking.BookingStatus || '';
                document.getElementById('detailsBookingIdDisplay').value = booking.BookingCode || '';
                document.getElementById('detailsReservationIdDisplay').value = booking.ReservationID || '';
                document.getElementById('detailsStudentIdDisplay').value = booking.StudentID || '';
                document.getElementById('detailsCheckIn').value = booking.CheckInDate || '';
                document.getElementById('detailsCheckOut').value = booking.CheckOutDate || '';
                document.getElementById('detailsFirstName').value = booking.FirstName || '';
                document.getElementById('detailsLastName').value = booking.LastName || '';
                document.getElementById('detailsEmail').value = booking.Email || '';
                document.getElementById('detailsPhone').value = booking.PhoneNumber || '';
                document.getElementById('detailsNotes').value = booking.Notes || '';
            }
        });
    });
}

function refreshReservationsTable() {
  fetch('reservation.php?action=get_reservations_table')
    .then(res => res.json())
    .then(data => {
      const tbody = document.querySelector('#reservationsTable tbody');
      if (tbody) {
        tbody.innerHTML = data.tableHtml;
        // Re-attach event listeners to new confirm-book-btn buttons
        tbody.querySelectorAll('.confirm-book-btn').forEach(btn => {
          btn.addEventListener('click', function(e) {
            e.preventDefault();
            const reservationId = this.getAttribute('data-id');
            if (!reservationId) return;
            if (!confirm('Are you sure you want to confirm this reservation?')) return;
            fetch('reservation.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new URLSearchParams({ action: 'confirm_reservation', reservationId })
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    showNotification(data.message, 'success');
                    refreshReservationsTable();
                }
            })
            .catch(() => alert('Failed to confirm reservation.'));
          });
        });
      }
    });
}

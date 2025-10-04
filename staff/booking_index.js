console.log("booking_index.js loaded!");

document.addEventListener('DOMContentLoaded', function() {
    // ============================================================================
    // MODAL ELEMENTS
    // ============================================================================
    const bookingModal = document.getElementById('bookingModal');
    const filterModal = document.getElementById('filterModal');
    const detailsModal = document.getElementById('detailsModal');
    const guestModal = document.getElementById('guestModal');
    
    // ============================================================================
    // BUTTON ELEMENTS
    // ============================================================================
    const walkInBtn = document.getElementById('walkInBtn');
    const filterBtn = document.getElementById('filterBtn');
    const closeBtns = document.querySelectorAll('.modal .close-btn');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const detailsCancelBtn = document.getElementById('detailsCancelBtn');
    const earlyCheckoutBtn = document.getElementById('earlyCheckoutBtn');
    const nextToGuestBtn = document.getElementById('nextToGuestBtn');
    const backToBookingBtn = document.getElementById('backToBookingBtn');
    const cancelBookingBtn = document.getElementById('cancelBookingBtn');
    const closeBookingModal = document.getElementById('closeBookingModal');
    const closeGuestModal = document.getElementById('closeGuestModal');
    
    // ============================================================================
    // FORM & FORM ELEMENTS
    // ============================================================================
    const bookingForm = document.getElementById('bookingForm');
    const detailsForm = document.getElementById('detailsForm');
    const filterForm = document.getElementById('filterForm');
    const formStep1 = document.getElementById('form-step-1');
    const formStep2 = document.getElementById('form-step-2');
    const searchInputModal = document.getElementById('searchInputModal');
    const filterStatus = document.getElementById('filterStatus');
    const clearFilterBtn = document.getElementById('clearFilterBtn');
    const roomTypeSelect = document.getElementById('roomType');
    const roomNumberSelect = document.getElementById('roomNumber');
    const checkInInput = document.getElementById('checkInDate');
    const checkOutInput = document.getElementById('checkOutDate');
    const detailsBookingId = document.getElementById('detailsBookingId');
    const detailsRoomNumber = document.getElementById('detailsRoomNumber');

    // ============================================================================
    // MODAL VISIBILITY LOGIC
    // ============================================================================
    if (walkInBtn) {
        walkInBtn.onclick = () => {
            console.log('Walk-in booking button clicked');
            if (bookingModal) bookingModal.style.display = 'flex';
            if (formStep1) formStep1.classList.add('active');
            if (formStep2) formStep2.classList.remove('active');
            // Reset room type and room number dropdowns
            if (roomTypeSelect) roomTypeSelect.selectedIndex = 0;
            if (roomNumberSelect) roomNumberSelect.innerHTML = '<option value="">Select Room Number</option>';
        }
    }

    if(filterBtn) {
        filterBtn.onclick = () => {
            console.log('Filter button clicked');
            if (filterModal) filterModal.style.display = 'flex';
        }
    }

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            if (bookingModal) bookingModal.style.display = 'none';
            if (filterModal) filterModal.style.display = 'none';
            if (detailsModal) detailsModal.style.display = 'none';
        }
    });

    if(cancelBtn) {
        cancelBtn.onclick = () => {
            if (bookingModal) bookingModal.style.display = 'none';
        }
    }
    
    if(detailsCancelBtn) {
        detailsCancelBtn.addEventListener('click', () => {
            if(detailsModal) detailsModal.style.display = 'none';
        });
    }

    // ============================================================================
    // WALK-IN BOOKING MODAL NAVIGATION
    // ============================================================================
    if (nextToGuestBtn) {
        nextToGuestBtn.onclick = function() {
            // Validate required fields in booking form
            const requiredFields = bookingForm.querySelectorAll('[required]');
            let valid = true;
            requiredFields.forEach(field => {
                if (!field.value || (field.tagName === 'SELECT' && field.value === '')) {
                    valid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '';
                }
            });
            if (!valid) {
                alert('Please fill out all required fields before proceeding.');
                return;
            }
            bookingModal.style.display = 'none';
            guestModal.style.display = 'block';
        };
    }

    if (backToBookingBtn) {
        backToBookingBtn.onclick = function() {
            guestModal.style.display = 'none';
            bookingModal.style.display = 'block';
        };
    }

    if (cancelBookingBtn) {
        cancelBookingBtn.onclick = function() {
            bookingModal.style.display = 'none';
        };
    }

    if (closeBookingModal) {
        closeBookingModal.onclick = function() {
            bookingModal.style.display = 'none';
        };
    }

    if (closeGuestModal) {
        closeGuestModal.onclick = function() {
            guestModal.style.display = 'none';
        };
    }

    // Close modals when clicking outside
    window.onclick = (event) => {
        if (event.target == bookingModal) bookingModal.style.display = 'none';
        if (event.target == filterModal) filterModal.style.display = 'none';
        if (event.target == detailsModal) detailsModal.style.display = 'none';
        if (event.target == guestModal) guestModal.style.display = 'none';
    }

    // ============================================================================
    // FORM SUBMISSION LOGIC
    // ============================================================================
    if(bookingForm) {
        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(bookingForm);
            formData.append('action', 'create_walkin');

            const response = await fetch('booking.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);

            if (result.success) {
                if (bookingModal) bookingModal.style.display = 'none';
                location.reload(); 
            }
        });
    }
    
    if(detailsForm) {
        detailsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Client-side validation for required fields
            const requiredFields = [
                'detailsRoomNumber',
                'detailsBookingStatus',
                'detailsCheckIn',
                'detailsCheckOut',
                'detailsFirstName',
                'detailsLastName'
            ];
            let valid = true;
            requiredFields.forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) {
                    console.log('Empty required field:', id, el);
                    el.classList.add('input-error');
                    el.style.border = '2px solid #e74c3c';
                    valid = false;
                } else if (el) {
                    el.classList.remove('input-error');
                    el.style.border = '';
                }
            });
            if (!valid) {
                alert('Please fill out all required fields.');
                return;
            }
            
            // Check if required hidden fields are set
            const bookingId = document.getElementById('detailsBookingId').value;
            const studentId = document.getElementById('detailsStudentIdDisplay').value;
            
            if (!bookingId && !studentId) {
                alert('Error: Missing booking or student information. Please try refreshing the page.');
                return;
            }
            
            const formData = new FormData(detailsForm);
            formData.append('action', 'update_booking');
            
            try {
                const response = await fetch('booking.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    // Set the Booking ID and BookingCode in the modal if available
                    if (result.bookingId) {
                        if (document.getElementById('detailsBookingId')) document.getElementById('detailsBookingId').value = result.bookingId;
                    }
                    if (result.bookingCode) {
                        if (document.getElementById('detailsBookingIdDisplay')) document.getElementById('detailsBookingIdDisplay').value = result.bookingCode;
                    }
                    alert(result.message);
                    // Close the modal and reload to show updated data
                    if(detailsModal) detailsModal.style.display = 'none';
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to update booking. Please try again.'));
                }
            } catch (error) {
                console.error('AJAX Error:', error);
                alert('Network error. Please check your connection and try again.');
            }
        });
    }

    // ============================================================================
    // SEARCH AND FILTER LOGIC
    // ============================================================================
    const allBookings = window.allBookingsData || [];
    const uniqueBookings = Object.values(allBookings.reduce((acc, booking) => {
        acc[booking.BookingID] = booking;
        return acc;
    }, {}));

    function applySearchAndFilter(updateListOnly = false) {
        const searchTerm = searchInputModal ? searchInputModal.value.trim().toLowerCase() : '';
        const statusFilter = filterStatus ? filterStatus.value.toLowerCase() : '';
        const resultsContainer = document.getElementById('filter-results');
        let resultsHTML = '';
        let matchingBookingIds = new Set();

        uniqueBookings.forEach(booking => {
            const roomNumber = booking.RoomNumber ? String(booking.RoomNumber).toLowerCase() : '';
            const guestName = (booking.FirstName + ' ' + (booking.LastName || '')).trim().toLowerCase();
            let barStatus = booking.RoomStatus ? booking.RoomStatus.toLowerCase() : '';

            if (!['booked', 'reserved', 'maintenance'].includes(barStatus)) {
                barStatus = 'booked';
            }

            const matchesSearch = searchTerm === '' || roomNumber.includes(searchTerm) || guestName.includes(searchTerm) || barStatus.includes(searchTerm);
            const matchesFilter = statusFilter === '' || barStatus === statusFilter;

            if (matchesSearch && matchesFilter) {
                matchingBookingIds.add(booking.BookingID);
                resultsHTML += `<div class="result-item"><b>Room ${booking.RoomNumber}</b> - ${booking.FirstName || ''} ${(booking.LastName || 'N/A')} (${barStatus})</div>`;
            }
        });
        
        if (resultsContainer) {
            if (matchingBookingIds.size > 0) {
                resultsContainer.innerHTML = resultsHTML;
            } else {
                resultsContainer.innerHTML = `<div class="result-item empty">No matching bookings found.</div>`;
            }
        }
        
        if (!updateListOnly) {
             document.querySelectorAll('.booking-bar').forEach(bar => {
                const bookingId = bar.dataset.bookingId;
                if (matchingBookingIds.has(bookingId)) {
                    bar.classList.remove('filtered');
                } else {
                    bar.classList.add('filtered');
                }
            });
        }
    }

    if(searchInputModal) {
        searchInputModal.addEventListener('keyup', () => applySearchAndFilter(true));
    }
    if(filterStatus) {
        filterStatus.addEventListener('change', () => applySearchAndFilter(true));
    }

    if(filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            applySearchAndFilter(false); // Apply to calendar
            if(filterModal) filterModal.style.display = 'none';
        });
    }

    if(clearFilterBtn) {
        clearFilterBtn.addEventListener('click', () => {
            if(filterStatus) filterStatus.value = '';
            if(searchInputModal) searchInputModal.value = '';
            applySearchAndFilter(true);
        });
    }

    // ============================================================================
    // ROOM TYPE TO ROOM NUMBER DROPDOWN
    // ============================================================================
    if(roomTypeSelect && roomNumberSelect && checkInInput && checkOutInput) {
        async function fetchAvailableRooms() {
            const roomType = roomTypeSelect.value;
            const checkIn = checkInInput.value;
            const checkOut = checkOutInput.value;
            if (!roomType) {
                roomNumberSelect.innerHTML = '<option value="">Select Room Number</option>';
                return;
            }
            roomNumberSelect.innerHTML = '<option value="">Loading...</option>';
            let url = `booking.php?roomType=${encodeURIComponent(roomType)}`;
            if (checkIn && checkOut) {
                url += `&checkIn=${encodeURIComponent(checkIn)}&checkOut=${encodeURIComponent(checkOut)}`;
            }
            try {
                const response = await fetch(url);
                const rooms = await response.json();
                let options = '<option value="">Select Room Number</option>';
                if (rooms.noRooms) {
                    options = '<option value="" disabled selected>No rooms available for the selected dates.</option>';
                    roomNumberSelect.disabled = true;
                } else {
                    roomNumberSelect.disabled = false;
                    rooms.forEach(room => {
                        options += `<option value="${room.RoomNumber}">${room.RoomNumber}</option>`;
                    });
                }
                roomNumberSelect.innerHTML = options;
            } catch (err) {
                roomNumberSelect.innerHTML = '<option value="">Error loading rooms</option>';
            }
        }

        roomTypeSelect.addEventListener('change', fetchAvailableRooms);
        checkInInput.addEventListener('change', fetchAvailableRooms);
        checkOutInput.addEventListener('change', fetchAvailableRooms);
    }

    // ============================================================================
    // GUEST FORM SUBMISSION HANDLER
    // ============================================================================
    const guestForm = document.getElementById('guestForm');
    if (guestForm) {
        guestForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get booking form data
            const bookingFormData = new FormData(bookingForm);
            const guestFormData = new FormData(guestForm);
            
            // Combine both forms' data
            const combinedData = new FormData();
            
            // Add booking form data
            for (let [key, value] of bookingFormData.entries()) {
                combinedData.append(key, value);
            }
            
            // Add guest form data
            for (let [key, value] of guestFormData.entries()) {
                combinedData.append(key, value);
            }
            
            // Add action for walk-in booking
            combinedData.append('action', 'create_walkin');
            
            try {
                const response = await fetch('booking.php', {
                    method: 'POST',
                    body: combinedData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Walk-in booking created successfully!');
                    guestModal.style.display = 'none';
                    bookingModal.style.display = 'none';
                    // Reset forms
                    bookingForm.reset();
                    guestForm.reset();
                    // Reload page to show updated calendar
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to create booking'));
                }
            } catch (error) {
                console.error('Error submitting booking:', error);
                alert('Network error. Please check your connection and try again.');
            }
        });
    }

    // ============================================================================
    // CALENDAR MONTH/YEAR PICKER
    // ============================================================================
    const monthSelect = document.getElementById('monthSelect');
    const yearInput = document.getElementById('yearInput');
    const goToDateBtn = document.getElementById('goToDateBtn');
    if(monthSelect && yearInput && goToDateBtn) {
        goToDateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const month = monthSelect.value;
            const year = yearInput.value;
            window.location.href = `?month=${month}&year=${year}`;
        });
    }

    // ==========================================================================
    // CALENDAR BAR CLICK TO EDIT BOOKING
    // ==========================================================================
    document.querySelectorAll('.booking-bar').forEach(function(bar) {
        bar.addEventListener('click', function() {
            const type = bar.getAttribute('data-type');
            let booking;
            if (type === 'reservation') {
                const reservationId = bar.getAttribute('data-id');
                booking = (window.allBookingsData || []).find(b => String(b.ReservationID) === String(reservationId));
                if (!booking) return;
                // Show modal
                const detailsModal = document.getElementById('detailsModal');
                if (detailsModal) detailsModal.style.display = 'flex';
                // Fill modal fields for reservation
                document.getElementById('detailsBookingId').value = '';
                document.getElementById('detailsRoomNumber').value = booking.RoomNumber || '';
                document.getElementById('detailsBookingStatus').value = booking.BookingStatus || booking.Status || '';
                document.getElementById('detailsBookingIdDisplay').value = '';
                document.getElementById('detailsReservationIdDisplay').value = booking.ReservationID || '';
                console.log('Booking object for modal:', booking);
                document.getElementById('detailsStudentIdDisplay').value = booking.StudentID || booking.StudentIDNum || '';
                document.getElementById('detailsStudentIdHidden').value = booking.StudentID || booking.StudentIDNum || '';
                document.getElementById('detailsCheckIn').value = booking.CheckInDate || booking.PCheckInDate || '';
                document.getElementById('detailsCheckOut').value = booking.CheckOutDate || booking.PCheckOutDate || '';
                document.getElementById('detailsFirstName').value = booking.FirstName || booking.GuestName || '';
                document.getElementById('detailsLastName').value = booking.LastName || '';
                document.getElementById('detailsEmail').value = booking.Email || '';
                document.getElementById('detailsPhone').value = booking.PhoneNumber || '';
                document.getElementById('detailsNotes').value = booking.Notes || '';
                
                // Check if early checkout button should be shown (reservations can't be checked out early)
                if (earlyCheckoutBtn) {
                    earlyCheckoutBtn.style.display = 'none';
                }
            } else {
                const bookingId = bar.getAttribute('data-id');
                booking = (window.allBookingsData || []).find(b => String(b.BookingID) === String(bookingId));
                if (!booking) return;
                // Show modal
                const detailsModal = document.getElementById('detailsModal');
                if (detailsModal) detailsModal.style.display = 'flex';
                // Fill modal fields for booking
                document.getElementById('detailsBookingId').value = booking.BookingID || '';
                document.getElementById('detailsRoomNumber').value = booking.RoomNumber || '';
                document.getElementById('detailsBookingStatus').value = booking.BookingStatus || '';
                document.getElementById('detailsBookingIdDisplay').value = booking.BookingCode || '';
                document.getElementById('detailsReservationIdDisplay').value = booking.ReservationID || '';
                console.log('Booking object for modal:', booking);
                document.getElementById('detailsStudentIdDisplay').value = booking.StudentID || booking.StudentIDNum || '';
                document.getElementById('detailsStudentIdHidden').value = booking.StudentID || booking.StudentIDNum || '';
                console.log('Set StudentID field value:', document.getElementById('detailsStudentIdDisplay').value);
                document.getElementById('detailsCheckIn').value = booking.CheckInDate || '';
                document.getElementById('detailsCheckOut').value = booking.CheckOutDate || '';
                document.getElementById('detailsFirstName').value = booking.FirstName || '';
                document.getElementById('detailsLastName').value = booking.LastName || '';
                document.getElementById('detailsEmail').value = booking.Email || '';
                document.getElementById('detailsPhone').value = booking.PhoneNumber || '';
                document.getElementById('detailsNotes').value = booking.Notes || '';
                
                // Check if early checkout button should be shown
                if (earlyCheckoutBtn) {
                    const bookingStatus = booking.BookingStatus || '';
                    const checkOutDateRaw = booking.CheckOutDate || '';
                    const today = new Date();
                    let checkOutDateObj;
                    // Support both YYYY-MM-DD and DD/MM/YYYY
                    if (/^\d{4}-\d{2}-\d{2}$/.test(checkOutDateRaw)) {
                        checkOutDateObj = new Date(checkOutDateRaw);
                    } else if (/^\d{2}\/\d{2}\/\d{4}$/.test(checkOutDateRaw)) {
                        const [d, m, y] = checkOutDateRaw.split('/');
                        checkOutDateObj = new Date(`${y}-${m}-${d}`);
                    } else {
                        checkOutDateObj = new Date(checkOutDateRaw);
                    }
                    // Show button only for confirmed/booked bookings with future check-out date
                    if ((bookingStatus === 'Confirmed' || bookingStatus === 'Booked') && checkOutDateObj > today) {
                        earlyCheckoutBtn.style.display = 'inline-block';
                    } else {
                        earlyCheckoutBtn.style.display = 'none';
                    }
                }
            }
        });
    });

    // EARLY CHECKOUT BUTTON LOGIC
    if (earlyCheckoutBtn) {
        earlyCheckoutBtn.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to check out this guest early? This will mark the booking as completed and make the room available.')) return;
            
            const bookingId = document.getElementById('detailsBookingId').value;
            const roomNumber = document.getElementById('detailsRoomNumber').value;
            
            if (!bookingId || !roomNumber) {
                alert('Error: Missing booking information. Please try again.');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'early_checkout');
            formData.append('bookingId', bookingId);
            formData.append('roomNumber', roomNumber);
            
            try {
                const response = await fetch('booking.php', { method: 'POST', body: formData });
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    // Close modal and reload to show updated status
                    const detailsModal = document.getElementById('detailsModal');
                    if (detailsModal) detailsModal.style.display = 'none';
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Failed to process early checkout.'));
                }
            } catch (error) {
                console.error('Early checkout error:', error);
                alert('Network error. Please check your connection and try again.');
            }
        });
    }

    // Confirm & Book button logic
    document.querySelectorAll('.confirm-book-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Open the booking modal (assume #bookingModal exists)
            const bookingModal = document.getElementById('bookingModal');
            if (bookingModal) bookingModal.style.display = 'flex';
            // Fill in the booking form fields
            document.getElementById('roomNumber').value = this.getAttribute('data-room') || '';
            document.getElementById('roomType').value = this.getAttribute('data-type') || '';
            document.getElementById('checkInDate').value = this.getAttribute('data-checkin') || '';
            document.getElementById('checkOutDate').value = this.getAttribute('data-checkout') || '';
            document.querySelector('input[name="firstName"]').value = this.getAttribute('data-guest') || '';
            document.querySelector('input[name="studentId"]').value = this.getAttribute('data-studentid') || '';
            // Optionally clear or set other fields as needed
        });
    });

    const openAllBookingsModal = document.getElementById('openAllBookingsModal');
    const allBookingsModal = document.getElementById('allBookingsModal');
    if (openAllBookingsModal) {
        openAllBookingsModal.onclick = () => {
            console.log('View All Bookings button clicked');
            if (allBookingsModal) allBookingsModal.style.display = 'flex';
        }
    }

    function fetchAndRenderBookings() {
        const search = document.getElementById('bookingSearchInput').value;
        const roomType = document.getElementById('bookingRoomTypeFilter').value;
        const roomNumber = document.getElementById('bookingRoomNumberFilter').value;
        fetch(`booking.php?action=get_all_bookings&search=${encodeURIComponent(search)}&roomType=${encodeURIComponent(roomType)}&roomNumber=${encodeURIComponent(roomNumber)}`)
            .then(res => res.json())
            .then(data => {
                const tbody = document.querySelector('#allBookingsTable tbody');
                tbody.innerHTML = data.bookings.map(b => {
                    let badge = '';
                    if (b.ReservationID && b.ReservationID !== '') {
                        badge = '<span class="badge badge-reservation">Reservation</span>';
                    } else if (b.BookingCode && b.BookingCode !== '') {
                        badge = '<span class="badge badge-walkin">Walk-in</span>';
                    } else {
                        badge = '';
                    }
                    return `
                        <tr>
                            <td>${b.BookingCode || b.BookingID}</td>
                            <td>${b.GuestName}</td>
                            <td>${b.RoomNumber}</td>
                            <td>${b.RoomType}</td>
                            <td>${b.BookingStatus}</td>
                            <td>${b.CheckInDate}</td>
                            <td>${b.CheckOutDate}</td>
                            <td>${b.BookingDate}</td>
                            <td>${badge}</td>
                        </tr>
                    `;
                }).join('');
            });
    }
    document.getElementById('bookingSearchInput').oninput = fetchAndRenderBookings;
    document.getElementById('bookingRoomTypeFilter').onchange = fetchAndRenderBookings;
    document.getElementById('bookingRoomNumberFilter').onchange = fetchAndRenderBookings;
}); 

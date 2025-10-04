<?php
session_start();

// --- Room Data (simulate from DB) ---
$rooms = [
  [
    'id' => 1,
    'type' => 'standard',
    'name' => 'Serenity Standard Room',
    'desc' => 'A calm and inviting space designed for relaxation. The Serenity Standard Room blends modern comfort with a soothing ambiance, perfect for solo travelers or small families.',
    'beds' => '1 Queen bed',
    'capacity' => 2,
    'size' => '28 sq m',
    'img' => 'images/serenity_standard room.jpg',
  ],
  [
    'id' => 2,
    'type' => 'standard',
    'name' => 'Haven Standard Room',
    'desc' => 'Your personal haven awaits. With a plush king bed and refined interiors, this room offers peace and comfort in a stylish setting.',
    'beds' => '1 Queen bed',
    'capacity' => 2,
    'size' => '28 sq m',
    'img' => 'images/haven_standard room.jpg',
  ],
  [
    'id' => 3,
    'type' => 'standard',
    'name' => 'Enchanted Chamber Standard Room',
    'desc' => 'Step into an atmosphere of quiet enchantment. Cozy and elegant, this room is perfect for a magical staycation or romantic escape.',
    'beds' => '1 Queen bed',
    'capacity' => 2,
    'size' => '28 sq m',
    'img' => 'images/chamber_standard room.png',
  ],
  [
    'id' => 4,
    'type' => 'deluxe',
    'name' => 'Family Retreat Deluxe Room',
    'desc' => 'Spacious and welcoming, this deluxe room is tailor-made for families. Enjoy quality bonding time with plenty of room for everyone to relax and recharge.',
    'beds' => '1 King bed, 1 Sofa bed',
    'capacity' => 4,
    'size' => '38 sq m',
    'img' => 'images/family retreat_deluxe room.jpg',
  ],
  [
    'id' => 5,
    'type' => 'deluxe',
    'name' => 'Premier Loft Deluxe Room',
    'desc' => "Elevate your experience in this deluxe loft. Designed with style and comfort, it's perfect for guests seeking something more spacious and unique.",
    'beds' => '1 King bed, 1 Sofa bed',
    'capacity' => 4,
    'size' => '38 sq m',
    'img' => 'images/premier loft_deluxe room.jpg',
  ],
  [
    'id' => 6,
    'type' => 'deluxe',
    'name' => 'Luxe Escape Room',
    'desc' => 'Indulge in modern luxury. The Luxe Escape Room offers a sophisticated setting with every amenity to help you unwind and feel pampered.',
    'beds' => '1 King bed, 1 Sofa bed',
    'capacity' => 4,
    'size' => '38 sq m',
    'img' => 'images/luxe escape_deluxe room.png',
  ],
  [
    'id' => 7,
    'type' => 'suite',
    'name' => 'Executive Suite Room',
    'desc' => "A sophisticated suite for business or pleasure. With enhanced space and comfort, it's perfect for productive stays or refined getaways.",
    'beds' => '1 King bed, 1 Sofa Bed, 1 Lounge Bed',
    'capacity' => 6,
    'size' => '60 sq m',
    'img' => 'images/executive suite_suite room.jpg',
  ],
  [
    'id' => 8,
    'type' => 'suite',
    'name' => 'Grand Villa Suite Room',
    'desc' => 'Experience grandeur in our Grand Villa Suite—spacious, private, and stylish. A perfect sanctuary for long stays or luxurious escapes.',
    'beds' => '1 King bed, 1 Sofa Bed, 1 Lounge Bed',
    'capacity' => 6,
    'size' => '60 sq m',
    'img' => 'images/grand villa_suite room.jpg',
  ],
  [
    'id' => 9,
    'type' => 'suite',
    'name' => 'Royal Haven Suite Room',
    'desc' => 'Feel like royalty in this elegant suite, combining luxurious design and ultimate comfort. Perfect for guests seeking a regal stay.',
    'beds' => '1 King bed, 1 Sofa Bed, 1 Lounge Bed',
    'capacity' => 6,
    'size' => '60 sq m',
    'img' => 'images/royal haven_suite room.png',
  ],
];

// --- Pricing Data (for room price display) ---
$room_pricing = [
  'standard' => ['hourly' => 200, '6' => ['price' => 1100, 'rf' => 330], '12' => ['price' => 2200, 'rf' => 660], '24' => ['price' => 4500, 'rf' => 1350]],
  'deluxe' => ['hourly' => 300, '6' => ['price' => 1600, 'rf' => 480], '12' => ['price' => 3500, 'rf' => 1050], '24' => ['price' => 7150, 'rf' => 2145]],
  'suite' => ['hourly' => 500, '6' => ['price' => 2929, 'rf' => 878], '12' => ['price' => 5989, 'rf' => 1796], '24' => ['price' => 11899, 'rf' => 3569]],
];

// --- Filter Logic ---
$selected = [
  'adults' => isset($_GET['adults']) ? (int)$_GET['adults'] : 1,
  'children' => isset($_GET['children']) ? (int)$_GET['children'] : 0,
  'checkin' => $_GET['checkin'] ?? '',
  'checkout' => $_GET['checkout'] ?? '',
  'checkin_time' => $_GET['checkin_time'] ?? '14:00',
  'checkout_time' => $_GET['checkout_time'] ?? '12:00',
];

$total_guests = $selected['adults'] + $selected['children'];

// Determine room type based on guest count
function get_room_type_by_guests($guests) {
  if ($guests <= 2) return 'standard';
  if ($guests <= 4) return 'deluxe';
  if ($guests <= 6) return 'suite';
  return '';
}
$selected['room_type'] = get_room_type_by_guests($total_guests);

// Filter rooms by type and capacity
function filter_rooms($rooms, $total_guests, $room_type) {
  $filtered = [];
  foreach ($rooms as $room) {
    if ($room['type'] !== $room_type) continue;
    if ($room['capacity'] < $total_guests) continue;
    $filtered[] = $room;
  }
  return $filtered;
}

$show_results = !empty($selected['checkin']) && !empty($selected['checkout']);
$available_rooms = $show_results && $selected['room_type'] ? filter_rooms($rooms, $total_guests, $selected['room_type']) : [];

// --- Calculate Duration and Price ---
function get_hour_diff($checkin, $checkout, $checkin_time, $checkout_time) {
  if (!$checkin || !$checkout) return 0;
  $start = strtotime($checkin . ' ' . $checkin_time);
  $end = strtotime($checkout . ' ' . $checkout_time);
  $diff = ($end - $start) / 3600;
  return $diff > 0 ? $diff : 0;
}

$hours = get_hour_diff($selected['checkin'], $selected['checkout'], $selected['checkin_time'], $selected['checkout_time']);
$price = 0;
$rf = 0;
$hoursLabel = '';
if ($selected['room_type'] && $hours > 0) {
  $h = round($hours);
  $pricing = $room_pricing[$selected['room_type']];
  if ($h >= 24) {
    $price = $pricing['24']['price'];
    $rf = $pricing['24']['rf'];
    $hoursLabel = '24 Hours';
  } elseif ($h >= 12) {
    $price = $pricing['12']['price'];
    $rf = $pricing['12']['rf'];
    $hoursLabel = '12 Hours';
  } elseif ($h >= 6) {
    $price = $pricing['6']['price'];
    $rf = $pricing['6']['rf'];
    $hoursLabel = '6 Hours';
  } else {
    $price = $h * $pricing['hourly'];
    $rf = round($price * 0.3);
    $hoursLabel = $h . ' Hour(s)';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Villa Valore Hotel</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
  .booking-panel {
    display: flex;
    gap: 24px;
    justify-content: space-between;
    align-items: stretch;
    flex-wrap: wrap;
  }
  .booking-box, .cart-box {
    flex: 1 1 0;
    min-width: 180px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    padding: 18px 16px 16px 16px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    height: 100%;
    box-sizing: border-box;
  }
  .booking-box label, .cart-box p {
    margin-bottom: 8px;
    font-weight: 500;
  }
  .booking-box select, .booking-box input[type="date"], .booking-box input[type="time"] {
    margin-bottom: 8px;
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 1em;
  }
  .cart-box {
    justify-content: center;
    align-items: flex-start;
  }
  @media (max-width: 900px) {
    .booking-panel {
    flex-direction: column;   
    gap: 16px;
    }
    .booking-box, .cart-box {
    min-width: 0;
    width: 100%;
    }
  }
  .room-card {
    display: flex;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin-bottom: 24px;
    overflow: hidden;
    align-items: stretch;
  }
  .room-img {
    width: 180px;
    height: 140px;
    object-fit: cover;
    flex-shrink: 0;
  }
  .room-info {
    flex: 1;
    padding: 16px;
  }
  .room-title {
    font-size: 1.2em;
    font-weight: bold;
    color: #27ae60;
    text-decoration: none;
  }
  .room-price {
    width: 160px;
    background: #f8f8f8;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px;
    border-left: 1px solid #eee;
  }
  .room-price .price {
    font-size: 1.3em;
    font-weight: bold;
    color: #27ae60;
  }
  .btn.green {
    background: #27ae60;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 8px 18px;
    margin: 4px 0;
    cursor: pointer;
    font-weight: 500;
  }
  .btn.green:disabled {
    background: #ccc;
    cursor: not-allowed;
  }
  .no-rooms {
    color: #c0392b;
    font-weight: bold;
    margin: 32px 0;
    text-align: center;
  }
  .room-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
  }
  .room-table th, .room-table td {
    border: 1px solid #eee;
    padding: 8px 12px;
    text-align: center;
  }
  .room-table th {
    background: #f8f8f8;
    color: #27ae60;
  }
  </style>
</head>
<body>

<!-- Main Navigation -->
<header class="main-header">
  <div class="brand">
  <img src="villa-valore-logo.png" alt="Villa Valore Logo" class="villa-logo">
  <div class="brand-text">
    <h1>Villa Valore Hotel</h1>
    <small>BIGA I, SILANG, CAVITE</small>
  </div>
  </div>
  <nav class="nav-links">
  <a href="booking.php">Rooms</a>
  <a href="about.php">About</a>
  <a href="mybookings.php">My Bookings</a>
  <?php if (isset($_SESSION['student_id'])): ?>
    <a href="account/change_password.php">Change Password</a>
    <a href="logout.php">Log Out</a>
  <?php else: ?>
    <a href="login.php">Log In</a>
  <?php endif; ?>
  </nav>
</header>

<!-- Header -->
<div class="header-img">
  <div class="hotel-info">
  <h2>Hotel Villa Valore</h2>
  <div class="info-line"><span class="icon" data-icon="fa-location-dot"></span> CvSU Avenue Brgy. Biga 1, Silang, Cavite 4118</div>
  <div class="info-line"><span class="icon" data-icon="fa-phone"></span> (046) 888-9900</div>
  <div class="info-line"><span class="icon" data-icon="fa-link"></span> <a href="https://cvsu-silang.edu.ph/" target="_blank">cvsu-silang.edu.ph</a></div>
  </div>
</div>
<script>
  // Replace .icon[data-icon] with FontAwesome icons
  document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.icon[data-icon]').forEach(function(el) {
    const icon = el.getAttribute('data-icon');
    el.innerHTML = `<i class="fa-solid ${icon}" aria-hidden="true"></i>`;
  });
  });
</script>

<!-- Booking Section -->
<form method="get" id="bookingForm" autocomplete="off">
<div class="booking-panel">
  <!-- Guests -->
  <div class="booking-box">
  <label><i class="fa-solid fa-user-group"></i> Guests</label>
  <div style="display: flex; align-items: center; gap: 12px;">
    <div>
    <span>Adults</span><br>
    <button type="button" id="adult-minus" style="width:28px; height:28px; border-radius:50%; border:1px solid #ccc; background:#fff; color:#333; font-size:18px; display:inline-flex; align-items:center; justify-content:center;">
      <i class="fa-solid fa-minus"></i>
    </button>
    <span id="adult-count" style="margin:0 6px;"><?= htmlspecialchars($selected['adults']) ?></span>
    <button type="button" id="adult-plus" style="width:28px; height:28px; border-radius:50%; border:1.5px solid #27ae60; background:#eafaf1; color:#27ae60; font-size:18px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 1px 3px rgba(39,174,96,0.08);">
      <i class="fa-solid fa-plus"></i>
    </button>
    <input type="hidden" name="adults" id="adults-input" value="<?= htmlspecialchars($selected['adults']) ?>">
    </div>
    <div>
    <span>Children</span><br>
    <button type="button" id="child-minus" style="width:28px; height:28px; border-radius:50%; border:1px solid #ccc; background:#fff; color:#333; font-size:18px; display:inline-flex; align-items:center; justify-content:center;">
      <i class="fa-solid fa-minus"></i>
    </button>
    <span id="child-count" style="margin:0 6px;"><?= htmlspecialchars($selected['children']) ?></span>
    <button type="button" id="child-plus" style="width:28px; height:28px; border-radius:50%; border:1.5px solid #27ae60; background:#eafaf1; color:#27ae60; font-size:18px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 1px 3px rgba(39,174,96,0.08);">
      <i class="fa-solid fa-plus"></i>
    </button>
    <input type="hidden" name="children" id="children-input" value="<?= htmlspecialchars($selected['children']) ?>">
    </div>
  </div>
  </div>
  <script>
  // Guest counter logic
  const adultCount = document.getElementById('adult-count');
  const childCount = document.getElementById('child-count');
  const adultsInput = document.getElementById('adults-input');
  const childrenInput = document.getElementById('children-input');
  document.getElementById('adult-minus').onclick = function() {
    let val = parseInt(adultCount.innerText);
    if (val > 1) { adultCount.innerText = val - 1; adultsInput.value = val - 1; updateCart(); autoSubmit(); }
  };
  document.getElementById('adult-plus').onclick = function() {
    let val = parseInt(adultCount.innerText);
    if (val < 6) { adultCount.innerText = val + 1; adultsInput.value = val + 1; updateCart(); autoSubmit(); }
  };
  document.getElementById('child-minus').onclick = function() {
    let val = parseInt(childCount.innerText);
    if (val > 0) { childCount.innerText = val - 1; childrenInput.value = val - 1; updateCart(); autoSubmit(); }
  };
  document.getElementById('child-plus').onclick = function() {
    let val = parseInt(childCount.innerText);
    if (val < 3) { childCount.innerText = val + 1; childrenInput.value = val + 1; updateCart(); autoSubmit(); }
  };
  </script>

  <!-- Check-in -->
  <div class="booking-box">
  <label><i class="fa-solid fa-calendar-days"></i> Check-in</label>
  <input type="date" id="checkin" name="checkin" value="<?= htmlspecialchars($selected['checkin']) ?>" required />
  <input type="time" id="checkin_time" name="checkin_time" value="<?= htmlspecialchars($selected['checkin_time']) ?>" />
  </div>

  <!-- Check-out -->
  <div class="booking-box">
  <label><i class="fa-solid fa-calendar-check"></i> Check-out</label>
  <input type="date" id="checkout" name="checkout" value="<?= htmlspecialchars($selected['checkout']) ?>" required />
  <input type="time" id="checkout_time" name="checkout_time" value="<?= htmlspecialchars($selected['checkout_time']) ?>" />
  </div>

  <!-- Cart -->
  <div class="cart-box">
  <p>Your Cart: <strong id="cart-items"><?= $total_guests ?> guest(s)</strong></p>
  <p>Room Type: <strong id="cart-roomtype"><?= ucfirst($selected['room_type']) ?: '-' ?></strong></p>
  <p>Check-in: <strong id="cart-checkin"><?= htmlspecialchars($selected['checkin']) ?> <?= htmlspecialchars($selected['checkin_time']) ?></strong></p>
  <p>Check-out: <strong id="cart-checkout"><?= htmlspecialchars($selected['checkout']) ?> <?= htmlspecialchars($selected['checkout_time']) ?></strong></p>
  <p>Total: ₱<strong id="cart-total"><?= $price ?></strong></p>
  <p style="font-size:0.9em;color:#888;" id="cart-hours"><?= $hoursLabel ? "Duration: $hoursLabel (" . round($hours) . " hour(s))" : "" ?></p>
  <p style="font-size:0.9em;color:#888;" id="cart-rf"><?= $rf ? "Reservation Fee: ₱$rf" : "" ?></p>
  <button type="submit" class="btn green" style="margin-top:12px;display:none;" id="showRoomsBtn">Show Available Rooms</button>
  </div>
</div>
</form>
<script>
  // Set minimum check-in date to today
  document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('checkin').min = today;
    document.getElementById('checkout').min = today;
    updateCart();
  });

  // Update checkout min date when checkin changes
  document.getElementById('checkin').addEventListener('change', function() {
    const checkinDate = new Date(this.value);
    const minCheckout = new Date(checkinDate.getTime() + 86400000).toISOString().split('T')[0];
    document.getElementById('checkout').min = minCheckout;
    if (document.getElementById('checkout').value < minCheckout) {
      document.getElementById('checkout').value = minCheckout;
    }
    updateCart();
    autoSubmit();
  });

  document.getElementById('checkin_time').addEventListener('change', function() {
    updateCart();
    autoSubmit();
  });
  document.getElementById('checkout').addEventListener('change', function() {
    updateCart();
    autoSubmit();
  });
  document.getElementById('checkout_time').addEventListener('change', function() {
    updateCart();
    autoSubmit();
  });

  function getRoomTypeByGuests(guests) {
    if (guests <= 2) return 'standard';
    if (guests <= 4) return 'deluxe';
    if (guests <= 6) return 'suite';
    return '';
  }

  function getHourDiff() {
    const checkinDate = document.getElementById('checkin').value;
    const checkoutDate = document.getElementById('checkout').value;
    const checkinTime = document.getElementById('checkin_time').value || "14:00";
    const checkoutTime = document.getElementById('checkout_time').value || "12:00";
    if (!checkinDate || !checkoutDate) return 0;
    const start = new Date(checkinDate + "T" + checkinTime);
    const end = new Date(checkoutDate + "T" + checkoutTime);
    let diff = (end - start) / (1000 * 60 * 60);
    return diff > 0 ? diff : 0;
  }

  function updateCart() {
    let totalGuests = parseInt(document.getElementById('adult-count').innerText) + parseInt(document.getElementById('child-count').innerText);
    document.getElementById('cart-items').innerText = `${totalGuests} guest(s)`;

    let roomType = getRoomTypeByGuests(totalGuests);
    document.getElementById('cart-roomtype').innerText = roomType ? roomType.charAt(0).toUpperCase() + roomType.slice(1) : '-';

    let checkin = document.getElementById('checkin').value;
    let checkinTime = document.getElementById('checkin_time').value;
    let checkout = document.getElementById('checkout').value;
    let checkoutTime = document.getElementById('checkout_time').value;
    document.getElementById('cart-checkin').innerText = checkin + " " + checkinTime;
    document.getElementById('cart-checkout').innerText = checkout + " " + checkoutTime;

    let hours = getHourDiff();
    let total = 0, rf = 0, hoursLabel = '', rfLabel = '';
    let pricing = {
      'standard': { 'hourly': 200, '6': {price:1100,rf:330}, '12': {price:2200,rf:660}, '24': {price:4500,rf:1350} },
      'deluxe': { 'hourly': 300, '6': {price:1600,rf:480}, '12': {price:3500,rf:1050}, '24': {price:7150,rf:2145} },
      'suite': { 'hourly': 500, '6': {price:2929,rf:878}, '12': {price:5989,rf:1796}, '24': {price:11899,rf:3569} }
    };
    if (roomType && hours > 0) {
      let h = Math.round(hours);
      if (h >= 24) {
        total = pricing[roomType]['24'].price;
        rf = pricing[roomType]['24'].rf;
        hoursLabel = '24 Hours';
      } else if (h >= 12) {
        total = pricing[roomType]['12'].price;
        rf = pricing[roomType]['12'].rf;
        hoursLabel = '12 Hours';
      } else if (h >= 6) {
        total = pricing[roomType]['6'].price;
        rf = pricing[roomType]['6'].rf;
        hoursLabel = '6 Hours';
      } else {
        total = h * pricing[roomType]['hourly'];
        rf = Math.round(total * 0.3);
        hoursLabel = h + ' Hour(s)';
      }
      document.getElementById('cart-total').innerText = total;
      document.getElementById('cart-hours').innerText = hours > 0 ? `Duration: ${hoursLabel} (${h} hour(s))` : '';
      document.getElementById('cart-rf').innerText = `Reservation Fee: ₱${rf}`;
    } else {
      document.getElementById('cart-total').innerText = '0';
      document.getElementById('cart-hours').innerText = '';
      document.getElementById('cart-rf').innerText = '';
    }
  }

  // Auto submit form when all required fields are filled
  function autoSubmit() {
    let adults = parseInt(document.getElementById('adults-input').value);
    let children = parseInt(document.getElementById('children-input').value);
    let checkin = document.getElementById('checkin').value;
    let checkout = document.getElementById('checkout').value;
    if (adults >= 1 && (adults + children) <= 6 && checkin && checkout) {
      document.getElementById('bookingForm').submit();
    }
  }
</script>
<br><br>

<div class="container">
  <h2>Available Rooms</h2>
  <?php if ($show_results): ?>
    <?php if (!$selected['room_type']): ?>
      <div class="no-rooms">Sorry, we cannot accommodate more than 6 guests in a single room.</div>
    <?php elseif (count($available_rooms) === 0): ?>
      <div class="no-rooms">No rooms available for <?= htmlspecialchars($total_guests) ?> guest(s).</div>
    <?php else: ?>
      <?php foreach ($available_rooms as $room): ?>
      <div class="room-card">
        <img src="<?= htmlspecialchars($room['img']) ?>" alt="<?= htmlspecialchars($room['name']) ?>" class="room-img"/>
        <div class="room-info">
        <a href="#" class="room-title"><?= htmlspecialchars($room['name']) ?></a>
        <p><?= htmlspecialchars($room['desc']) ?></p>
        <p><?= htmlspecialchars($room['beds']) ?> &nbsp; • &nbsp; Sleeps <?= $room['capacity'] ?> &nbsp; • &nbsp; <?= htmlspecialchars($room['size']) ?></p>
        </div>
        <div class="room-price">
        <p class="price">
          <?php
            $type = $room['type'];
            echo '₱' . number_format($room_pricing[$type]['hourly']);
          ?>
        </p>
        <p>Per Hour</p>
        <span style="font-size:0.95em;color:#888;">Max: <?= $room['capacity'] ?> guest(s)</span>
        <br>
        <?php
          // Build query string for booknow.php with all guest choices and price
          $query = http_build_query([
            'room' => $room['type'],
            'roomid' => $room['id'],
            'adults' => $selected['adults'],
            'children' => $selected['children'],
            'checkin' => $selected['checkin'],
            'checkout' => $selected['checkout'],
            'checkin_time' => $selected['checkin_time'],
            'checkout_time' => $selected['checkout_time'],
            'price' => $price,
            'rf' => $rf,
            'duration' => $hoursLabel,
          ]);
        ?>
        <button class="btn green" onclick="window.location.href='login.php?next=booknow.php&<?= $query ?>'">BOOK</button>
        <button class="btn green" onclick="window.location.href='login.php?next=reservenow.php&<?= $query ?>'">RESERVE</button>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php else: ?>
    <div style="color:#888; margin:32px 0;">Please select guests, check-in, and check-out to see available rooms.</div>
  <?php endif; ?>
</div>

</body>
</html>

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2025 at 05:05 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hotel_reservation_systemdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `accountID` int(11) NOT NULL,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `PhoneNumber` bigint(11) NOT NULL,
  `AccountType` enum('','Staff','Admin','') NOT NULL,
  `Status` enum('Active','Inactive','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`accountID`, `FirstName`, `LastName`, `Email`, `Password`, `PhoneNumber`, `AccountType`, `Status`) VALUES
(202310637, 'Phoebe Ann', 'Balderamos', 'phoebeann.balderamos@cvsu.edu.ph', 'phoebe@cvsuk', 9471275781, 'Admin', 'Active'),
(202310638, 'Thalia Joy', 'Bardaje', 'thaliajoy.bardaje@cvsu.edu.ph', 'thaliajoycvsu', 9506760174, 'Admin', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `BookingID` int(11) NOT NULL,
  `ReservationID` varchar(32) DEFAULT NULL,
  `BookingCode` varchar(32) DEFAULT NULL,
  `StudentID` int(11) NOT NULL,
  `RoomNumber` int(11) NOT NULL,
  `RoomType` enum('Standard','Deluxe','Suite','') NOT NULL,
  `BookingStatus` enum('Pending','Confirmed','Cancelled','Completed') NOT NULL,
  `RoomStatus` enum('Available','Booked','Reserved','Maintenance','Cleaning') NOT NULL,
  `Notes` text NOT NULL,
  `CheckInDate` date NOT NULL,
  `CheckOutDate` date NOT NULL,
  `BookingDate` date NOT NULL,
  `Price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`BookingID`, `ReservationID`, `BookingCode`, `StudentID`, `RoomNumber`, `RoomType`, `BookingStatus`, `RoomStatus`, `Notes`, `CheckInDate`, `CheckOutDate`, `BookingDate`, `Price`) VALUES
(10018, 'RS-06242025-0002', 'BK-06242025-0001', 202310596, 1102, 'Standard', 'Confirmed', 'Booked', '', '2025-06-25', '2025-06-30', '2025-06-24', 0),
(10022, 'RS-06242025-0003', 'BK-06242025-0002', 202310467, 1101, 'Standard', 'Confirmed', 'Booked', '', '2025-08-13', '2025-08-17', '2025-06-24', 0);

-- --------------------------------------------------------

--
-- Table structure for table `guest_requests`
--

CREATE TABLE `guest_requests` (
  `RequestID` varchar(20) NOT NULL,
  `GuestName` varchar(100) NOT NULL,
  `RoomNumber` int(11) NOT NULL,
  `RequestDetails` text NOT NULL,
  `Priority` enum('Low','High') DEFAULT 'Low',
  `Status` enum('Pending','In Progress','Completed','Cancelled') DEFAULT 'Pending',
  `RequestTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `temp_seq` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest_requests`
--

INSERT INTO `guest_requests` (`RequestID`, `GuestName`, `RoomNumber`, `RequestDetails`, `Priority`, `Status`, `RequestTime`, `temp_seq`) VALUES
('GRQ-06222025-0001', 'John Doe', 101, 'Extra towels needed', 'High', 'Pending', '2025-06-22 14:05:38', 1),
('GRQ-06222025-0002', 'Jane Smith', 205, 'Wake-up call at 7 AM', 'Low', 'Completed', '2025-06-22 12:05:38', 2),
('GRQ-06222025-0003', 'Mike Johnson', 302, 'Room service - dinner menu', 'Low', 'In Progress', '2025-06-22 13:05:38', 3),
('GRQ-06222025-0004', 'Sarah Wilson', 150, 'Fix air conditioning', 'High', 'Pending', '2025-06-22 14:05:38', 4),
('GRQ-06222025-0005', 'David Brown', 208, 'Extra pillows', 'Low', 'Completed', '2025-06-22 11:05:38', 5),
('GRQ-06222025-0006', 'John Doe Daw hehe', 101, 'Extra towels needed', 'High', 'Pending', '2025-06-22 14:10:42', 6),
('GRQ-06222025-0007', 'John Doe', 101, 'Extra towels needed', 'High', 'Pending', '2025-06-22 14:10:50', 7),
('GRQ-06222025-0008', 'John Doe', 101, 'Extra towels needed', 'High', 'Pending', '2025-06-22 14:11:02', 8),
('GRQ-06222025-0009', 'John Doe', 101, 'Extra towels needed', 'High', 'In Progress', '2025-06-22 14:44:16', 9),
('GRQ-06222025-0010', 'John Doe', 101, 'Extra towels needed', 'Low', 'Pending', '2025-06-22 14:48:54', 10),
('GRQ-06222025-0011', 'John Doe', 101, 'Extra towels needed', 'Low', 'In Progress', '2025-06-22 14:48:58', 11),
('GRQ-06222025-0012', 'John Doe', 101, 'Extra towels needed', 'High', 'Pending', '2025-06-22 14:49:15', 12),
('GRQ-06222025-0013', 'Sarah Wilson', 150, 'Fix air conditioning', 'High', 'In Progress', '2025-06-22 15:42:29', 13),
('GRQ-06252025-0001', 'Mariet', 1102, 'One towel', 'Low', 'Pending', '2025-06-25 02:22:59', 1),
('GRQ-06252025-0002', 'Mariet', 1102, 'One soap and toothbrush', 'Low', 'Pending', '2025-06-25 02:48:13', 2);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `ItemID` int(15) NOT NULL,
  `AdminID` int(15) NOT NULL,
  `StaffID` int(15) NOT NULL,
  `RequestID` int(15) NOT NULL,
  `ItemName` varchar(255) NOT NULL,
  `DateReceived` datetime NOT NULL,
  `DateExpiry` datetime NOT NULL,
  `Quantity` int(255) NOT NULL,
  `Price` decimal(10,0) NOT NULL,
  `Total` int(255) NOT NULL,
  `CurrentStocks` bigint(255) NOT NULL,
  `RqStocks` bigint(255) NOT NULL,
  `Status` enum('Approved','Denied','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`ItemID`, `AdminID`, `StaffID`, `RequestID`, `ItemName`, `DateReceived`, `DateExpiry`, `Quantity`, `Price`, `Total`, `CurrentStocks`, `RqStocks`, `Status`) VALUES
(7, 0, 0, 0, 'Pillow', '2025-06-21 20:00:06', '2025-06-27 20:00:06', 10, 250, 2500, 20, 0, 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `menu_service`
--

CREATE TABLE `menu_service` (
  `MenuID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Type` enum('Reception/Front Desk','Housekeeping','Room Service','Food & Beverage','Wi-Fi & Technology','Spa & Wellness','Safety & Security') NOT NULL,
  `Description` varchar(255) NOT NULL,
  `SellingPrice` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_service`
--

INSERT INTO `menu_service` (`MenuID`, `Name`, `Type`, `Description`, `SellingPrice`) VALUES
(1, 'Gym', 'Spa & Wellness', 'Stay fit during your stay with access to our fully equipped gym, available daily for all guests', 1500);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(15) NOT NULL,
  `StudentID` int(15) NOT NULL,
  `ReservationID` int(15) NOT NULL,
  `AdminID` int(15) NOT NULL,
  `BookingID` int(15) NOT NULL,
  `Amount` decimal(10,0) NOT NULL,
  `PaymentStatus` enum('Paid','Unpaid','Failed','Refunded') NOT NULL,
  `PaymentDate` datetime NOT NULL,
  `PaymentMethod` enum('Cash','Card','Online','') NOT NULL,
  `Discount` decimal(10,0) NOT NULL,
  `TotalBill` decimal(10,0) NOT NULL,
  `ReferenceCode` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`PaymentID`, `StudentID`, `ReservationID`, `AdminID`, `BookingID`, `Amount`, `PaymentStatus`, `PaymentDate`, `PaymentMethod`, `Discount`, `TotalBill`, `ReferenceCode`) VALUES
(2, 0, 20, 1111, 21, 2000, 'Unpaid', '2025-05-23 16:54:08', 'Cash', 400, 1600, '545465323ab'),
(202310637, 0, 20, 1111, 21, 2000, 'Paid', '2025-05-23 16:22:07', 'Cash', 400, 1600, '20265644522Fr88');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `ReservationID` varchar(255) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `GuestName` varchar(255) NOT NULL,
  `PCheckInDate` date NOT NULL,
  `PCheckOutDate` date NOT NULL,
  `RoomNumber` enum('1101','1102','1103','1104','1105','1106','1107','1108','1109') NOT NULL,
  `RoomType` enum('Standard','Deluxe','Suite','') NOT NULL,
  `Status` enum('Pending','Confirmed','Cancelled') NOT NULL,
  `RoomStatus` enum('Available','Booked','Reserved','Maintenance','Cleaning') NOT NULL,
  `ReservationFee` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`ReservationID`, `StudentID`, `GuestName`, `PCheckInDate`, `PCheckOutDate`, `RoomNumber`, `RoomType`, `Status`, `RoomStatus`, `ReservationFee`) VALUES
('RS-06242025-0002', 202310596, 'Mariet', '2025-06-25', '2025-06-30', '1102', 'Standard', 'Confirmed', 'Available', 0.00),
('RS-06242025-0003', 202310467, 'Ann', '2025-08-13', '2025-08-17', '1101', 'Standard', 'Confirmed', 'Available', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_cancellations`
--

CREATE TABLE `reservation_cancellations` (
  `CancellationID` int(11) NOT NULL,
  `ReservationID` varchar(20) NOT NULL,
  `CancellationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `CancellationReason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `RoomID` varchar(11) NOT NULL,
  `RoomNumber` int(11) NOT NULL,
  `RoomName` text NOT NULL,
  `RoomType` enum('Standard','Deluxe','Suite','') NOT NULL,
  `RoomPerHour` int(11) NOT NULL,
  `RoomStatus` enum('Available','Occupied','Maintenance','Cleaning') NOT NULL,
  `Capacity` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`RoomID`, `RoomNumber`, `RoomName`, `RoomType`, `RoomPerHour`, `RoomStatus`, `Capacity`) VALUES
('RM-1101-01', 1101, 'Serenity', 'Standard', 200, 'Available', '2'),
('RM-1102-02', 1102, 'Haven', 'Standard', 200, 'Available', '2'),
('RM-1103-03', 1103, 'Chamber', 'Standard', 200, 'Available', '2'),
('RM-1104-04', 1104, 'Family Retreat', 'Deluxe', 300, 'Available', '3'),
('RM-1105-05', 1105, 'Premier Loft', 'Deluxe', 300, 'Available', '3'),
('RM-1106-06', 1106, 'Luxe Escape', 'Deluxe', 300, 'Available', '3'),
('RM-1107-07', 1107, 'Executive Suite', 'Suite', 500, 'Available', '6'),
('RM-1108-08', 1108, 'Grand Villa', 'Suite', 500, 'Available', '6'),
('RM-1109-09', 1109, 'Royal Haven', 'Suite', 500, 'Available', '6');

-- --------------------------------------------------------

--
-- Table structure for table `stock_requests`
--

CREATE TABLE `stock_requests` (
  `RequestID` int(11) NOT NULL,
  `RequestedBy` varchar(100) DEFAULT NULL,
  `Department` varchar(100) DEFAULT NULL,
  `ProductName` varchar(100) DEFAULT NULL,
  `RequestedQuantity` int(11) DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `Priority` enum('Low','Medium','High') DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `RequestDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(9) NOT NULL,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Gender` enum('Male','Female','Prefer not to say','') NOT NULL,
  `PhoneNumber` varchar(11) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Nationality` text NOT NULL,
  `Birthdate` date NOT NULL,
  `Password` varchar(255) NOT NULL,
  `ConfirmPassword` varchar(255) NOT NULL,
  `OTP` varchar(100) NOT NULL,
  `OTP_Send_Time` varchar(100) NOT NULL,
  `Verify_OTP` varchar(100) NOT NULL,
  `IP` varchar(100) NOT NULL,
  `Status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `FirstName`, `LastName`, `Gender`, `PhoneNumber`, `Address`, `Email`, `Nationality`, `Birthdate`, `Password`, `ConfirmPassword`, `OTP`, `OTP_Send_Time`, `Verify_OTP`, `IP`, `Status`) VALUES
(202310467, 'Ann', 'Bardaje', '', '', '', '', '', '0000-00-00', '', '', '', '', '', '', ''),
(202310596, 'Mariet', 'Yater', 'Female', '2956478142', 'Sample Address', 'mariet.yater@example.com', 'Filipino', '0000-00-00', '', '', '', '', '', '', ''),
(202310637, 'Phoebe Ann', 'Balderamos', 'Male', '', '', 'phoebeannbalderamos001@gmail.com', '', '0000-00-00', '$2y$10$mOyNgj4cWtGEz8Y3WP4OQuGAHcDskHp9P5EP24x9A.3Puk97HMAXe', 'pbpb09', '', '', '', '', ''),
(202365433, 'Joy Joy', 'Happy', 'Male', '', '', 'phoebeanncap08@gmail.com', '', '0000-00-00', '$2y$10$aVOhd6FJb/Flc7x7mTeZiO91tiNh1QkYM752GT69TDxiwcRaY8jHi', 'joyjoy98', '', '', '', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`accountID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`BookingID`),
  ADD UNIQUE KEY `BookingCode` (`BookingCode`);

--
-- Indexes for table `guest_requests`
--
ALTER TABLE `guest_requests`
  ADD PRIMARY KEY (`RequestID`),
  ADD KEY `idx_status` (`Status`),
  ADD KEY `idx_priority` (`Priority`),
  ADD KEY `idx_room` (`RoomNumber`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`ItemID`),
  ADD UNIQUE KEY `RequestID` (`RequestID`);

--
-- Indexes for table `menu_service`
--
ALTER TABLE `menu_service`
  ADD PRIMARY KEY (`MenuID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`ReservationID`);

--
-- Indexes for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  ADD PRIMARY KEY (`CancellationID`),
  ADD KEY `idx_reservation_id` (`ReservationID`),
  ADD KEY `idx_cancellation_date` (`CancellationDate`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`RoomNumber`);

--
-- Indexes for table `stock_requests`
--
ALTER TABLE `stock_requests`
  ADD PRIMARY KEY (`RequestID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `accountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202310639;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10023;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `ItemID` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menu_service`
--
ALTER TABLE `menu_service`
  MODIFY `MenuID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202310638;

--
-- AUTO_INCREMENT for table `reservation_cancellations`
--
ALTER TABLE `reservation_cancellations`
  MODIFY `CancellationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_requests`
--
ALTER TABLE `stock_requests`
  MODIFY `RequestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudentID` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202365438;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

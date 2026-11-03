-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 03, 2026 at 06:09 PM
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
-- Database: `hostelia`
--

-- --------------------------------------------------------

--
-- Table structure for table `hostel_rooms`
--

CREATE TABLE `hostel_rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(30) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hostel_rooms`
--

INSERT INTO `hostel_rooms` (`id`, `room_number`, `room_type`, `capacity`, `is_active`, `created_at`) VALUES
(1, 'S101', 'Single Sharing', 1, 1, '2026-10-31 19:23:58'),
(2, 'S102', 'Single Sharing', 1, 1, '2026-10-31 19:23:58'),
(3, 'S103', 'Single Sharing', 1, 1, '2026-10-31 19:23:58'),
(4, 'S104', 'Single Sharing', 1, 1, '2026-10-31 19:23:58'),
(5, 'S105', 'Single Sharing', 1, 1, '2026-10-31 19:23:58'),
(6, 'D201', 'Double Sharing', 2, 1, '2026-10-31 19:23:58'),
(7, 'D202', 'Double Sharing', 2, 1, '2026-10-31 19:23:58'),
(8, 'D203', 'Double Sharing', 2, 1, '2026-10-31 19:23:58'),
(9, 'D204', 'Double Sharing', 2, 1, '2026-10-31 19:23:58'),
(10, 'D205', 'Double Sharing', 2, 1, '2026-10-31 19:23:58'),
(11, 'T301', 'Triple Sharing', 3, 1, '2026-10-31 19:23:58'),
(12, 'T302', 'Triple Sharing', 3, 1, '2026-10-31 19:23:58'),
(13, 'T303', 'Triple Sharing', 3, 1, '2026-10-31 19:23:58'),
(14, 'T304', 'Triple Sharing', 3, 1, '2026-10-31 19:23:58'),
(15, 'T305', 'Triple Sharing', 3, 1, '2026-10-31 19:23:58'),
(16, 'Q401', 'Quad Sharing', 4, 1, '2026-10-31 19:23:58'),
(17, 'Q402', 'Quad Sharing', 4, 1, '2026-10-31 19:23:58'),
(18, 'Q403', 'Quad Sharing', 4, 1, '2026-10-31 19:23:58'),
(19, 'Q404', 'Quad Sharing', 4, 1, '2026-10-31 19:23:58'),
(20, 'Q405', 'Quad Sharing', 4, 1, '2026-10-31 19:23:58');

-- --------------------------------------------------------

--
-- Table structure for table `rent_payments`
--

CREATE TABLE `rent_payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_status` enum('Pending','Partial','Paid') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rent_payments`
--

INSERT INTO `rent_payments` (`id`, `booking_id`, `student_id`, `amount_due`, `amount_paid`, `due_date`, `payment_date`, `payment_status`, `created_at`) VALUES
(1, 1, 13, 7999.00, 7999.00, '2026-11-09', '2026-11-02 00:00:00', 'Paid', '2026-11-01 20:16:16'),
(2, 2, 12, 6599.00, 3000.00, '2026-11-09', '2026-11-02 00:00:00', 'Partial', '2026-11-01 20:25:10'),
(3, 3, 13, 9749.00, 0.00, '2026-11-10', NULL, 'Pending', '2026-11-03 13:32:24');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `facilities` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_type`, `monthly_rent`, `facilities`, `created_at`) VALUES
(1, 'Single Sharing', 8499.00, 'Wi-Fi, AC, Study Table, Attached Bathroom, Geyser, Laundry', '2026-10-31 17:59:27'),
(2, 'Double Sharing', 6999.00, 'Wi-Fi, AC, Study Table, Attached Bathroom, Geyser, Laundry', '2026-10-31 17:59:27'),
(3, 'Triple Sharing', 5499.00, 'Wi-Fi, AC, Study Table, Attached Bathroom, Geyser, Laundry', '2026-10-31 17:59:27'),
(4, 'Quad Sharing', 3999.00, 'Wi-Fi, AC, Study Table, Attached Bathroom, Geyser, Laundry', '2026-10-31 17:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `room_allocations`
--

CREATE TABLE `room_allocations` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `hostel_room_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_bookings`
--

CREATE TABLE `room_bookings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `room_type` varchar(50) NOT NULL,
  `selected_facilities` text NOT NULL,
  `base_rent` decimal(10,2) NOT NULL,
  `facility_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_rent` decimal(10,2) NOT NULL,
  `booking_status` enum('Pending','Confirmed','Cancellation Requested','Cancelled') NOT NULL DEFAULT 'Pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancellation_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_bookings`
--

INSERT INTO `room_bookings` (`id`, `student_id`, `room_type`, `selected_facilities`, `base_rent`, `facility_charge`, `total_rent`, `booking_status`, `booking_date`, `cancellation_date`) VALUES
(1, 13, 'Double Sharing', '[\"AC\",\"Laundry\"]', 6999.00, 1000.00, 7999.00, 'Cancelled', '2026-10-31 19:35:41', '2026-11-03 18:52:07'),
(2, 12, 'Triple Sharing', '[\"Wi-Fi\",\"AC\",\"Laundry\"]', 5499.00, 1100.00, 6599.00, 'Cancelled', '2026-11-01 20:24:42', '2026-11-02 02:14:54'),
(3, 13, 'Single Sharing', '[\"Wi-Fi\",\"AC\",\"Attached Bathroom\",\"Geyser\"]', 8499.00, 1250.00, 9749.00, 'Cancelled', '2026-11-03 13:23:36', '2026-11-03 22:09:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hostel_rooms`
--
ALTER TABLE `hostel_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `rent_payments`
--
ALTER TABLE `rent_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_type` (`room_type`);

--
-- Indexes for table `room_allocations`
--
ALTER TABLE `room_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD UNIQUE KEY `unique_student_room` (`student_id`,`hostel_room_id`),
  ADD KEY `hostel_room_id` (`hostel_room_id`);

--
-- Indexes for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hostel_rooms`
--
ALTER TABLE `hostel_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `rent_payments`
--
ALTER TABLE `rent_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room_allocations`
--
ALTER TABLE `room_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `room_bookings`
--
ALTER TABLE `room_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rent_payments`
--
ALTER TABLE `rent_payments`
  ADD CONSTRAINT `rent_payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `room_bookings` (`id`),
  ADD CONSTRAINT `rent_payments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `room_allocations`
--
ALTER TABLE `room_allocations`
  ADD CONSTRAINT `room_allocations_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `room_bookings` (`id`),
  ADD CONSTRAINT `room_allocations_ibfk_2` FOREIGN KEY (`hostel_room_id`) REFERENCES `hostel_rooms` (`id`),
  ADD CONSTRAINT `room_allocations_ibfk_3` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `room_bookings`
--
ALTER TABLE `room_bookings`
  ADD CONSTRAINT `room_bookings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

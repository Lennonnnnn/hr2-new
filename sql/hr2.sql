-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 09, 2024 at 04:27 PM
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
-- Database: `hr2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_evaluations`
--

CREATE TABLE `admin_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `a_id` int(11) NOT NULL,
  `e_id` int(10) UNSIGNED NOT NULL,
  `department` varchar(255) NOT NULL,
  `quality` decimal(3,2) NOT NULL,
  `communication_skills` decimal(3,2) NOT NULL,
  `teamwork` decimal(3,2) NOT NULL,
  `punctuality` decimal(3,2) NOT NULL,
  `initiative` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_recognition`
--

CREATE TABLE `admin_recognition` (
  `id` int(25) NOT NULL,
  `your_name` char(25) NOT NULL,
  `recipients_name` char(25) NOT NULL,
  `performance_area` char(25) NOT NULL,
  `recognition_message` char(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_register`
--

CREATE TABLE `admin_register` (
  `a_id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `birthdate` date NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `position` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `available_leaves` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `pfp` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_register`
--

INSERT INTO `admin_register` (`a_id`, `firstname`, `middlename`, `lastname`, `birthdate`, `email`, `password`, `role`, `position`, `department`, `available_leaves`, `phone_number`, `address`, `pfp`) VALUES
(12, 'Wendel', '', 'Ureta', '2024-10-28', 'uretawendel@gmail.com', '$2y$10$PJtTaPBrJvJIU8T2G1ZAzeiuq1/HfGe6mNerSq7V/YlLDsEzIUUhm', 'Admin', '', '', 0, '09123456789', 'Caloocan', ''),
(20, 'Thirdy', '', 'Murillo', '2024-11-09', 'rmmurillo2002@gmail.com', '$2y$10$ShHkOo/.jY10CRqzYR4pl.RrYsUmrCHERMYjdzfuW0N7yWFQvrj4C', 'Admin', '', '', 0, '09123456789', 'Caloocan', ''),
(21, 'Steffano', '', 'Dizo', '2024-11-09', 'dsteffano011402@gmail.com', '$2y$10$jcQ/z7E2/irQMsImZHtfQ.R/JrVqj6eOyYOp6I4wYhrcHb8ZoNDYe', 'Admin', '', '', 0, '09123456789', 'Caloocan', ''),
(22, 'Lennon', '', 'Aguilor', '2024-11-09', '012@gmail.com', '$2y$10$xxvACszjP6MNMmB9jGo4FuRgR0ChIdhQgr5quvNEMhsDHAtR4SoMi', 'Admin', '', '', 0, '09123456789', 'Caloocan', '');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `action` enum('Time In','Time Out') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('Admin','Employee') NOT NULL,
  `comment_content` text NOT NULL,
  `comment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_register`
--

CREATE TABLE `employee_register` (
  `e_id` int(11) UNSIGNED NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `birthdate` date NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `available_leaves` int(11) NOT NULL DEFAULT 0,
  `phone_number` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `pfp` longblob NOT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_register`
--

INSERT INTO `employee_register` (`e_id`, `firstname`, `middlename`, `lastname`, `birthdate`, `email`, `password`, `role`, `position`, `department`, `available_leaves`, `phone_number`, `address`, `pfp`, `qr_code_path`) VALUES
(1, 'Steffano', '', 'Dizo', '0000-00-00', 'dsteffano011402@gmail.com', '$2y$10$Sc/nOae2bCo8bGTMQhe79ubg57.TkXTQm9TpO7ZP2XMrY3xaBoAZy', 'Employee', 'Financial Controller', 'Finance Department', 0, '09123456789', 'Caloocan', '', 'QR/employee_1.png'),
(2, 'Wendel', '', 'Ureta', '0000-00-00', 'uretawendel@gmail.com', '$2y$10$Kwtk2J8mqTXDRNcgHbh/duoSZfekM5e.AepF05qHxhLvmgR6FOZ92', 'Employee', 'Operations Manager', 'Administration Department', 0, '09123456789', 'Caloocan', '', 'QR/employee_2.png'),
(3, 'Thirdy', '', 'Murillo', '2002-10-15', 'rmmurillo2002@gmail.com', '$2y$10$dGcIE2sCljwNb8N7rMMWzOAE4qt0qQahC7ajTh/i8McWoTS9sMPPy', 'Employee', 'Sales Manager', 'Sales Department', 0, '09123456789', 'Caloocan', '', 'QR/employee_3.png'),
(4, 'Lennon', '', 'Aguilor', '0000-00-00', '123@gmail.com', '$2y$10$2W30BLJLab9DRWU052AU7OU86KKJcJVAZA2bzj3B5vSb48hFyY4hu', 'Employee', 'Loan Officer', 'Credit Department', 0, '09123456789', 'Caloocan', '', 'QR/employee_4.png'),
(5, 'Wensi', '', 'Cornejo', '0000-00-00', '321@gmail.com', '$2y$10$7FtQRHCn4tIylKvXRLPVNe4KoYC0hAAtod5T83ylzUcNcWOeraWri', 'Employee', 'HR Manager', 'Human Resource Department', 0, '09123456789', 'Caloocan', '', 'QR/employee_5.png'),
(6, 'Ryme', '', 'Simbajon', '0000-00-00', '012@gmail.com', '$2y$10$yhxa6xS5v/xHkq2GxXQwSebhBQYf2poW7gYimBLgu/HUj/t7tC9SK', 'Employee', 'IT Manager', 'IT Department', 0, '09123456789', 'Caloocan', '', 'QR/employee_6.png');

-- --------------------------------------------------------

--
-- Table structure for table `leave_allocations`
--

CREATE TABLE `leave_allocations` (
  `crdt_id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL,
  `leave_days` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `leave_id` int(10) UNSIGNED NOT NULL,
  `e_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `leave_type` enum('Annual Leave','Sick Leave','Family Leave') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Denied') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `non_working_days`
--

CREATE TABLE `non_working_days` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `type` enum('regular','irregular') DEFAULT 'irregular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `non_working_days`
--

INSERT INTO `non_working_days` (`id`, `date`, `description`, `type`) VALUES
(1, '2019-01-01', 'New Year\'s Day', 'regular'),
(2, '2019-04-09', 'Araw ng Kagitingan', 'regular'),
(3, '2019-05-01', 'Labor Day', 'regular'),
(4, '2019-06-12', 'Independence Day', 'regular'),
(7, '2019-08-28', 'National Heroes Day', 'regular'),
(10, '2019-11-01', 'All Saint\'s Day', 'regular'),
(14, '2019-11-30', 'Bonifacio Day', 'regular'),
(20, '2019-12-25', 'Christmas Day', 'regular'),
(22, '2019-12-30', 'Rizal Day', 'regular'),
(26, '2020-01-01', 'New Year\'s Day', 'regular'),
(31, '2020-04-09', 'Araw ng Kagitingan', 'regular'),
(37, '2020-05-01', 'Labor Day', 'regular'),
(43, '2020-06-12', 'Independence Day', 'regular'),
(49, '2020-08-28', 'National Heroes Day', 'regular'),
(52, '2020-11-01', 'All Saint\'s Day', 'regular'),
(60, '2020-11-30', 'Bonifacio Day', 'regular'),
(62, '2020-12-25', 'Christmas Day', 'regular'),
(64, '2020-12-30', 'Rizal Day', 'regular'),
(72, '2021-01-01', 'New Year\'s Day', 'regular'),
(77, '2021-04-09', 'Araw ng Kagitingan', 'regular'),
(84, '2021-05-01', 'Labor Day', 'regular'),
(91, '2021-06-12', 'Independence Day', 'regular'),
(93, '2021-08-28', 'National Heroes Day', 'regular'),
(100, '2021-11-01', 'All Saint\'s Day', 'regular'),
(107, '2021-11-30', 'Bonifacio Day', 'regular'),
(110, '2021-12-25', 'Christmas Day', 'regular'),
(111, '2021-12-30', 'Rizal Day', 'regular'),
(117, '2022-01-01', 'New Year\'s Day', 'regular'),
(121, '2022-04-09', 'Araw ng Kagitingan', 'regular'),
(127, '2022-05-01', 'Labor Day', 'regular'),
(135, '2022-06-12', 'Independence Day', 'regular'),
(139, '2022-08-28', 'National Heroes Day', 'regular'),
(148, '2022-11-01', 'All Saint\'s Day', 'regular'),
(156, '2022-11-30', 'Bonifacio Day', 'regular'),
(157, '2022-12-25', 'Christmas Day', 'regular'),
(170, '2022-12-30', 'Rizal Day', 'regular'),
(176, '2023-01-01', 'New Year\'s Day', 'regular'),
(183, '2023-04-09', 'Araw ng Kagitingan', 'regular'),
(190, '2023-05-01', 'Labor Day', 'regular'),
(197, '2023-06-12', 'Independence Day', 'regular'),
(199, '2023-08-28', 'National Heroes Day', 'regular'),
(206, '2023-11-01', 'All Saint\'s Day', 'regular'),
(208, '2023-11-30', 'Bonifacio Day', 'regular'),
(215, '2023-12-25', 'Christmas Day', 'regular'),
(222, '2023-12-30', 'Rizal Day', 'regular'),
(229, '2024-01-01', 'New Year\'s Day', 'regular'),
(236, '2024-04-09', 'Araw ng Kagitingan', 'regular'),
(245, '2024-05-01', 'Labor Day', 'regular'),
(249, '2024-06-12', 'Independence Day', 'regular'),
(254, '2024-08-28', 'National Heroes Day', 'regular'),
(260, '2024-11-01', 'All Saint\'s Day', 'regular'),
(268, '2024-11-30', 'Bonifacio Day', 'regular'),
(274, '2024-12-25', 'Christmas Day', 'regular'),
(282, '2024-12-30', 'Rizal Day', 'regular'),
(289, '2025-01-01', 'New Year\'s Day', 'regular'),
(295, '2025-04-09', 'Araw ng Kagitingan', 'regular'),
(303, '2025-05-01', 'Labor Day', 'regular'),
(309, '2025-06-12', 'Independence Day', 'regular'),
(317, '2025-08-28', 'National Heroes Day', 'regular'),
(324, '2025-11-01', 'All Saint\'s Day', 'regular'),
(325, '2025-11-30', 'Bonifacio Day', 'regular'),
(334, '2025-12-25', 'Christmas Day', 'regular'),
(337, '2025-12-30', 'Rizal Day', 'regular'),
(338, '2026-01-01', 'New Year\'s Day', 'regular'),
(339, '2026-04-09', 'Araw ng Kagitingan', 'regular'),
(340, '2026-05-01', 'Labor Day', 'regular'),
(347, '2026-06-12', 'Independence Day', 'regular'),
(353, '2026-08-28', 'National Heroes Day', 'regular'),
(355, '2026-11-01', 'All Saint\'s Day', 'regular'),
(359, '2026-11-30', 'Bonifacio Day', 'regular'),
(366, '2026-12-25', 'Christmas Day', 'regular'),
(372, '2026-12-30', 'Rizal Day', 'regular'),
(379, '2027-01-01', 'New Year\'s Day', 'regular'),
(386, '2027-04-09', 'Araw ng Kagitingan', 'regular'),
(392, '2027-05-01', 'Labor Day', 'regular'),
(401, '2027-06-12', 'Independence Day', 'regular'),
(408, '2027-08-28', 'National Heroes Day', 'regular'),
(411, '2027-11-01', 'All Saint\'s Day', 'regular'),
(418, '2027-11-30', 'Bonifacio Day', 'regular'),
(425, '2027-12-25', 'Christmas Day', 'regular'),
(433, '2027-12-30', 'Rizal Day', 'regular'),
(438, '2028-01-01', 'New Year\'s Day', 'regular'),
(444, '2028-04-09', 'Araw ng Kagitingan', 'regular'),
(453, '2028-05-01', 'Labor Day', 'regular'),
(460, '2028-06-12', 'Independence Day', 'regular'),
(462, '2028-08-28', 'National Heroes Day', 'regular'),
(463, '2028-11-01', 'All Saint\'s Day', 'regular'),
(470, '2028-11-30', 'Bonifacio Day', 'regular'),
(478, '2028-12-25', 'Christmas Day', 'regular'),
(486, '2028-12-30', 'Rizal Day', 'regular'),
(493, '2029-01-01', 'New Year\'s Day', 'regular'),
(500, '2029-04-09', 'Araw ng Kagitingan', 'regular'),
(507, '2029-05-01', 'Labor Day', 'regular'),
(514, '2029-06-12', 'Independence Day', 'regular'),
(518, '2029-08-28', 'National Heroes Day', 'regular'),
(525, '2029-11-01', 'All Saint\'s Day', 'regular'),
(532, '2029-11-30', 'Bonifacio Day', 'regular'),
(534, '2029-12-25', 'Christmas Day', 'regular'),
(541, '2029-12-30', 'Rizal Day', 'regular');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `post_content` text NOT NULL,
  `post_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_evaluations`
--
ALTER TABLE `admin_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `e_id` (`e_id`);

--
-- Indexes for table `admin_recognition`
--
ALTER TABLE `admin_recognition`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_register`
--
ALTER TABLE `admin_register`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `employee_register`
--
ALTER TABLE `employee_register`
  ADD PRIMARY KEY (`e_id`);

--
-- Indexes for table `leave_allocations`
--
ALTER TABLE `leave_allocations`
  ADD PRIMARY KEY (`crdt_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `e_id` (`e_id`);

--
-- Indexes for table `non_working_days`
--
ALTER TABLE `non_working_days`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_evaluations`
--
ALTER TABLE `admin_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_recognition`
--
ALTER TABLE `admin_recognition`
  MODIFY `id` int(25) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_register`
--
ALTER TABLE `admin_register`
  MODIFY `a_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_register`
--
ALTER TABLE `employee_register`
  MODIFY `e_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `leave_allocations`
--
ALTER TABLE `leave_allocations`
  MODIFY `crdt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `leave_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `non_working_days`
--
ALTER TABLE `non_working_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7926;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_evaluations`
--
ALTER TABLE `admin_evaluations`
  ADD CONSTRAINT `admin_evaluations_ibfk_1` FOREIGN KEY (`e_id`) REFERENCES `employee_register` (`e_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`e_id`) REFERENCES `employee_register` (`e_id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_register` (`a_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

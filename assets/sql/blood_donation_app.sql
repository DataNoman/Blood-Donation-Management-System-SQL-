-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 10, 2025 at 12:10 PM
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
-- Database: `blood_donation_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `blood_banks`
--

CREATE TABLE `blood_banks` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_banks`
--

INSERT INTO `blood_banks` (`id`, `name`, `location`, `contact`) VALUES
(1, 'Quantum Blood Bank', 'Shantinagar, Dhaka', '01712345678'),
(2, 'Red Crescent Blood Bank', 'Mohammadpur, Dhaka', '01912345678'),
(4, 'Basundhara Blood Bank', 'Basundhara City, Dhaka', '01756986532');

-- --------------------------------------------------------

--
-- Table structure for table `blood_types`
--

CREATE TABLE `blood_types` (
  `id` int(11) NOT NULL,
  `type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_types`
--

INSERT INTO `blood_types` (`id`, `type`) VALUES
(1, 'A+'),
(2, 'A-'),
(3, 'B+'),
(4, 'B-'),
(5, 'AB+'),
(6, 'AB-'),
(7, 'O+'),
(8, 'O-');

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE `chat` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'Links to the specific blood request',
  `sender_user_id` int(11) NOT NULL COMMENT 'ID of the user who sent the message (from users table)',
  `receiver_user_id` int(11) NOT NULL COMMENT 'ID of the user who is the intended recipient',
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat`
--

INSERT INTO `chat` (`id`, `request_id`, `sender_user_id`, `receiver_user_id`, `message`, `sent_at`, `is_read`) VALUES
(1, 13, 9, 12, 'hi', '2025-10-09 16:26:45', 0),
(2, 13, 9, 12, 'Hwllow', '2025-10-09 16:32:42', 0),
(3, 13, 9, 12, 'How are you doing?', '2025-10-09 16:32:53', 0),
(4, 13, 12, 9, 'hellow', '2025-10-09 17:05:06', 0),
(5, 13, 12, 9, 'how are you doing', '2025-10-09 17:05:15', 0),
(6, 13, 9, 12, 'I am fine', '2025-10-09 17:05:50', 0),
(7, 13, 9, 12, 'Nice to meet you', '2025-10-09 17:05:59', 0),
(8, 13, 12, 9, '😎', '2025-10-09 17:06:54', 0),
(9, 5, 8, 9, 'hi', '2025-10-09 17:14:09', 0),
(10, 16, 13, 9, 'Hi, I saw your post of urgent blood need', '2025-10-09 18:33:59', 0),
(11, 16, 9, 13, 'Thank you for reaching out', '2025-10-09 18:34:27', 0),
(12, 5, 8, 9, 'hellow', '2025-10-10 09:38:19', 0),
(13, 16, 9, 13, 'hi', '2025-10-10 09:39:20', 0),
(14, 16, 9, 13, 'hellow', '2025-10-10 09:39:23', 0),
(15, 13, 12, 9, 'hi', '2025-10-10 09:40:03', 0);

-- --------------------------------------------------------

--
-- Table structure for table `compatibility`
--

CREATE TABLE `compatibility` (
  `id` int(11) NOT NULL,
  `donor_type_id` int(11) NOT NULL,
  `recipient_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `compatibility`
--

INSERT INTO `compatibility` (`id`, `donor_type_id`, `recipient_type_id`) VALUES
(1, 1, 1),
(2, 1, 5),
(3, 2, 1),
(4, 2, 2),
(5, 2, 5),
(6, 2, 6),
(7, 3, 3),
(8, 3, 5),
(9, 4, 3),
(10, 4, 4),
(11, 4, 5),
(12, 4, 6),
(13, 5, 5),
(14, 6, 5),
(15, 6, 6),
(16, 7, 1),
(17, 7, 3),
(18, 7, 5),
(19, 7, 7),
(20, 8, 1),
(21, 8, 2),
(22, 8, 3),
(23, 8, 4),
(24, 8, 5),
(25, 8, 6),
(26, 8, 7),
(27, 8, 8);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Akas Hasan', 'akas1232@gmail.com', 'Is there any charge for blood receiving ?', 'replied', '2025-10-04 08:48:24', '2025-10-06 18:48:07'),
(2, 'Sakil', 'ahamed1334@gmail.com', 'How much blood Can I donate at a time?', 'replied', '2025-10-04 10:13:28', '2025-10-06 18:48:18'),
(3, 'Mamun', 'mm765@gmail.com', 'Where is your location? ', 'read', '2025-10-04 10:48:11', '2025-10-06 18:48:16'),
(4, 'Sabit Islam', 'sabit958@gmail.com', 'Do you guys provide 24/7 help ?', 'read', '2025-10-04 10:59:56', '2025-10-05 18:27:47'),
(5, 'MD. Faisal', 'trc33@netfbd.club', 'Is this message Working ?\r\n ', 'unread', '2025-10-05 18:29:26', '2025-10-05 18:29:26'),
(6, 'Mustak Ahamed', 'oushf@netfbd.club', 'Is there any rate limit?', 'unread', '2025-10-05 18:31:26', '2025-10-06 18:35:24'),
(8, 'noman ', 'noman@gmail.com', 'urgent blood need', 'unread', '2025-10-07 04:05:14', '2025-10-07 04:05:14'),
(9, 'MD. Noman ', 'noman@gmail.com', 'urgent blood need', 'unread', '2025-10-08 07:19:44', '2025-10-08 07:19:44');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `blood_bank_id` int(11) NOT NULL,
  `donation_date` date NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `status` enum('Pending','Completed','Canceled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_id`, `blood_bank_id`, `donation_date`, `quantity_ml`, `status`) VALUES
(3, 5, 4, '2024-10-09', 450, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE `donors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `age` int(11) NOT NULL,
  `blood_type_id` int(11) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `last_donation_date` date DEFAULT NULL,
  `health_status` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donors`
--

INSERT INTO `donors` (`id`, `user_id`, `age`, `blood_type_id`, `gender`, `last_donation_date`, `health_status`, `location`, `phone`) VALUES
(3, 4, 21, 6, 'Male', NULL, 'Perfectly Healthy', NULL, NULL),
(4, 6, 21, 3, 'Male', NULL, 'Perfectly healthy', 'Uttara, Dhaka', NULL),
(5, 8, 23, 3, 'Male', '2024-05-12', 'Fit to Donate', NULL, NULL),
(6, 10, 25, 8, 'Male', NULL, 'Good Health', NULL, NULL),
(7, 11, 45, 8, 'Male', '2024-06-17', 'Fit to donate', 'Badda, Dhaka', '01578976371'),
(8, 12, 20, 8, 'Male', '2024-05-13', 'Fit to donate', 'Badda, Dhaka', '01578976371'),
(9, 13, 55, 8, 'Male', NULL, 'Fit to Donate', 'Gulshan, Dhaka', '01748653294');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `bank_id` int(11) NOT NULL,
  `blood_type_id` int(11) NOT NULL,
  `quantity_ml` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `bank_id`, `blood_type_id`, `quantity_ml`, `last_updated`) VALUES
(1, 1, 1, 5000, '2025-09-29 09:55:47'),
(2, 1, 8, 500, '2025-09-29 16:30:44'),
(3, 1, 3, 4000, '2025-09-29 09:55:47'),
(4, 2, 1, 3500, '2025-09-29 09:55:47'),
(5, 2, 7, 8000, '2025-09-29 09:55:47'),
(6, 2, 6, 1500, '2025-09-30 05:42:08'),
(9, 1, 4, 3500, '2025-09-29 16:16:49'),
(10, 2, 5, 2500, '2025-09-29 16:17:05'),
(11, 2, 2, 3000, '2025-09-29 16:17:28'),
(12, 4, 1, 1500, '2025-09-29 16:42:24'),
(13, 4, 6, 2500, '2025-09-29 16:42:37'),
(14, 4, 4, 2000, '2025-09-30 05:43:12');

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `match_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipients`
--

CREATE TABLE `recipients` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `blood_type_id` int(11) NOT NULL,
  `urgency_level` enum('Low','Medium','High') NOT NULL,
  `hospital` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipients`
--

INSERT INTO `recipients` (`id`, `user_id`, `blood_type_id`, `urgency_level`, `hospital`, `phone`) VALUES
(2, 5, 5, 'High', 'United Medical College and Hospital', NULL),
(3, 7, 8, 'High', 'Mugdha Medical College', NULL),
(4, 9, 3, 'High', 'Ibne Sina', '01756265454');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `blood_type_id` int(11) NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `status` enum('Open','Fulfilled','Canceled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `recipient_id`, `blood_type_id`, `quantity_ml`, `request_date`, `status`) VALUES
(5, 4, 3, 450, '2025-10-02', 'Fulfilled'),
(6, 4, 8, 800, '2025-10-02', 'Fulfilled'),
(7, 4, 3, 200, '2025-10-02', 'Fulfilled'),
(8, 4, 1, 500, '2025-10-04', 'Open'),
(9, 4, 5, 200, '2025-10-05', 'Fulfilled'),
(10, 4, 3, 250, '2025-10-05', 'Open'),
(11, 4, 5, 200, '2025-10-07', 'Open'),
(12, 4, 5, 100, '2025-10-08', 'Fulfilled'),
(13, 4, 8, 100, '2025-10-08', 'Fulfilled'),
(14, 4, 6, 1050, '2025-10-08', 'Open'),
(15, 4, 4, 250, '2025-10-08', 'Open'),
(16, 4, 2, 500, '2025-10-08', 'Fulfilled');

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE `responses` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `response_date` datetime NOT NULL,
  `status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `responses`
--

INSERT INTO `responses` (`id`, `donor_id`, `request_id`, `response_date`, `status`) VALUES
(1, 5, 5, '2025-10-02 21:21:28', 'Accepted'),
(2, 6, 6, '2025-10-02 21:42:52', 'Accepted'),
(3, 5, 7, '2025-10-03 00:04:41', 'Rejected'),
(4, 5, 10, '2025-10-06 00:33:28', 'Rejected'),
(5, 5, 9, '2025-10-06 01:14:32', 'Rejected'),
(8, 8, 15, '2025-10-08 20:56:12', 'Rejected'),
(9, 8, 13, '2025-10-09 22:18:28', 'Accepted'),
(10, 9, 16, '2025-10-10 00:32:21', 'Accepted');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('donor','recipient') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(4, 'Abid', 'Hasan', 'abid_hasan', '$2y$10$LnuldxLpgmL8gy1jRekIJuTCiiLT2EIc4mHoX3RsGJPgKm8I8h3Ai', 'abid@gmail.com', 'donor', '2025-09-29 17:11:39'),
(5, 'Akif', 'Aslam', 'akif_aslam', '$2y$10$f4CdGmDn9w/ia1yJVV3T3e0ck4QNvBJfrvw.6dBCU8gHmY6HDOway', 'akif@gmail.com', 'recipient', '2025-09-30 06:10:26'),
(6, 'Sagor', 'Islam', 'sagor_islam', '$2y$10$atRWcLsbV05VEj9hHM5y9O7midTL8/HICmIMOb7YKdjQdZ93EFKBC', 'sagor@gmail.com', 'donor', '2025-09-30 06:41:40'),
(7, 'Alamin', 'Hossein', 'alamin_hossein', '$2y$10$G6jtnqLNY4T9/FJIa7ozVuFfVjPITGSdJLWwsCVUrx1jILM3uuVTu', 'alamin12@gmail.com', 'recipient', '2025-10-01 16:42:42'),
(8, 'Noman', 'Alam', 'noman', '$2y$10$80qyKx/SPu0ekNu8bXx9jODEapReprfJ9jd0lVx0rCjo8klsfTXsa', 'noman@gmail.com', 'donor', '2025-10-01 17:53:12'),
(9, 'Moien', 'Khan', 'moien_khan', '$2y$10$VWwmlfGod3bqv51lPIeXR.aporS65ZoV0GqkFh/IlBx8wXTrlrr3i', 'mo123@gmail.com', 'recipient', '2025-10-01 18:01:32'),
(10, 'Limon', 'Sheikh', 'limon_s', '$2y$10$POq7A7ml.Il4oTlghs5IZOs7eIUu49Ddwz0wURCbf3rfovudwySKm', 'limon@gmail.com', 'donor', '2025-10-02 15:42:25'),
(11, 'Atif', 'Aslam', 'atif_aslam', '$2y$10$iaULEm7kBOusbJo52u4fFuIRxCjLHHcyMboVzPPlwDGNW3JQcAS8O', 'atif@gmail.com', 'donor', '2025-10-02 18:24:20'),
(12, 'Naeem', 'Hasan', 'naeem_hasan', '$2y$10$r/5HmyRkzmwb2qzWjJTFqu/QNkRSISTX/ZwAKIRV8t3NoGqsDtUYe', 'naeem@gmail.com', 'donor', '2025-10-05 10:44:52'),
(13, 'Faisal', 'Kabir', 'fasial_kabir', '$2y$10$///l/WL..33gLtZh9F1DzOM78JD2f3FLmOkIe4aFsaFx4g9TTSR8O', '0-@gmail.com', 'donor', '2025-10-09 18:29:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blood_banks`
--
ALTER TABLE `blood_banks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blood_types`
--
ALTER TABLE `blood_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `sender_user_id` (`sender_user_id`),
  ADD KEY `receiver_user_id` (`receiver_user_id`);

--
-- Indexes for table `compatibility`
--
ALTER TABLE `compatibility`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_type_id` (`donor_type_id`),
  ADD KEY `recipient_type_id` (`recipient_type_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `blood_bank_id` (`blood_bank_id`);

--
-- Indexes for table `donors`
--
ALTER TABLE `donors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `donors_ibfk_2` (`blood_type_id`),
  ADD KEY `idx_location` (`location`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_id` (`bank_id`),
  ADD KEY `inventory_ibfk_2` (`blood_type_id`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donation_id` (`donation_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `recipients`
--
ALTER TABLE `recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `recipients_ibfk_2` (`blood_type_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `requests_ibfk_2` (`blood_type_id`);

--
-- Indexes for table `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_id` (`donor_id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blood_banks`
--
ALTER TABLE `blood_banks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blood_types`
--
ALTER TABLE `blood_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat`
--
ALTER TABLE `chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `compatibility`
--
ALTER TABLE `compatibility`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `donors`
--
ALTER TABLE `donors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recipients`
--
ALTER TABLE `recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat`
--
ALTER TABLE `chat`
  ADD CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_ibfk_3` FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `compatibility`
--
ALTER TABLE `compatibility`
  ADD CONSTRAINT `compat_ibfk_1` FOREIGN KEY (`donor_type_id`) REFERENCES `blood_types` (`id`),
  ADD CONSTRAINT `compat_ibfk_2` FOREIGN KEY (`recipient_type_id`) REFERENCES `blood_types` (`id`);

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`blood_bank_id`) REFERENCES `blood_banks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `donors`
--
ALTER TABLE `donors`
  ADD CONSTRAINT `donors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donors_ibfk_2` FOREIGN KEY (`blood_type_id`) REFERENCES `blood_types` (`id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `blood_banks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`blood_type_id`) REFERENCES `blood_types` (`id`);

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recipients`
--
ALTER TABLE `recipients`
  ADD CONSTRAINT `recipients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipients_ibfk_2` FOREIGN KEY (`blood_type_id`) REFERENCES `blood_types` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`blood_type_id`) REFERENCES `blood_types` (`id`);

--
-- Constraints for table `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`),
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

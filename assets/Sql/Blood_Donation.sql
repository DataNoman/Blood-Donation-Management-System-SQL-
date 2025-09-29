--
-- Database: `blood_donation_app`
--

-- This command will drop the existing database to start fresh.
DROP DATABASE IF EXISTS `blood_donation_app`;
CREATE DATABASE `blood_donation_app` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `blood_donation_app`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- The central table for storing login and personal information.
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('donor','recipient') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donors`
-- Profile information specific to donors, linked to a user account.
--
CREATE TABLE `donors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `age` int(11) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `last_donation_date` date DEFAULT NULL,
  `health_status` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `donors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipients`
-- Profile information specific to recipients, linked to a user account.
--
CREATE TABLE `recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `urgency_level` enum('Low','Medium','High') NOT NULL,
  `hospital` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `recipients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blood_banks`
--
CREATE TABLE `blood_banks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
-- Replaces the old JSON field for efficient stock management.
--
CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_id` int(11) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `quantity_ml` int(11) NOT NULL DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bank_id` (`bank_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`bank_id`) REFERENCES `blood_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` int(11) NOT NULL,
  `blood_bank_id` int(11) NOT NULL,
  `donation_date` date NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `status` enum('Pending','Completed','Canceled') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `donor_id` (`donor_id`),
  KEY `blood_bank_id` (`blood_bank_id`),
  CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`blood_bank_id`) REFERENCES `blood_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--
CREATE TABLE `requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) NOT NULL,
  `blood_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `status` enum('Open','Fulfilled','Canceled') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recipient_id` (`recipient_id`),
  CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matches`
-- Records which donation fulfilled which request.
--
CREATE TABLE `matches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donation_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `match_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `donation_id` (`donation_id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blood_types`
-- A simple lookup table for blood types.
--
CREATE TABLE `blood_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compatibility`
-- The "rulebook" for blood type compatibility, replacing the old JSON field.
--
CREATE TABLE `compatibility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  `recipient_type` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- --------------------------------------------------------
--                  DATA SEEDING SECTION
-- --------------------------------------------------------
--

-- Dumping data for table `users`
INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'Farid', 'Ahmed', 'farid_ahmed', 'hashed_password_1', 'farid.ahmed@example.com', 'donor', '2025-09-29 09:00:00'),
(2, 'Nasreen', 'Sultana', 'nasreen_sultana', 'hashed_password_2', 'nasreen.sultana@example.com', 'donor', '2025-09-29 09:00:00'),
(3, 'Kamal', 'Hossain', 'kamal_hossain', 'hashed_password_3', 'kamal.hossain@example.com', 'recipient', '2025-09-29 09:00:00');

-- Dumping data for table `donors`
INSERT INTO `donors` (`id`, `user_id`, `age`, `blood_type`, `gender`, `last_donation_date`, `health_status`) VALUES
(1, 1, 35, 'O+', 'Male', '2024-05-10', 'Good health'),
(2, 2, 28, 'A-', 'Female', '2023-11-20', 'Fit to donate');

-- Dumping data for table `recipients`
INSERT INTO `recipients` (`id`, `user_id`, `blood_type`, `urgency_level`, `hospital`) VALUES
(1, 3, 'A+', 'High', 'Dhaka Medical College Hospital');

-- Dumping data for table `blood_banks`
INSERT INTO `blood_banks` (`id`, `name`, `location`, `contact`) VALUES
(1, 'Quantum Blood Bank', 'Shantinagar, Dhaka', '01712345678'),
(2, 'Red Crescent Blood Bank', 'Mohammadpur, Dhaka', '01912345678');

-- Dumping data for table `inventory`
INSERT INTO `inventory` (`id`, `bank_id`, `blood_type`, `quantity_ml`) VALUES
(1, 1, 'A+', 5000), (2, 1, 'O-', 2500), (3, 1, 'B+', 4000),
(4, 2, 'A+', 3500), (5, 2, 'O+', 8000), (6, 2, 'AB-', 1000);

-- Dumping data for table `donations`
INSERT INTO `donations` (`id`, `donor_id`, `blood_bank_id`, `donation_date`, `quantity_ml`, `status`) VALUES
(1, 1, 1, '2024-05-10', 450, 'Completed'),
(2, 2, 2, '2024-06-25', 450, 'Completed');

-- Dumping data for table `requests`
INSERT INTO `requests` (`id`, `recipient_id`, `blood_type`, `quantity_ml`, `request_date`, `status`) VALUES
(1, 1, 'A+', 900, '2025-08-28', 'Open');

-- Dumping data for table `matches`
INSERT INTO `matches` (`id`, `donation_id`, `request_id`, `match_date`) VALUES
(1, 2, 1, '2025-08-29');

-- Dumping data for table `blood_types`
INSERT INTO `blood_types` (`id`, `type`) VALUES
(1, 'A+'), (2, 'A-'), (3, 'B+'), (4, 'B-'),
(5, 'AB+'), (6, 'AB-'), (7, 'O+'), (8, 'O-');

-- Dumping data for table `compatibility`
INSERT INTO `compatibility` (`donor_type`, `recipient_type`) VALUES
('A+', 'A+'), ('A+', 'AB+'),
('A-', 'A+'), ('A-', 'A-'), ('A-', 'AB+'), ('A-', 'AB-'),
('B+', 'B+'), ('B+', 'AB+'),
('B-', 'B+'), ('B-', 'B-'), ('B-', 'AB+'), ('B-', 'AB-'),
('AB+', 'AB+'),
('AB-', 'AB+'), ('AB-', 'AB-'),
('O+', 'O+'), ('O+', 'A+'), ('O+', 'B+'), ('O+', 'AB+'),
('O-', 'A+'), ('O-', 'A-'), ('O-', 'B+'), ('O-', 'B-'), ('O-', 'AB+'), ('O-', 'AB-'), ('O-', 'O+'), ('O-', 'O-');

COMMIT;
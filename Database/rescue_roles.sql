-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2025 at 08:13 PM
-- Server version: 10.11.14-MariaDB-cll-lve-log
-- PHP Version: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `newsomew_wp191`
--

-- --------------------------------------------------------

--
-- Table structure for table `rescue_roles`
--

CREATE TABLE `rescue_roles` (
  `role_id` int(8) NOT NULL,
  `role_name` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_roles`
--

INSERT INTO `rescue_roles` (`role_id`, `role_name`) VALUES
(1, 'Owner/Manager'),
(2, 'Volunteer'),
(3, 'Staff'),
(4, 'Vet'),
(5, 'Vet Nurse'),
(6, 'Administrator'),
(7, 'Driver');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rescue_roles`
--
ALTER TABLE `rescue_roles`
  ADD PRIMARY KEY (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rescue_roles`
--
ALTER TABLE `rescue_roles`
  MODIFY `role_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

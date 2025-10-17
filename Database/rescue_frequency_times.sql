-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2025 at 08:11 PM
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
-- Dumping data for table `rescue_frequency_times`
--

INSERT INTO `rescue_frequency_times` (`frequency_time_id`, `frequency_id`, `time`, `frequency_name`) VALUES
(1, 1, '08:00:00', 'Once per Day (am)'),
(2, 2, '08:00:00', 'Twice per Day'),
(3, 2, '20:00:00', 'Twice per Day'),
(4, 4, '08:00:00', 'Three times per Day'),
(5, 4, '14:00:00', 'Three times per Day'),
(6, 4, '20:00:00', 'Three times per Day'),
(7, 7, '08:00:00', 'Four times per Day'),
(8, 7, '12:00:00', 'Four times per Day'),
(9, 7, '16:00:00', 'Four times per Day'),
(10, 7, '20:00:00', 'Four times per Day'),
(11, 11, '20:00:00', 'Once per Day (pm)');

--
-- Indexes for dumped tables
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

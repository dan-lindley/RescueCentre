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
-- Table structure for table `rescue_frequencies`
--

CREATE TABLE `rescue_frequencies` (
  `frequency_id` int(8) NOT NULL,
  `frequency` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_frequencies`
--

INSERT INTO `rescue_frequencies` (`frequency_id`, `frequency`) VALUES
(1, 'Once per Day (am)'),
(2, 'Twice per Day'),
(4, 'Three times per Day'),
(7, 'Four times per Day'),
(11, 'Once per Day (pm)');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rescue_frequencies`
--
ALTER TABLE `rescue_frequencies`
  ADD PRIMARY KEY (`frequency_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rescue_frequencies`
--
ALTER TABLE `rescue_frequencies`
  MODIFY `frequency_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

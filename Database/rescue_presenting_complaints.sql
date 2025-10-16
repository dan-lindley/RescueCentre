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
-- Table structure for table `rescue_presenting_complaints`
--

CREATE TABLE `rescue_presenting_complaints` (
  `pc_id` int(8) NOT NULL,
  `prsenting_complaint` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rescue_presenting_complaints`
--

INSERT INTO `rescue_presenting_complaints` (`pc_id`, `prsenting_complaint`) VALUES
(1, 'Injured - Road Traffic'),
(2, 'Injured - Accident'),
(3, 'Attacked - Cat'),
(4, 'Attacked - Predator'),
(5, 'Injured - Fishing Line'),
(6, 'Injured - Glue/Roofing membrane'),
(7, 'Injured - Natural'),
(8, 'Injured - Unknown'),
(9, 'Poisoned'),
(10, 'Lost Baby/Juvenile'),
(11, 'Other Injury'),
(12, 'No apparent injury (flightless)'),
(13, 'No apparent injury (released)'),
(14, 'Out during the day'),
(15, 'Attacked - Dog'),
(16, 'Attacked - Badger'),
(17, 'Grounded or laying passively'),
(18, 'Injured - Garden Tools'),
(19, 'Heavy ectoparasite load'),
(20, 'Neurological symptoms'),
(21, 'Myiasis'),
(22, 'Captured for research');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rescue_presenting_complaints`
--
ALTER TABLE `rescue_presenting_complaints`
  ADD PRIMARY KEY (`pc_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rescue_presenting_complaints`
--
ALTER TABLE `rescue_presenting_complaints`
  MODIFY `pc_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

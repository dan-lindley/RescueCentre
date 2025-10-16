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
-- Table structure for table `rescue_labs_tests`
--

CREATE TABLE `rescue_labs_tests` (
  `l_test_id` int(8) NOT NULL,
  `lab_test` varchar(255) DEFAULT NULL,
  `lab_category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_labs_tests`
--

INSERT INTO `rescue_labs_tests` (`l_test_id`, `lab_test`, `lab_category`) VALUES
(1, 'Not Defined', 'Not Defined'),
(2, 'Gross Microscopy', 'Microbiology'),
(3, 'Glucose', 'Urinalysis'),
(4, 'Ketones', 'Urinalysis'),
(5, 'Leukocytes', 'Urinalysis'),
(6, 'Nitrites', 'Urinalysis'),
(7, 'Blood', 'Urinalysis'),
(8, 'Protein', 'Urinalysis'),
(9, 'pH', 'Urinalysis');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rescue_labs_tests`
--
ALTER TABLE `rescue_labs_tests`
  ADD PRIMARY KEY (`l_test_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rescue_labs_tests`
--
ALTER TABLE `rescue_labs_tests`
  MODIFY `l_test_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

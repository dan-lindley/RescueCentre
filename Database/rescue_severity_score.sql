-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2025 at 08:14 PM
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
-- Dumping data for table `rescue_severity_score`
--

INSERT INTO `rescue_severity_score` (`ss_id`, `ss_category`, `ss_description`, `ss_value`) VALUES
(1, 'Apparently Healthy', 'Animal has no obvious injuries', 1),
(2, 'Apparently Healthy', 'No or low number of fleas, lice, ticks etc', 1),
(3, 'Mildly unwell', 'Animal appears thin or underweight', 2),
(4, 'Obvious Injuries', 'Animal has some small wounds (less than 10% body surface)', 3),
(5, 'Obvious Injuries', 'Appears disorientated or unwell', 3),
(6, 'Severe Injuries', 'Animal has large deep wounds (e.g. bones visible or more than 10% body surface)', 4),
(7, 'Severe Injuries', 'Animal has broken/unusable limb', 4),
(8, 'Severe Injuries', 'Animal is extremely thin', 4),
(9, 'Severe Injuries', 'Animal looks severely unwell (breathing rapidly, reluctant to move)', 4),
(10, 'Near Death', 'Unable to use back or front legs', 5),
(11, 'Near Death', 'Doesn\'t respond to noise or light', 5),
(12, 'Near Death', 'Animal is gasping for breath', 5);

--
-- Indexes for dumped tables
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

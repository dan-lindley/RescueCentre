-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2025 at 08:12 PM
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

-- Dumping data for table `rescue_medications`
--

INSERT INTO `rescue_medications` (`medication_id`, `medication_name`, `class`, `common_name`, `description`, `contraindications`, `cautions`, `dose`, `side_effects`) VALUES
(5, 'Gentamicin', 'Antibiotic', 'Gentamicin', NULL, NULL, NULL, NULL, NULL),
(6, 'Clindamycin', 'Antibiotic', 'Clindamycin', NULL, NULL, NULL, NULL, NULL),
(7, 'Amoxicillin', 'Antibiotic', 'Synulox', NULL, NULL, NULL, NULL, NULL),
(8, 'Enrofloxacin', 'Antibiotic', 'Baytril', NULL, NULL, NULL, NULL, NULL),
(9, 'Cephalexin', 'Antibiotic', 'Cephalexin', NULL, NULL, NULL, NULL, NULL),
(10, 'Cefpodoxime', 'Antibiotic', 'Cefpodoxime', NULL, NULL, NULL, NULL, NULL),
(11, 'Cefazolin', 'Antibiotic', 'Cefazolin', NULL, NULL, NULL, NULL, NULL),
(12, 'Cefovecin', 'Antibiotic', 'Cefovecin', NULL, NULL, NULL, NULL, NULL),
(13, 'Trimethoprim', 'Antibiotic', 'Trimethoprim', NULL, NULL, NULL, NULL, NULL),
(14, 'Doxycycline', 'Antibiotic', 'Doxycycline', NULL, NULL, NULL, NULL, NULL),
(15, 'Metronidazole', 'Antibiotic', 'Metronidazole', NULL, NULL, NULL, NULL, NULL),
(16, 'Buprenorphine', 'Analgesia', 'Temgesic', NULL, NULL, NULL, NULL, NULL),
(17, 'Butorphanol', 'Analgesia', 'Torbugesic', NULL, NULL, NULL, NULL, NULL),
(18, 'Carprofen', 'Analgesia', 'Rimadyl', NULL, NULL, NULL, NULL, NULL),
(19, 'Meloxicam', 'Analgesia', 'Metacam (for dogs)', NULL, NULL, NULL, NULL, NULL),
(20, 'Fenbendazole', 'Anthelmentics', 'Panacur', NULL, NULL, NULL, NULL, NULL),
(21, 'Fenbendazole', 'Anthelmentics', 'Fenbendazole', NULL, NULL, NULL, NULL, NULL),
(22, 'Ivermectin', 'Anthelmentics', 'Ivermectin', NULL, NULL, NULL, NULL, NULL),
(23, 'Fipronil', 'Anthelmentics', 'Frontline', NULL, NULL, NULL, NULL, NULL),
(24, 'Permethrin', 'Anthelmentics', 'Permethrin', NULL, NULL, NULL, NULL, NULL),
(25, 'Itraconazole', 'Anthelmentics', 'Itraconazole', NULL, NULL, NULL, NULL, NULL),
(26, 'Excelpet', 'Anthelmentics', 'Excelpet', NULL, NULL, NULL, NULL, NULL),
(27, 'Carnidazole', 'Anthelmentics', 'Spartrix', NULL, NULL, NULL, NULL, NULL),
(28, 'Meloxicam', 'Analgesia', 'Metacam (for cats)', NULL, NULL, NULL, NULL, NULL),
(29, 'Marbofloxacin', 'Antibiotics', 'Marbocyl', '', '', '', '', '');

--
-- Indexes for dumped tables
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

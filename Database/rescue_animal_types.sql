-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2025 at 08:10 PM
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
-- Dumping data for table `rescue_animal_types`
--

INSERT INTO `rescue_animal_types` (`type_id`, `type_name`, `animal_order`) VALUES
(2, 'Badger', 'Mammal'),
(3, 'Beaver', 'Mammal'),
(4, 'Birds of Prey', 'Bird'),
(5, 'Bunting', 'Bird'),
(6, 'Corvid', 'Bird'),
(7, 'Crane', 'Bird'),
(8, 'Cuckoo', 'Bird'),
(9, 'Deer', 'Mammal'),
(10, 'Dipper', 'Bird'),
(11, 'Diver', 'Bird'),
(12, 'Dove', 'Bird'),
(13, 'Dunnock', 'Bird'),
(14, 'Egret', 'Bird'),
(15, 'Finch', 'Bird'),
(16, 'Flycatcher', 'Bird'),
(17, 'Fox', 'Mammal'),
(18, 'Frog', 'Amphibian'),
(19, 'Grebe', 'Bird'),
(20, 'Grouse', 'Bird'),
(21, 'Hare', 'Mammal'),
(22, 'Hedgehog', 'Mammal'),
(23, 'Heron', 'Bird'),
(24, 'Kingfisher', 'Bird'),
(25, 'Lark', 'Bird'),
(26, 'Lizard', 'Reptile'),
(27, 'Marine Mammal', 'Mammal'),
(28, 'Marine Reptile', 'Reptile'),
(29, 'Marine Fish', 'Fish'),
(30, 'Marten', 'Mammal'),
(31, 'Martin', 'Bird'),
(32, 'Mink', 'Mammal'),
(33, 'Mole', 'Mammal'),
(34, 'Mouse', 'Mammal'),
(35, 'Newt', 'Amphibian'),
(36, 'Nightjar', 'Bird'),
(37, 'Otter', 'Mammal'),
(38, 'Parakeet', 'Bird'),
(39, 'Partridge', 'Bird'),
(40, 'Pheasant', 'Bird'),
(41, 'Pigeon', 'Bird'),
(42, 'Polecat', 'Mammal'),
(43, 'Quail', 'Bird'),
(44, 'Rabbit', 'Mammal'),
(45, 'Rat', 'Mammal'),
(46, 'Seabird', 'Bird'),
(47, 'Shrew', 'Mammal'),
(48, 'Shrike', 'Bird'),
(49, 'Snake', 'Reptile'),
(50, 'Sparrow', 'Bird'),
(51, 'Spoonbill', 'Bird'),
(52, 'Squirrel', 'Mammal'),
(53, 'Starling', 'Bird'),
(54, 'Stoat', 'Mammal'),
(55, 'Swallow', 'Bird'),
(56, 'Swift', 'Bird'),
(57, 'Thrush', 'Bird'),
(58, 'Tit', 'Bird'),
(59, 'Toad', 'Amphibian'),
(60, 'Unknown', 'Unknown'),
(61, 'Vole', 'Mammal'),
(62, 'Wading Birds', 'Bird'),
(63, 'Wagtail', 'Bird'),
(64, 'Warbler', 'Bird'),
(65, 'Waterfowl', 'Bird'),
(66, 'Waxwing', 'Bird'),
(67, 'Weasel', 'Mammal'),
(68, 'Wildcat', 'Mammal'),
(69, 'Woodpecker', 'Bird'),
(70, 'Wren', 'Bird'),
(99, 'Bat', 'Mammal'),
(100, 'Dormouse', 'Mammal');

--
-- Indexes for dumped tables
--

--
-- AUTO_INCREMENT for dumped tables
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

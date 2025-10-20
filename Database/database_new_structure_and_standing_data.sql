-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 17, 2025 at 02:09 PM
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
-- Database: `newsomew_wp927`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'Member',
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `activation_code` varchar(255) DEFAULT NULL,
  `remember_me_code` varchar(255) DEFAULT NULL,
  `reset_code` varchar(255) DEFAULT NULL,
  `last_seen` datetime NOT NULL,
  `registered` datetime NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `rescue_role` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lat_search`
--

CREATE TABLE `lat_search` (
  `centre_id` int(8) NOT NULL,
  `query` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `network_cons`
--

CREATE TABLE `network_cons` (
  `net_con_id` int(8) NOT NULL,
  `centre_id` int(8) NOT NULL DEFAULT 0,
  `network_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `outcodepostcodes`
--

CREATE TABLE `outcodepostcodes` (
  `id` int(11) NOT NULL,
  `outcode` varchar(8) NOT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `postcodelatlng`
--

CREATE TABLE `postcodelatlng` (
  `id` int(11) NOT NULL,
  `postcode` varchar(8) NOT NULL,
  `country_code` varchar(4) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_admissions`
--

CREATE TABLE `rescue_admissions` (
  `admission_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `admission_date` datetime NOT NULL,
  `age_on_admission` varchar(255) NOT NULL,
  `presenting_complaint` varchar(255) NOT NULL,
  `dehydrated` varchar(255) NOT NULL,
  `starved` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `collection_location` varchar(255) NOT NULL,
  `finder_id` int(11) NOT NULL,
  `finder_name` varchar(255) NOT NULL,
  `disposition` varchar(255) NOT NULL,
  `weight` int(8) NOT NULL,
  `weight_unit` varchar(255) NOT NULL,
  `measurement` int(8) NOT NULL,
  `measurement_unit` varchar(255) NOT NULL,
  `centre_id` int(8) NOT NULL,
  `staff_wp_id` int(8) NOT NULL,
  `time_to_admission` varchar(20) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `owner_id` int(8) DEFAULT NULL,
  `transfer_id` int(8) DEFAULT 0,
  `survived` int(1) DEFAULT 0,
  `w_temp` int(8) DEFAULT 0,
  `w_wind` int(8) DEFAULT 0,
  `w_humidity` int(4) DEFAULT 0,
  `w_freetext` varchar(255) DEFAULT NULL,
  `severity_score` int(2) DEFAULT 99,
  `finder_tel` varchar(15) DEFAULT NULL,
  `disposition_date` datetime DEFAULT NULL,
  `consent_to_update` int(1) DEFAULT 0,
  `disposition_user` int(8) DEFAULT NULL,
  `disposition_centre` int(8) DEFAULT NULL,
  `disposition_comment` longtext DEFAULT NULL,
  `euthanasia_method` varchar(255) DEFAULT NULL,
  `hpc` longtext DEFAULT NULL,
  `on_examination` longtext DEFAULT NULL,
  `ss_text` varchar(255) DEFAULT NULL,
  `bc_score` int(2) DEFAULT 99,
  `bcs_text` varchar(255) DEFAULT NULL,
  `species_score` int(2) DEFAULT 99,
  `age_score` int(2) DEFAULT 99,
  `location_lat` varchar(18) DEFAULT NULL,
  `location_long` varchar(18) DEFAULT NULL,
  `passphrase` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_alerts`
--

CREATE TABLE `rescue_alerts` (
  `alert_id` int(8) NOT NULL,
  `alert_message` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `alert_type` varchar(255) DEFAULT NULL,
  `is_closed` varchar(255) DEFAULT NULL,
  `centre_id` int(8) DEFAULT 0,
  `patient_id` int(8) DEFAULT 0,
  `is_deleted` int(1) DEFAULT 0,
  `is_active` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_animal_orders`
--

CREATE TABLE `rescue_animal_orders` (
  `order_id` int(8) NOT NULL,
  `order_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rescue_animal_orders`
--

INSERT INTO `rescue_animal_orders` (`order_id`, `order_name`) VALUES
(1, 'Mammal'),
(2, 'Amphibian'),
(3, 'Bird'),
(4, 'Fish'),
(5, 'Reptile'),
(6, 'Unknown');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_animal_species`
--

CREATE TABLE `rescue_animal_species` (
  `species_id` int(8) NOT NULL,
  `species_name` varchar(255) NOT NULL,
  `scientific_name` varchar(255) NOT NULL,
  `animal_type` varchar(255) NOT NULL,
  `species_weight_from` decimal(8,2) NOT NULL,
  `species_weight_to` decimal(8,2) NOT NULL,
  `species_weight_unit` text NOT NULL,
  `species_measurement_from` decimal(8,2) NOT NULL,
  `species_measurement_to` decimal(8,2) NOT NULL,
  `species_measurement_unit` text NOT NULL,
  `reference` text NOT NULL,
  `species_measurement_standard` text DEFAULT NULL,
  `iucn_status` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rescue_animal_species`
--

INSERT INTO `rescue_animal_species` (`species_id`, `species_name`, `scientific_name`, `animal_type`, `species_weight_from`, `species_weight_to`, `species_weight_unit`, `species_measurement_from`, `species_measurement_to`, `species_measurement_unit`, `reference`, `species_measurement_standard`, `iucn_status`) VALUES
(1, 'Unknown Animal', 'ignotus ignotus', 'Unknown', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(2, 'Bat (Unknown)', '(unknown)', 'Bat', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(3, 'Common Pipistrelle', 'pipistrellus pipistrellus', 'Bat', 3.50, 8.50, 'g', 28.00, 34.50, 'mm', 'Maggie Brown / BCT', 'forearm', NULL),
(4, 'Marsh Frog', 'pelophylax ridibundus', 'Frog', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(5, 'Great Crested Newt', 'triturus cristatus', 'Newt', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(6, 'Smooth Newt', 'lissotriton vulgaris', 'Newt', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(7, 'Palmate Newt', 'lissotriton helveticus', 'Newt', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(8, 'Common Toad', 'bufo bufo', 'Toad', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(9, 'Natterjack Toad', 'epidalea calamita', 'Toad', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(10, 'Common Frog', 'rana temporaria', 'Frog', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(11, 'Chinese Water Deer', 'hydropotes inermis', 'Deer', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(12, 'European Hedgehog', 'erinaceus europaeus', 'Hedgehog', 500.00, 600.00, 'g', 20.00, 30.00, 'cm', 'British Hedgehogs', 'Snout to tail base', NULL),
(13, 'European Otter', 'lutra lutra', 'Otter', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(14, 'Wood Mouse', 'apodemus sylvaticus', 'Mouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(15, 'Common Shrew', 'sorex araneus', 'Shrew', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(16, 'American Mink', 'neovison vison', 'Mink', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(17, 'Brown Rat', 'rattus norvegicus', 'Rat', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(18, 'Pygmy Shrew', 'sorex minutus', 'Shrew', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(19, 'Stoat', 'mustela erminea', 'Stoat', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(20, 'Water Vole', 'arvicola amphibius', 'Vole', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(21, 'Water Shrew', 'neomys fodiens', 'Shrew', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(22, 'Weasel', 'mustela nivalis', 'Weasel', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(23, 'Bank Vole', 'myodes glareolus', 'Vole', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(24, 'Mole', 'talpa europaea', 'Mole', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(25, 'Wildcat', 'felis silvestris', 'Wildcat', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(26, 'Field Vole', 'microtus agrestis', 'Vole', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(27, 'Pine Marten', 'martes martes', 'Marten', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(28, 'Brown Hare', 'lepus europaeus', 'Hare', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(29, 'Noctule', 'nyctalus noctula', 'Bat', 19.00, 40.00, 'g', 47.00, 58.00, 'mm', 'Maggie Brown / BCT', 'forearm', NULL),
(30, 'Polecat', 'mustela putorius', 'Polecat', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(31, 'Mountain Hare', 'lepus timus', 'Hare', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(32, 'Brown Long-eared', 'plecotus auritus', 'Bat', 5.00, 12.00, 'g', 34.00, 42.00, 'mm', 'Maggie Brown / BCT', 'forearm', NULL),
(33, 'Roe Deer', 'capreolus capreolus', 'Deer', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(34, 'Rabbit', 'oryctolagus cuniculus', 'Rabbit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(35, 'Brandt\'s', 'myotis brandtii', 'Bat', 4.30, 9.50, 'g', 31.00, 38.90, 'mm', 'Maggie Brown / BCT', 'forearm', NULL),
(36, 'Muntjac Deer', 'muntiacus reevesi', 'Deer', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(37, 'Beaver', 'castor fiber', 'Beaver', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(38, 'Daubentons', 'myotis daubentonii', 'Bat', 7.00, 15.00, 'g', 35.00, 41.70, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(39, 'Red Deer', 'cervus elaphus', 'Deer', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(40, 'Barbastelle', 'barbastella barbastellus', 'Bat', 5.00, 13.50, 'g', 36.00, 44.00, 'mm', 'Maggie Brown / BCT', 'forearm', NULL),
(41, 'Leislers', 'nyctalus leisleri', 'Bat', 11.00, 20.00, 'g', 38.00, 47.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(42, 'Fallow Deer', 'dama dama', 'Deer', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(43, 'Bechstein\'s', 'myotis bechsteinii', 'Bat', 7.00, 14.00, 'g', 38.00, 47.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(44, 'Natterer\'s', 'myotis nattereri', 'Bat', 5.00, 12.00, 'g', 36.00, 43.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(45, 'Grey Squirrel', 'sciurus carolinensis', 'Squirrel', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(46, 'Greater Horseshoe', 'rhinolophus ferrumequinum', 'Bat', 17.00, 34.00, 'g', 54.00, 61.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(47, 'Lesser Horseshoe', 'rhinolophus hipposideros', 'Bat', 5.00, 9.00, 'g', 37.00, 42.50, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(48, 'Serotine', 'eptesicus serotinus', 'Bat', 14.00, 33.00, 'g', 48.00, 57.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(49, 'Red Squirrel', 'sciurus vulgaris', 'Squirrel', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(50, 'Whiskered', 'myotis mystacinus', 'Bat', 4.00, 8.00, 'g', 32.00, 36.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(51, 'Hazel Dormouse', 'muscardinus avellanarius', 'Dormouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(52, 'Grey long-eared', 'plecotus austriacus', 'Bat', 7.00, 14.00, 'g', 37.00, 45.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(53, 'Red Fox', 'vulpes vulpes', 'Fox', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(54, 'Harvest Mouse', 'micromys minutus', 'Mouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(55, 'Alcathoe', 'myotis alcathoe', 'Bat', 3.50, 5.50, 'g', 30.80, 34.60, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(56, 'European Badger', 'meles meles', 'Badger', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(57, 'House Mouse', 'mus musculus', 'Mouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(58, 'Amphibian (unknown)', '', 'Amphibian', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(59, 'Mouse (unknown)', '', 'Mouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(60, 'Common Lizard', 'zootoca vivipara', 'Lizard', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(61, 'Sand Lizard', 'lacetra agilis', 'Lizard', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(62, 'Slow Worm', 'anguis fragilis', 'Lizard', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(63, 'Grass Snake', 'natrix helvetica', 'Snake', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(64, 'Adder', 'vipera berus', 'Snake', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(65, 'Smooth Snake', 'coronella austriaca', 'Snake', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(66, 'Red-necked Grebe', 'podiceps grisegena', 'Grebe', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(67, 'Great Northern Diver', 'gavia immer', 'Diver', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(68, 'Little Grebe', 'tachybaptus ruficollis', 'Grebe', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(69, 'Great Crested Grebe', 'podiceps cristatus', 'Grebe', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(70, 'Black-necked Grebe', 'podiceps nigricollis', 'Grebe', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(71, 'Slavonian Grebe', 'podiceps auritus', 'Grebe', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(72, 'Black-throated Diver', 'gavia arctica', 'Diver', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(73, 'Red-throated Diver', 'gavia stellata', 'Diver', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(74, 'Mediterranean Gull', 'larus melanocephalus', 'Seabird', 260.00, 382.00, 'g', 294.00, 322.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/mediterranean-gull', 'maximum flattened chord', 'Least Concern'),
(75, 'Sooty Shearwater', 'ardenna grisea', 'Seabird', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, 'Near threatened'),
(76, 'Storm Petrel', 'hydrobates pelagicus', 'Seabird', 22.50, 29.10, 'g', 118.00, 127.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/storm-petrel', 'maximum flattened chord', 'Least Concern'),
(77, 'Roseate Tern', 'sterna dougallii', 'Seabird', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, 'Least Concern'),
(78, 'Arctic Tern', 'sterna paradisaea', 'Seabird', 90.50, 119.00, 'g', 262.00, 286.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/arctic-tern', 'maximum flattened chord', 'Least Concern'),
(79, 'Common Tern', 'sterna hirundo', 'Seabird', 113.00, 144.00, 'g', 260.00, 281.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/common-tern', 'maximum flattened chord', 'Least Concern'),
(80, 'Sandwich Tern', 'sterna sandvicensis', 'Seabird', 241.00, 261.00, 'g', 295.00, 316.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/sandwich-tern', 'maximum flattened chord', 'Least Concern'),
(81, 'Little Tern', 'sternula albifrons', 'Seabird', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, 'Least Concern'),
(82, 'Kittiwake', 'rissa tridactyla', 'Seabird', 310.00, 434.00, 'g', 295.00, 322.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/kittiwake', 'maximum flattened chord', 'Vulnerable'),
(83, 'Great Black-backed Gull', 'larus marinus', 'Seabird', 1.29, 1.92, 'kg', 438.00, 513.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/great-black-backed-gull', 'maximum flattened chord', 'Least Concern'),
(84, 'Lesser Black-backed Gull', 'larus fuscus', 'Seabird', 686.00, 999.00, 'g', 384.00, 438.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/lesser-black-backed-gull', 'maximum flattened chord', 'Least Concern'),
(85, 'Herring Gull', 'larus argentatus', 'Seabird', 757.00, 1260.00, 'g', 385.00, 448.00, 'mm', 'British Trust for Ornithology - ', 'maximum flattened chord', 'Least Concern'),
(86, 'Common Gull', 'larus canus', 'Seabird', 328.00, 497.00, 'g', 336.00, 380.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/common-gull', 'maximum flattened chord', 'Least Concern'),
(87, 'Black-headed Gull', 'chroicocephalus ridibundus', 'Seabird', 240.00, 348.00, 'g', 287.00, 323.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/black-headed-gull', 'maximum flattened chord', 'Least Concern'),
(88, 'Arctic Skua', 'stercorarius parasiticus', 'Seabird', 360.00, 476.00, 'g', 314.00, 343.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/arctic-skua', 'maximum flattened chord', 'Least Concern'),
(89, 'Great Skua', 'stercorarius skua', 'Seabird', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/great-skua', 'maximum flattened chord', 'Least Concern'),
(90, 'Razorbill', 'alca torda', 'Seabird', 525.00, 705.00, 'g', 189.00, 205.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/razorbill', 'maximum flattened chord', 'Near Threatened'),
(91, 'Guillemot', 'uria aalge', 'Seabird', 770.00, 1010.00, 'g', 194.00, 211.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/guillemot', 'maximum flattened chord', 'Least Concern'),
(92, 'Black Guillemot', 'cepphus grylle', 'Seabird', 360.00, 480.00, 'g', 157.00, 174.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/black-guillemot', 'maximum flattened chord', 'Least Concern'),
(93, 'Puffin', 'fratercula arctica', 'Seabird', 325.00, 450.00, 'g', 152.00, 167.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/puffin', 'maximum flattened chord', 'Vulnerable'),
(94, 'Shag', 'gulosos aristotelis', 'Seabird', 1.54, 2.10, 'kg', 255.00, 280.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/shag', 'maximum flattened chord', 'Least Concern'),
(95, 'Cormorant', 'phalacrocorax carbo', 'Seabird', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/cormorant', 'maximum flattened chord', 'Least Concern'),
(96, 'Gannet', 'morus bassanus', 'Seabird', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/gannet', 'maximum flattened chord', 'Least Concern'),
(97, 'Manx Shearwater', 'puffinus puffinus', 'Seabird', 330.00, 455.00, 'g', 232.00, 248.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/manx-shearwater', 'maximum flattened chord', 'Least Concern'),
(98, 'Fulmar', 'fulmarus glacialis', 'Seabird', 595.00, 970.00, 'g', 312.00, 350.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/fulmar', 'maximum flattened chord', 'Least Concern'),
(99, 'Seabird (unknown)', '', 'Seabird', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(100, 'Long-tailed Duck', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(101, 'Egyptian Goose', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(102, 'Smew', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(103, 'Garganey', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(104, 'Eider', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(105, 'Common Scoter', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(106, 'Red-breasted Merganser', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(107, 'Goosander', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(108, 'Goldeneye', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(109, 'Tufted Duck', '', 'Watwrfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(110, 'Pochard', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(111, 'Teal', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(112, 'Wigeon', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(113, 'Shoveler', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(114, 'Pintail', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(115, 'Gadwall', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(116, 'Mallard', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(117, 'Mandarin Duck', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(118, 'Shelduck', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(119, 'Brent Goose', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(120, 'Barnacle Goose', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(121, 'Canada Goose', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(122, 'Pink-footed Goose', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(123, 'White-fronted Goose', '', 'Watwrfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(124, 'Greylag Goose', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(125, 'Whooper Swan', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(126, 'Bewick\'s Swan', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(127, 'Mute Swan', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(128, 'Glossy Ibis', '', 'Heron', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(129, 'Cattle Egret', '', 'Egret', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(130, 'Common Crane', '', 'Crane', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(131, 'Great White Egret', '', 'Egret', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(132, 'European Spoonbill', '', 'Spoonbill', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(133, 'Little Egret', '', 'Egret', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(134, 'Grey Heron', '', 'Heron', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(135, 'Bittern', '', 'Heron', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(136, 'Honey Buzzard', 'pernis apivorus', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/honey-buzzard', 'maximum flattened chord', 'Least Concern'),
(137, 'Goshawk', 'accipiter gentilis', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/goshawk', 'maximum flattened chord', 'Least Concern'),
(138, 'Barn Owl', 'tyto alba', 'Birds of Prey', 280.00, 420.00, '', 280.00, 304.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/barn-owl', 'maximum flattened chord', 'Least Concern'),
(139, 'Little Owl', 'athene noctua', 'Birds of Prey', 153.00, 224.00, 'g', 158.00, 173.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/little-owl', 'maximum flattened chord', 'Least Concern'),
(140, 'Long-eared Owl', 'asio otus', 'Birds of Prey', 287.00, 312.00, 'mm', 215.00, 347.00, 'g', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/long-eared-owl', 'maximum flattened chord', 'Least Concern'),
(141, 'Short-eared Owl', 'asio flammeus', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/short-eared-owl', 'maximum flattened chord', 'Least Concern'),
(142, 'Tawny Owl', 'strix aluco', 'Birds of Prey', 365.00, 587.00, 'g', 255.00, 281.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/tawny-owl', 'maximum flattened chord', 'Least Concern'),
(143, 'Merlin', 'falco columbarius', 'Birds of Prey', 148.00, 343.00, 'g', 198.00, 236.50, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/merlin', 'maximum flattened chord', 'Least Concern'),
(144, 'Peregrine', 'falco peregrinus', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/peregrine', 'maximum flattened chord', 'Least Concern'),
(145, 'Hobby', 'falco subbuteo', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/hobby', 'maximum flattened chord', 'Least Concern'),
(146, 'Kestrel', 'falco tinnunculus', 'Birds of Prey', 160.00, 273.00, 'g', 233.00, 262.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/kestrel', 'maximum flattened chord', 'Least Concern'),
(147, 'Sparrowhawk', 'accipiter nisus', 'Birds of Prey', 133.00, 297.00, 'g', 191.00, 240.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/sparrowhawk', 'maximum flattened chord', 'Least Concern'),
(148, 'Buzzard', 'buteo buteo', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/buzzard', 'maximum flattened chord', 'Least Concern'),
(149, 'Hen Harrier', 'circus cyaneus', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for  Ornithology - https://www.bto.org/understanding-birds/birdfacts/hen-harrier', 'maximum flattened chord', 'Least Concern'),
(150, 'Marsh Harrier', 'circus aeruginosu', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/marsh-harrier', 'maximum flattened chord', 'Least Concern'),
(151, 'Osprey', 'pandion haliaetus', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/osprey', 'maximum flattened chord', 'Least Concern'),
(152, 'Red Kite', 'milvus milvus', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'https://www.bto.org/understanding-birds/birdfacts/red-kite', 'maximum flattened chord', 'Near Threatened'),
(153, 'Golden Eagle', 'aquila chrysaetos', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/golden-eagle', 'maximum flattened chord', 'Least Concern'),
(154, 'White-tailed Eagle', 'haliaeetus albicilla', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/white-tailed-eagle', 'maximum flattened chord', 'Least Concern'),
(155, 'Quail', '', 'Quail', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(156, 'Capercaillie', '', 'Grouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(157, 'Ptarmigan', '', 'Grouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(158, 'Pheasant', '', 'Pheasant', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(159, 'Grey Partridge', '', 'Partridge', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(160, 'Red-legged Partridge', '', 'Partridge', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(161, 'Black Grouse', '', 'Grouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(162, 'Red Grouse', '', 'Grouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(163, 'Waterfowl (unknown)', '', 'Waterfowl', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(164, 'Bird of Prey (unknown)', '', 'Birds of Prey', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(165, 'Stone Curlew', 'burhinus oedicnemus', 'Wading Birds', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/stone-curlew', 'maximum flattened chord', 'Least Concern'),
(166, 'Black-winged Stilt', 'himantopus himantopus', 'Wading Birds', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/black-winged-stilt', 'maximum flattened chord', 'Least Concern'),
(167, 'Jack Snipe', 'lymnocryptes minimus', 'Wading Birds', 47.00, 73.00, 'g', 110.00, 121.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/jack-snipe', 'maximum flattened chord', 'Least Concern'),
(168, 'Purple Sandpiper', 'calidris maritima', 'Wading Birds', 57.00, 86.00, 'g', 125.00, 138.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/purple-sandpiper', 'maximum flattened chord', 'Least Concern'),
(169, 'Corncrake', 'crex crex', 'Wading Birds', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/corncrake', 'maximum flattened chord', 'Least Concern'),
(170, 'Red-necked Phalarope', 'phalaropus lobatus', 'Wading Birds', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/red-necked-phalarope', 'maximum flattened chord', 'Least Concern'),
(171, 'Snipe', 'gallinago gallinago', 'Wading Birds', 91.00, 129.00, 'g', 129.00, 142.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/snipe', 'maximum flattened chord', 'Least Concern'),
(172, 'Woodcock', 'scolopax rusticola', 'Wading Birds', 220.00, 377.00, 'g', 190.00, 210.00, 'mm', 'British Trust for Ornithology -  https://www.bto.org/understanding-birds/birdfacts/woodcock', 'maximum flattened chord', 'Least Concern'),
(173, 'Whimbrel', 'numenius phaeopus', 'Wading Birds', 230.50, 255.00, 'g', 241.00, 268.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/whimbrel', 'maximum flattened chord', 'Least Concern'),
(174, 'Curlew', 'numenius arquata', 'Wading Birds', 660.00, 1000.00, 'g', 289.00, 324.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/curlew', 'maximum flattened chord ', 'Near Threatened'),
(175, 'Bar-tailed Godwit', 'limosa lapponica', 'Wading Birds', 244.00, 360.00, 'g', 205.00, 235.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/bar-tailed-godwit', 'maximum flattened chord ', 'Near Threatened'),
(176, 'Greenshank', 'tringa nebularia', 'Wading Birds', 150.00, 241.00, 'g', 183.00, 202.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/greenshank', 'maximum flattened chord ', 'Least Concern'),
(177, 'Redshank', 'tringa totanus', 'Wading Birds', 126.00, 184.00, 'g', 161.00, 178.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/redshank', 'maximum flattened chord ', 'Least Concern'),
(178, 'Ruff', 'calidris pugnax', 'Wading Birds', 85.00, 215.00, 'g', 152.00, 194.00, 'mm', '**** JUVENILE ONLY DATA ****\r\nBritish Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/ruff', 'maximum flattened chord', 'Least Concern'),
(179, 'Common Sandpiper', 'actitis hypoleucos', 'Wading Birds', 44.00, 76.00, 'g', 107.00, 117.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/common-sandpiper', 'maximum flattened chord', 'Least Concern'),
(180, 'Green Sandpiper', 'tringa ochropus', 'Wading Birds', 70.00, 110.00, 'g', 135.00, 152.00, 'mm', 'British Trust for Ornithology -  https://www.bto.org/understanding-birds/birdfacts/green-sandpiper', 'maximum flattened chord', 'Least Concern'),
(181, 'Dunlin', 'calidris alpina', 'Wading Birds', 42.00, 58.00, 'g', 113.00, 125.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/dunlin', 'maximum flattened chord', 'Least Concern'),
(182, 'Sanderling', 'calidris alba', 'Wading Birds', 46.00, 73.00, 'g', 121.00, 133.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/sanderling', 'maximum flattened chord', 'Least Concern'),
(183, 'Knot', 'calidris canutus', 'Wading Birds', 120.00, 158.00, 'g', 159.00, 179.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/knot', 'maximum flattened chord', 'Near Threatened'),
(184, 'Turnstone', 'arenaria interpres', 'Wading Birds', 94.00, 125.00, 'g', 149.00, 164.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/turnstone', 'maximum flattened chord', 'Least Concern'),
(185, 'Grey Plover', 'pluvialis squatarola', 'Wading Birds', 200.00, 290.00, 'g', 188.00, 209.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/grey-plover', 'maximum flattened chord ', 'Least Concern'),
(186, 'Golden Plover', 'pluvialis apricaria', 'Wading Birds', 180.00, 236.00, 'g', 186.00, 202.00, 'mm', 'British Trust for Ornithology -  https://www.bto.org/understanding-birds/birdfacts/golden-plover', 'maximum flattened chord', 'Least Concern'),
(187, 'Lapwing', 'vanellus vanellus', 'Wading Birds', 214.00, 295.00, 'g', 221.00, 241.00, 'mm', 'British Trust for Ornithology -  https://www.bto.org/understanding-birds/birdfacts/lapwing', 'maximum flattened chord', 'Near Threatened'),
(188, 'Ringed Plover', 'charadrius hiaticula', 'Wading Birds', 54.00, 82.00, 'g', 129.00, 142.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/ringed-plover', 'maximum flattened chord', 'Least Concern'),
(189, 'Little Ringed Plover', 'charadrius dubius', 'Wading Birds', 34.00, 42.60, 'g', 114.00, 124.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/little-ringed-plover', 'maximum flattened chord', 'Least Concern'),
(190, 'Avocet', 'recurvirostra avosetta', 'Wading Birds', 0.00, 0.00, '', 0.00, 0.00, '', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/avocet', 'maximum flattened chord ', 'Least Concern'),
(191, 'Oystercatcher', 'haematopus ostralegus', 'Wading Birds', 465.00, 640.00, 'g', 251.00, 275.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/oystercatcher', 'maximum flattened chord', 'Near Threatened'),
(192, 'Coot', 'fulica atra', 'Wading Birds', 630.00, 1210.00, 'g', 201.00, 231.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/coot', 'maximum flattened chord', 'Least Concern'),
(193, 'Moorhen', 'gallinula chloropus', 'Wading Birds', 243.00, 460.00, 'g', 170.00, 195.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/moorhen', 'maximum flattened chord', 'Least Concern'),
(194, 'Water Rail', 'rallus aquaticus', 'Wading Birds', 93.00, 164.00, 'g', 112.00, 130.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/water-rail', 'maximum flattened chord', 'Least Concern'),
(195, 'Wading Bird (unknown)', '', 'Wading Birds', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(196, 'Collared Dove', '', 'Dove', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(197, 'Turtle Dove', '', 'Dove', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(198, 'Stock Dove', '', 'Dove', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(199, 'Woodpigeon', '', 'Pigeon', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(200, 'Rock Dove', '', 'Dove', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(201, 'Feral Pigeon', '', 'Pigeon', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(202, 'Wryneck', '', 'Woodpecker', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(203, 'Waxwing', '', 'Waxwing', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(204, 'Kingfisher', '', 'Kingfisher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(205, 'Cuckoo', '', 'Cuckoo', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(206, 'Lesser Spotted Woodpecker', '', 'Woodpecker', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(207, 'Green Woodpecker', '', 'Woodpecker', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(208, 'Great Spotted Woodpecker', '', 'Woodpecker', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(209, 'Nightjar', '', 'Nightjar', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(210, 'Swallow', '', 'Swallow', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(211, 'House Martin', '', 'Martin', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(212, 'Sand Martin', '', 'Martin', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(213, 'Swift', '', 'Swift', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(214, 'Ring-necked Parakeet', '', 'Parakeet', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(215, 'Tree Pipit', '', 'Wagtail', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(216, 'Shore Lark', '', 'Lark', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(217, 'Tree Sparrow', '', 'Sparrow', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(218, 'House Sparrow', '', 'Sparrow', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(219, 'Dunnock', '', 'Dunnock', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(220, 'Grey Wagtail', '', 'Wagtail', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(221, 'Yellow Wagtail', '', 'Wagtail', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(222, 'Pied Wagtail', '', 'Wagtail', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(223, 'Meadow Pipit', '', 'Wagtail', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(224, 'Rock Pipit', '', 'Wagtail', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(225, 'Woodlark', '', 'Lark', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(226, 'Skylark', '', 'Lark', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(227, 'Black Redstart', '', 'Thrush', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(228, 'Ring Ouzel', '', 'Thrush', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(229, 'Treecreeper', '', 'Wren', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(230, 'Nuthatch', '', 'Wren', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(231, 'Wren', '', 'Wren', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(232, 'Dipper', '', 'Dipper', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(233, 'Starling', '', 'Starling', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(234, 'Spotted Flycatcher', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(235, 'Pied Flycatcher', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(236, 'Wheatear', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(237, 'Whinchat', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(238, 'Stonechat', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(239, 'Redstart', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(240, 'Nightingale', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(241, 'Robin', '', 'Flycatcher', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(242, 'Blackbird', 'turdus merula', 'Thrush', 87.00, 122.00, 'g', 124.00, 138.00, 'mm', 'British Trust for Ornithology - https://www.bto.org/understanding-birds/birdfacts/blackbird', 'maximum flattened chord', 'Least concern'),
(243, 'Fieldfare', '', 'Thrush', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(244, 'Redwing', '', 'Thrush', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(245, 'Mistle Thrush', '', 'Thrush', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(246, 'Song Thrush', '', 'Thrush', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(247, 'Grasshopper Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(248, 'Firecrest', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(249, 'Dartford Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(250, 'Chiffchaff', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(251, 'Willow Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(252, 'Wood Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(253, 'Cetti\'s Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(254, 'Reed Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(255, 'Sedge Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(256, 'Whitethroat', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(257, 'Lesser Whitethroat', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(258, 'Garden Warbler', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(259, 'Blackcap', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(260, 'Goldcrest', '', 'Warbler', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(261, 'Marsh Tit', '', 'Tit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(262, 'Willow Tit', '', 'Tit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(263, 'Long-tailed Tit', '', 'Tit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(264, 'Coal Tit', '', 'Tit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(265, 'Bearded Tit', '', 'Tit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(266, 'Blue Tit', '', 'Tit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(267, 'Great Tit', '', 'Tit', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(268, 'Grey Grey Shrike', '', 'Shrike', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(270, 'Hooded Crow', 'corvus cornix', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(272, 'Rook', '', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(273, 'Jackdaw', '', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(274, 'Chough', '', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(275, 'Jay', '', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(276, 'Magpie', '', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(277, 'Twite', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(278, 'Brambling', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(279, 'Hawfinch', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(280, 'Snow Bunting', '', 'Bunting', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(281, 'Corn Bunting', '', 'Bunting', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(282, 'Yellow Hammer', '', 'Bunting', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(283, 'Reed Bunting', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(284, 'Bullfinch', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(285, 'Siskin', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(286, 'Goldfinch', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(287, 'Greenfinch', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(288, 'Lesser Redpoll', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(289, 'Linnet', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(290, 'Chaffinch', '', 'Finch', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(291, 'Risso\'s Dolphin', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(292, 'Leatherback Turtle', '', 'Marine Reptile', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(293, 'Minke Whale', '', 'Marine Mammal', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(294, 'Humpback', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(295, 'White-beaked Dolphin', '', 'Marine Mammal', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(296, 'Orca', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(297, 'Common Dolphin', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(298, 'Harbour Porpoise', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(299, 'Bottlenose Dolphin', '', 'Marine Mammal', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(300, 'Common Seal', '', 'Marine Mammal', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(301, 'Grey Seal', '', 'Marine Mammal', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(302, 'Tope Shark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(303, 'Spurdog Shark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(304, 'Spotted Ray', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(305, 'Thornback Ray', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(306, 'Nursehound Shark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(307, 'Small-spotted Catshark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(308, 'Montagu\'s Blenny', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(309, 'Painted Goby', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(310, 'Two spotted Goby', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(311, 'Shore Rockling', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(312, 'Red Mullet', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(313, 'Montagu\'s Sea Snail', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(314, 'Lesser Weaver Fish', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(315, 'European Flounder', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(316, 'Shore Clingfish / Cornish Sucker', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(317, 'Long Spined Sea Scorpion', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(318, 'Giant Goby', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(319, 'Worm Pipefish', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(320, 'Ballan Wrasse', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(321, 'Corkwing Wrasse', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(322, 'Thresher Shark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(323, 'Blonde Ray', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(324, 'Cuckoo Ray', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(325, 'Undulate Ray', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(326, 'Porbeagle Shark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(327, 'Cuckoo Wrasse', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(328, 'Common Skate', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(329, 'Sand Eel', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(330, 'Blue Shark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(331, 'Black Sea Bream', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(332, 'European Seabass', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(333, 'Long-snouted Seahorse', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(334, 'Short-snouted Seahorse', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(335, 'Red Gurnard', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(336, 'Mackerel', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(337, 'Sunfish', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(338, 'Plaice', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(339, 'Lumpsucker', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(340, 'Rock Goby', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(341, 'Tompot Blenny', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(342, 'Shanny', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(343, 'Butterfish', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(344, 'Basking Shark', '', 'Marine Fish', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(345, 'Nathusius Pipistrelle', 'pipistrellus nathusii', 'Bat', 6.00, 15.50, 'g', 32.00, 37.00, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(346, 'Soprano Pipistrelle', 'pipistrellus pygmaeus', 'Bat', 3.50, 8.50, 'g', 28.00, 32.30, 'mm', 'Maggie Brown / BCT ', 'forearm', NULL),
(347, 'Greater Mouse-eared', 'myotis myotis', 'Bat', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(348, 'Northern white-breasted Hedgehog', 'Erinaceus Roumanicus', 'Hedgehog', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(349, 'European Fat Dormouse', 'Glis Glis', 'Dormouse', 0.00, 0.00, '', 0.00, 0.00, '', '', NULL, NULL),
(350, 'Deer Mouse', 'peromyscus', 'Mouse', 0.00, 0.00, '', 0.00, 0.00, '', '', '', ''),
(351, 'Eastern Cottontail', 'sylvilagus floridanus', 'Rabbit', 140.00, 160.00, 'g', 0.00, 0.00, '', '', '', ''),
(352, 'Mourning Dove', 'zenaida macroura', 'Dove', 0.00, 0.00, '', 0.00, 0.00, '', '', '', ''),
(354, 'Carrion Crow', 'corvus corone', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', '', ''),
(355, 'American Crow', 'corvus brachyrhynchos', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', '', ''),
(356, 'Chihuahuan Raven', 'corvus cryptoleucus', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', '', ''),
(357, 'Common Raven', 'corvus corax', 'Corvid', 0.00, 0.00, '', 0.00, 0.00, '', '', '', ''),
(360, 'Brown-headed Cowbird', '', 'Thrush', 30.00, 60.00, 'g', 16.00, 22.00, 'cm', 'https://en.wikipedia.org/wiki/Brown-headed_cowbird', '', 'Least Concern'),
(361, 'Common Grackle', '', 'Thrush', 72.00, 142.00, 'g', 28.00, 34.00, 'cm', 'https://en.wikipedia.org/wiki/Common_grackle', '', 'Near Threatened'),
(362, 'American Robin', '', 'Thrush', 72.00, 94.00, 'g', 23.00, 28.00, 'cm', 'https://en.wikipedia.org/wiki/American_robin', '', 'Least Concern'),
(363, 'Grey Catbird', '', 'Thrush', 23.00, 56.00, 'g', 22.00, 30.00, 'cm', 'https://en.wikipedia.org/wiki/Gray_catbird', '', 'Least Concern'),
(364, 'Eastern Bluebird', '', 'Thrush', 10.00, 25.00, 'g', 15.00, 20.00, 'cm', 'https://en.wikipedia.org/wiki/Bluebird', '', 'Least Concern'),
(366, 'Song Sparrow', '', 'Sparrow', 11.00, 50.00, 'g', 11.00, 18.00, 'cm', 'https://en.wikipedia.org/wiki/Song_sparrow', '', 'Least Concern'),
(367, 'House Finch', '', 'Finch', 16.00, 27.00, 'g', 12.50, 15.00, 'cm', 'https://en.wikipedia.org/wiki/House_finch', '', 'Least Concern'),
(368, 'Northern Mockingbird', '', 'Thrush', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.allaboutbirds.org/guide/Northern_Mockingbird/overview', '', 'Least Concern'),
(369, 'Northern Cardinal', '', 'Bunting', 33.00, 65.00, 'g', 21.00, 23.00, 'mm', 'Fringillidae', '', 'Least Concern'),
(370, 'Blue-Grey Gnatcatcher', '', 'Wren', 5.00, 7.00, 'g', 10.00, 13.00, 'cm', 'https://en.wikipedia.org/wiki/Blue-gray_gnatcatcher', '', 'Least Concern'),
(371, 'Naked-rumped Tomb', 'taphozous nudiventris', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, '', 'https://www.eurobats.org/about_eurobats/protected_bat_species', 'mm', 'Least Concern'),
(372, 'European Free-tailed', 'tadarida teniotis', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Unknown'),
(373, 'Egyptian Fruit', 'rousettus aegyptiacus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(374, 'Blasius Horseshoe', 'rhinolophus blasii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(375, 'Mediterranean Horseshoe', 'rhinolophus euryale', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Near Threatened'),
(376, 'Mehelys Horseshoe', 'rhinolophus mehelyi', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Vulnerable'),
(377, 'Caspian Barbastelle', 'barbastella caspica', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Not Evaluated'),
(378, 'Anatolian Serotine', 'eptescicus anatolicus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(379, 'Isabelline Serotine', 'eptesicus isabellinus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(380, 'Northern', 'eptesicus nilssonii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(381, 'Ognevs Serotine', 'eptesicus ognevi', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Not Evaluated'),
(382, 'Savis Pipistrelle', 'hypsugo savii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(383, 'Pale Bent-wing', 'miniopterus pallidus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Not Evaluated'),
(384, 'Schreibers Bent-winged', 'miniopoterus schreibersii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Near Threatened'),
(385, 'Lesser Mouse-eared', 'myotis blythii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(386, 'Long-fingered', 'myotis capaccinii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Vulnerable'),
(387, 'Pond', 'myotis dasycneme', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Near Threatened'),
(388, 'Davids Mouse-eared', 'myotis davidii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(389, 'Geoffroys', 'myotis emarginatus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(390, 'Maghrebian Mouse-eared', 'myotis punicus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Data Deficient'),
(391, 'Schaubs Myotis', 'myotis schaubi', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Data Deficient'),
(392, 'Azorean Noctule', 'nyctalus azoreum', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Vulnerable'),
(393, 'Greater Noctule', 'nyctalus lasiopterus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Vulnerable'),
(394, 'Hemprichs Long-eared', 'otonycteris hemprichii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(395, 'Hanakis Dwarf', 'pipistrellus hanaki', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Data Deficient'),
(396, 'Kuhls Pipistrelle', 'pipistrellus kuhlii', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(397, 'Madeiran Pipistrelle', 'pipistrellus maderensis', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Endangered'),
(398, 'Balkan Long-eared', 'plecotus kolombatovici', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(399, 'Alpine Long-eared', 'plecotus macrobullaris', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern'),
(400, 'Sardinian Long-eared', 'plecotus sardus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Vulnerable'),
(401, 'Canary Long-eared', 'plecotus teneriffae', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Vulnerable'),
(402, 'Particoloured', 'vespertilio murinus', 'Bat', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.eurobats.org/about_eurobats/protected_bat_species', '', 'Least Concern');
INSERT INTO `rescue_animal_species` (`species_id`, `species_name`, `scientific_name`, `animal_type`, `species_weight_from`, `species_weight_to`, `species_weight_unit`, `species_measurement_from`, `species_measurement_to`, `species_measurement_unit`, `reference`, `species_measurement_standard`, `iucn_status`) VALUES
(403, 'Southern White-breasted', 'erinaceus concolor', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(404, 'Amur', 'erinaceus amurensis', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(405, 'African Pigmy', 'atelerix albiventris', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(406, 'Algerian', 'atelerix algirus', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(407, 'Somali', 'atelerix sclateri', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(408, 'South-African', 'atelerix frontalis', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(409, 'Long-eared', 'hemiechinus auritus', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(410, 'Indian Long-eared', 'hemiechinus collaris', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(411, 'Daurian', 'mesechinus dauuricus', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(412, 'Hughs', 'mesechinus hughi', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(413, 'Desert Ethiopian', 'paraechinus aethiopicus', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(414, 'Indian', 'paraechinus micropus', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(415, 'Brandts', 'paraechinus hypomelas', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(416, 'Bare-bellied', 'paraechinus nudiventris', 'Hedgehog', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.spikesfood.co.uk/hedgehogs-of-the-world/', '', ''),
(417, 'Asian Badger', 'meles leucurus', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(418, 'Japanese Badger', 'meles anakuma', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(419, 'American Badger', 'taxidea taxus', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(420, 'Honey Badger', 'mellivora capensis', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(421, 'Chinese Ferret-badger', 'melogale moschata', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(422, 'Bornean Ferret-badger', 'melogale everetti', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(423, 'Javan Ferret-badger', 'melogale orientalis', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(424, 'Burmese Ferret-badger', 'melogale personata', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(425, 'Greater Hog Badger', 'arctonyx collaris', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(426, 'Indonesian Stink Badger', 'mydaus javansis', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(427, 'Palawan Stink Badger', 'mydaus marchei', 'Badger', 0.00, 0.00, 'g', 0.00, 0.00, 'mm', 'https://www.trvst.world/biodiversity/types-of-badgers/', '', ''),
(428, 'Domestic Sheep', 'ovis aries', 'Sheep', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(429, 'Domestic Goat', 'capra hircus', 'Goat', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(430, 'Domestic Chicken', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(431, 'Rhode Island Red', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(432, 'Sussex', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(433, 'Sussex Bantam', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(434, 'Barnevelder', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(435, 'Maran', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(436, 'Orpington', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(437, 'Leghorn', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(438, 'Silkie', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(439, 'Cochin', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(440, 'Speckledy Hen', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(441, 'Gingernut Ranger', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(442, 'New Hampshire Red', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(443, 'Plymouth Rock', 'gallus gallus domesticus', 'Chicken', 0.00, 0.00, 'g', 0.00, 0.00, 'cm', '', '', ''),
(444, 'English Longhorn', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(445, 'Red Poll', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(446, 'White Park', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(447, 'Hereford', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(448, 'Highland', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(449, 'Ayrshire', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(450, 'Aberdeen Angus', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(451, 'South Devon', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(452, 'British White', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(453, 'Belted Galloway', 'bos taurus', 'Cow', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(454, 'Domestic Donkey', 'equus africanus asinus', 'Donkey', 0.00, 0.00, 'kg', 0.00, 0.00, 'cm', '', '', ''),
(455, 'Pileated Woodpecker', '', 'Woodpecker', 225.00, 400.00, 'g', 40.00, 49.00, 'cm', 'https://en.wikipedia.org/wiki/Pileated_woodpecker', '', 'Least Concern');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_animal_types`
--

CREATE TABLE `rescue_animal_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `animal_order` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(100, 'Dormouse', 'Mammal'),
(101, 'Sheep', 'Mammal'),
(102, 'Chicken', 'Bird'),
(103, 'Goat', 'Mammal'),
(104, 'Cow', 'Mammal'),
(105, 'Donkey', 'Mammal');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_areas`
--

CREATE TABLE `rescue_areas` (
  `area_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `area_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_centres`
--

CREATE TABLE `rescue_centres` (
  `rescue_id` int(8) NOT NULL,
  `rescue_name` varchar(255) NOT NULL,
  `owner_id` int(8) NOT NULL,
  `centre_type` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `office_tel` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `24_hour` varchar(255) NOT NULL,
  `address_line_one` varchar(255) NOT NULL,
  `address_line_two` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `postcode` varchar(255) NOT NULL,
  `coordinates` varchar(255) NOT NULL,
  `accepting_admissions` varchar(255) NOT NULL,
  `closed_message` varchar(255) NOT NULL,
  `species_accepted` varchar(255) NOT NULL,
  `opening_hours` text NOT NULL,
  `ngo_parameter` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_comments`
--

CREATE TABLE `rescue_comments` (
  `comment_id` int(8) NOT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `comment_on` datetime DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_connections`
--

CREATE TABLE `rescue_connections` (
  `connection_id` int(255) NOT NULL,
  `to_centre` int(8) DEFAULT NULL,
  `approved` varchar(5) DEFAULT NULL,
  `from_centre` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_dispositions`
--

CREATE TABLE `rescue_dispositions` (
  `disposition_id` int(3) NOT NULL,
  `disposition` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_dispositions`
--

INSERT INTO `rescue_dispositions` (`disposition_id`, `disposition`) VALUES
(1, 'Held in captivity'),
(2, 'Released'),
(3, 'Transferred to another rescue'),
(4, 'Died - Euthanised'),
(5, 'Died - after 48 hours'),
(6, 'Died - within 48 hours'),
(7, 'Died - on admission'),
(8, 'Long-term captive');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_dose_size`
--

CREATE TABLE `rescue_dose_size` (
  `dose_id` int(8) NOT NULL,
  `dose_name` varchar(255) DEFAULT NULL,
  `dose_short_name` varchar(3) DEFAULT NULL,
  `dose_multiplier` float DEFAULT NULL,
  `dose_divider` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_duties`
--

CREATE TABLE `rescue_duties` (
  `duty_id` int(8) NOT NULL,
  `duty` int(8) DEFAULT NULL,
  `person` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_duty_type`
--

CREATE TABLE `rescue_duty_type` (
  `duty_type_id` int(8) NOT NULL,
  `duty` varchar(255) DEFAULT NULL,
  `duty_colour` varchar(255) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_finders`
--

CREATE TABLE `rescue_finders` (
  `finder_id` int(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(255) NOT NULL,
  `update_preference` varchar(255) NOT NULL,
  `update_frequency` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `rescue_frequency_times`
--

CREATE TABLE `rescue_frequency_times` (
  `frequency_time_id` int(8) NOT NULL,
  `frequency_id` int(11) DEFAULT NULL,
  `time` time DEFAULT NULL,
  `frequency_name` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `rescue_images`
--

CREATE TABLE `rescue_images` (
  `image_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_incidents`
--

CREATE TABLE `rescue_incidents` (
  `incident_id` int(8) NOT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_location_line_1` varchar(255) DEFAULT NULL,
  `incident_location_line_2` varchar(255) DEFAULT NULL,
  `incident_location_city` varchar(255) DEFAULT NULL,
  `incident_location_postcode` varchar(255) DEFAULT NULL,
  `incident_centre_ref` varchar(255) DEFAULT NULL,
  `incident_total_casualties` int(8) DEFAULT NULL,
  `incident_doa` int(8) DEFAULT NULL,
  `incident_mass_cas` int(1) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_incident_related`
--

CREATE TABLE `rescue_incident_related` (
  `inc_rel_id` int(8) NOT NULL,
  `incident_id` int(8) DEFAULT NULL,
  `partner_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `is_deleted` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_injury_record`
--

CREATE TABLE `rescue_injury_record` (
  `injury_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `injury_site` varchar(255) DEFAULT NULL,
  `injury_type` varchar(255) DEFAULT NULL,
  `injury_notes` longtext DEFAULT NULL,
  `triage_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_labs`
--

CREATE TABLE `rescue_labs` (
  `lab_id` int(8) NOT NULL,
  `lab_date` datetime DEFAULT NULL,
  `sample_type` int(8) DEFAULT NULL,
  `lab_result` varchar(255) DEFAULT NULL,
  `reported_by` varchar(255) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `lab_test` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `rescue_locations`
--

CREATE TABLE `rescue_locations` (
  `location_id` int(11) NOT NULL,
  `centre_id` int(11) NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `location_type` varchar(255) NOT NULL,
  `max_occupancy` float DEFAULT NULL,
  `deleted` int(1) DEFAULT 0,
  `location_area` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_measurements`
--

CREATE TABLE `rescue_measurements` (
  `weight_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `date` datetime NOT NULL,
  `measurement` decimal(8,2) NOT NULL,
  `measurement_unit` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_medications`
--

CREATE TABLE `rescue_medications` (
  `medication_id` int(8) NOT NULL,
  `medication_name` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `common_name` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `contraindications` longtext DEFAULT NULL,
  `cautions` longtext DEFAULT NULL,
  `dose` varchar(255) DEFAULT NULL,
  `side_effects` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
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
(29, 'Marbofloxacin', 'Antibiotics', 'Marbocyl', '', '', '', '', ''),
(30, 'Amoxicillin Trihydrate', 'Antibiotic', 'Clamoxyl Long Acting', '', '', '', '', ''),
(31, 'Imidacloprid', 'Anthelmintics', 'Prinovox', '', '', '', '', ''),
(32, 'Emodepside', ' Anthelmintics', 'Profender', '', '', '', '', ''),
(33, 'Levamisole Hydrochloride', 'Anthelmintics', 'Levacide', '', 'Not to be used in cattle and sheep producing milk for human consumption', '', '', ''),
(34, 'Enilconozole', 'Antifungal', 'Imaverol', '', 'Horses: meat - Zero days', '', '', ''),
(35, 'Itraconozole', 'Antifungal', 'Itrafungol', '', '', '', '', ''),
(36, 'Chloramphenicol', 'Antibiotics', 'Chloromycetin', 'Eye drops', '', '', '', ''),
(37, 'Sulfadiazine Trimethoprim', 'Antibiotics', 'Diatrim', '', '', '', '', ''),
(38, 'Sulfamethoxazole Trimethoprim', 'Antibiotics', 'Sulfatrim', '', '', '', '', ''),
(39, 'Ivermectin', 'Anthelmentics', 'Xeno 50 Mini', 'Topical insecticidal and acaricidal spot-on solution. A simple and effective spot-on solution for prevention and treatment of common internal and external parasites of small mammals and birds. Applied to the skin, Xeno 50-mini is absorbed into the body and is particularly effective against the parasites which cause ear disease in the rabbit and ferret, and the mites that cause skin disease in guinea pigs, rabbits and rodents, including mange in guinea pigs and the condition known as walking dandruff in rabbits. It is also effective against many of the parasites which cause skin and feather problems in birds.\r\nThe active substance ivermectin is a substance known as an endectocide because it kills parasites that cause infestation inside the body (endoparasites) as well as those living outside the body on the skin (ectoparasites). Ivermectin is used in many species for the control of mites, roundworms and lice.', 'Do not use on animals other than those indicated. Serious reactions, including deaths, have been reported in dogs (especially Collies, Old English Sheepdogs and related breeds), tortoises and turtles treated with products containing the active substance.\r\nThis product is toxic to chelonians. Do not use in tortoises, turtles or related species.', 'For animal treatment only. Do not overdose.\r\nIf you are uncertain about the condition of your pet, consult a veterinary surgeon.\r\nIf symptoms persist, consult a veterinary surgeon.', '', 'Occasionally, central nervous system disorders, lethargy, inappetance, local irritation and/or hypersensitivity reactions may be observed. These signs may be more common in young animals. Should such a reaction occur, discontinue use and seek veterinary advice.\r\nIf you notice any serious effects or other effects not mentioned in this datasheet, please inform your veterinary surgeon.'),
(40, 'Permethrin', 'Anthelmentics', 'Johnsons Insecticidal Spray ', '', '', '', '', ''),
(41, 'Bromhexine', 'Mucolytic', 'Bisolvon', '', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_medications_given`
--

CREATE TABLE `rescue_medications_given` (
  `med_adm_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `medication_given` text NOT NULL,
  `dose` float(8,2) NOT NULL,
  `date` datetime NOT NULL,
  `centre_id` int(8) NOT NULL,
  `given_by` varchar(255) DEFAULT NULL,
  `dose_type` varchar(255) DEFAULT NULL,
  `stock_item_used` int(8) DEFAULT 0,
  `given_by_id` int(8) DEFAULT NULL,
  `vol_given` decimal(8,2) DEFAULT NULL,
  `batch_given` varchar(255) DEFAULT NULL,
  `exp_given` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='this table is used to record admin of medication';

-- --------------------------------------------------------

--
-- Table structure for table `rescue_medication_trans`
--

CREATE TABLE `rescue_medication_trans` (
  `med_trans_id` int(8) NOT NULL,
  `date` date DEFAULT NULL,
  `med_profile_id` int(8) DEFAULT NULL,
  `packs_in` float(8,2) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `batch_number` varchar(20) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `date_opened` date DEFAULT NULL,
  `date_finished` date DEFAULT NULL,
  `est_volume` int(4) DEFAULT NULL,
  `amount_destroyed` int(4) DEFAULT NULL,
  `reason_destroyed` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_messages`
--

CREATE TABLE `rescue_messages` (
  `message_id` int(8) NOT NULL,
  `from_centre` int(8) DEFAULT NULL,
  `to_centre` int(8) DEFAULT NULL,
  `message_sent` datetime DEFAULT NULL,
  `message` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_month_data`
--

CREATE TABLE `rescue_month_data` (
  `month_id` int(8) NOT NULL,
  `month_name` varchar(12) DEFAULT NULL,
  `month` date DEFAULT NULL,
  `count` int(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_month_data`
--

INSERT INTO `rescue_month_data` (`month_id`, `month_name`, `month`, `count`) VALUES
(1, 'January-23', '2023-01-01', 0),
(2, 'February-23', '2023-02-01', 0),
(3, 'March-23', '2023-03-01', 0),
(4, 'April-23', '2023-04-01', 0),
(5, 'May-23', '2023-05-01', 0),
(6, 'June-23', '2023-06-01', 0),
(7, 'July-23', '2023-07-01', 0),
(8, 'August-23', '2023-08-01', 0),
(9, 'September-23', '2023-09-01', 0),
(10, 'October-23', '2023-10-01', 0),
(11, 'November-23', '2023-11-01', 0),
(12, 'December-23', '2023-12-01', 0),
(13, 'January-24', '2024-01-01', 0),
(14, 'February-24', '2024-02-01', 0),
(15, 'March-24', '2024-03-01', 0),
(16, 'April-24', '2024-04-01', 0),
(17, 'May-24', '2024-05-01', 0),
(18, 'June-24', '2024-06-01', 0),
(19, 'July-24', '2024-07-01', 0),
(20, 'August-24', '2024-08-01', 0),
(21, 'September-24', '2024-09-01', 0),
(22, 'October-24', '2024-10-01', 0),
(23, 'November-24', '2024-11-01', 0),
(24, 'December-24', '2024-12-01', 0),
(25, 'May', '2023-05-01', 0),
(26, 'January-25', '2025-01-01', 0),
(27, 'February-25', '2025-02-01', 0),
(28, 'March-25', '2025-03-01', 0),
(29, 'April-25', '2025-04-01', 0),
(30, 'May-25', '2025-05-01', 0),
(31, 'June-25', '2025-06-01', 0),
(32, 'July-25', '2025-07-01', 0),
(33, 'August-25', '2025-08-01', 0),
(34, 'September-25', '2025-09-01', 0),
(35, 'October-25', '2025-10-01', 0),
(36, 'November-25', '2025-11-01', 0),
(37, 'December-25', '2025-12-01', 0);

-- --------------------------------------------------------

--
-- Table structure for table `rescue_networks`
--

CREATE TABLE `rescue_networks` (
  `network_id` int(11) NOT NULL,
  `network_name` varchar(255) DEFAULT NULL,
  `network_description` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_net_chat`
--

CREATE TABLE `rescue_net_chat` (
  `chat_id` int(10) NOT NULL,
  `from_centre` int(8) DEFAULT NULL,
  `chat_sent` datetime DEFAULT NULL,
  `chat` longtext DEFAULT NULL,
  `chat_user` varchar(255) DEFAULT NULL,
  `network_id` int(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_notes_patients`
--

CREATE TABLE `rescue_notes_patients` (
  `note_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `message` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `deleted` int(8) NOT NULL,
  `public` text DEFAULT NULL,
  `image_id` int(8) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_observations`
--

CREATE TABLE `rescue_observations` (
  `obs_id` int(8) NOT NULL,
  `obs_date` datetime DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `obs_severity_score` int(2) DEFAULT 99,
  `obs_severity_text` varchar(255) DEFAULT NULL,
  `obs_bcs_score` int(2) DEFAULT 99,
  `obs_bcs_text` varchar(255) DEFAULT NULL,
  `obs_age_score` int(2) DEFAULT 99,
  `obs_age_text` varchar(255) DEFAULT NULL,
  `obs_notes` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_orgs`
--

CREATE TABLE `rescue_orgs` (
  `org_id` int(8) NOT NULL,
  `org_name` varchar(255) DEFAULT NULL,
  `org_address` mediumtext DEFAULT NULL,
  `org_valid_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_partner_log`
--

CREATE TABLE `rescue_partner_log` (
  `p_log_id` int(8) NOT NULL,
  `partner_type` int(8) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `log_number` varchar(255) DEFAULT NULL,
  `log_notes` longtext DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `is_crime` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_partner_types`
--

CREATE TABLE `rescue_partner_types` (
  `p_type_id` int(8) NOT NULL,
  `partner_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_patients`
--

CREATE TABLE `rescue_patients` (
  `patient_id` int(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ringed` varchar(255) NOT NULL,
  `ring_number` varchar(255) NOT NULL,
  `microchipped` varchar(255) NOT NULL,
  `microchip_number` varchar(255) NOT NULL,
  `animal_type` varchar(255) NOT NULL,
  `animal_order` varchar(255) NOT NULL,
  `animal_species` varchar(255) NOT NULL,
  `sex` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `staff_wp_id` int(8) NOT NULL,
  `centre_id` int(8) NOT NULL,
  `date_added` datetime NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `transfer_id` int(8) DEFAULT 0,
  `created_by` int(8) DEFAULT NULL,
  `approx_dob` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_prescriptions`
--

CREATE TABLE `rescue_prescriptions` (
  `prescription_id` int(8) NOT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `medication` varchar(255) DEFAULT NULL,
  `dose` float(8,2) DEFAULT NULL,
  `dose_type` varchar(255) DEFAULT NULL,
  `duration` float DEFAULT NULL,
  `frequency` varchar(255) DEFAULT NULL,
  `frequency_id` int(8) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `rescue_query`
--

CREATE TABLE `rescue_query` (
  `q_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `q_from` date DEFAULT NULL,
  `q_to` date DEFAULT NULL,
  `q_date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

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
(7, 'Driver'),
(0, 'Unassigned User'),
(9, 'Researcher');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_roost_record`
--

CREATE TABLE `rescue_roost_record` (
  `record_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `network_id` int(8) DEFAULT NULL,
  `os_grid` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `town` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT NULL,
  `confirmed_by` varchar(255) DEFAULT NULL,
  `species` varchar(255) DEFAULT NULL,
  `number_age` varchar(255) DEFAULT NULL,
  `indoor_outdoor` varchar(2) DEFAULT NULL,
  `record_type` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_sample_types`
--

CREATE TABLE `rescue_sample_types` (
  `s_type_id` int(8) NOT NULL,
  `sample_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_sample_types`
--

INSERT INTO `rescue_sample_types` (`s_type_id`, `sample_type`) VALUES
(1, 'Blood'),
(2, 'Urine'),
(3, 'Fecal'),
(4, 'Saliva'),
(5, 'Skin'),
(6, 'Hair/Fur'),
(7, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_search_user`
--

CREATE TABLE `rescue_search_user` (
  `search_email` varchar(255) DEFAULT NULL,
  `centre_id` int(8) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_severity_score`
--

CREATE TABLE `rescue_severity_score` (
  `ss_id` int(8) NOT NULL,
  `ss_category` varchar(255) DEFAULT NULL,
  `ss_description` varchar(255) DEFAULT NULL,
  `ss_value` int(1) DEFAULT NULL,
  `ss_incare_desc` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_severity_score`
--

INSERT INTO `rescue_severity_score` (`ss_id`, `ss_category`, `ss_description`, `ss_value`, `ss_incare_desc`) VALUES
(1, 'Apparently Healthy', 'Animal has no obvious injuries', 1, 'No injuries'),
(2, 'Apparently Healthy', 'No or low number of fleas, lice, ticks etc', 1, 'No or low number of fleas, lice, ticks etc'),
(3, 'Mildly unwell', 'Animal appears thin or underweight', 2, 'Still thin or underweight'),
(4, 'Obvious Injuries', 'Animal has some small wounds (less than 10% body surface)', 3, 'Small wounds or wounds are healing'),
(5, 'Obvious Injuries', 'Appears disorientated or unwell', 3, 'Still appears unwell'),
(6, 'Severe Injuries', 'Animal has large deep wounds (e.g. bones visible or more than 10% body surface)', 4, 'Large deep wounds or wounds are not healing'),
(7, 'Severe Injuries', 'Animal has broken/unusable limb', 4, 'Has a broken limb, not repaired or healing'),
(8, 'Severe Injuries', 'Animal is extremely thin', 4, 'Still very thin'),
(9, 'Severe Injuries', 'Animal looks severely unwell (breathing rapidly, reluctant to move)', 4, 'Appears very unwell, rapid breathing, not moving '),
(10, 'Near Death', 'Unable to use back or front legs', 5, 'Unable to use legs, (front or back) won\'t move.'),
(11, 'Near Death', 'Doesn\'t respond to noise or light', 5, 'Not responding to noise or light'),
(12, 'Near Death', 'Animal is gasping for breath', 5, 'Gasping for breath');

-- --------------------------------------------------------

--
-- Table structure for table `rescue_stock_medication`
--

CREATE TABLE `rescue_stock_medication` (
  `medication_profile_id` int(8) NOT NULL,
  `medication` int(255) DEFAULT NULL,
  `concentration_dose` float(8,2) DEFAULT NULL,
  `concentration_volume` float(8,2) DEFAULT NULL,
  `pack_quantity` float(8,2) DEFAULT NULL,
  `reorder_level` float(8,2) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `user_id` int(8) DEFAULT NULL,
  `concentration_dose_type` varchar(5) DEFAULT NULL,
  `concentration_volume_type` varchar(12) DEFAULT NULL,
  `use_within` int(3) DEFAULT NULL,
  `mgml` float(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_todo`
--

CREATE TABLE `rescue_todo` (
  `todo_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `task` varchar(255) DEFAULT NULL,
  `priority` int(1) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_by` int(8) DEFAULT NULL,
  `frequency` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_todo_completions`
--

CREATE TABLE `rescue_todo_completions` (
  `completion_id` int(8) NOT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `task_id` int(8) DEFAULT NULL,
  `to_complete` date DEFAULT NULL,
  `completed` int(1) DEFAULT 0,
  `completed_by` int(8) DEFAULT NULL,
  `completed_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_treatments`
--

CREATE TABLE `rescue_treatments` (
  `treatment_given_id` int(8) NOT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `treatment` varchar(255) DEFAULT NULL,
  `treatment_free_text` varchar(255) DEFAULT NULL,
  `done_by` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_triages`
--

CREATE TABLE `rescue_triages` (
  `triage_id` int(8) NOT NULL,
  `admission_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `patient_id` int(8) DEFAULT NULL,
  `triage_date` datetime DEFAULT NULL,
  `care_form_used` varchar(255) DEFAULT NULL,
  `hpc` longtext DEFAULT NULL,
  `on_examination` longtext DEFAULT NULL,
  `age` int(1) DEFAULT NULL,
  `class` int(1) DEFAULT NULL,
  `ss` int(1) DEFAULT NULL,
  `bcs` int(1) DEFAULT NULL,
  `recorded_by` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_vets`
--

CREATE TABLE `rescue_vets` (
  `practice_id` int(8) NOT NULL,
  `practice_name` varchar(255) DEFAULT NULL,
  `practice_tel` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_vet_centres`
--

CREATE TABLE `rescue_vet_centres` (
  `rel_id` int(8) NOT NULL,
  `user_id` int(8) DEFAULT NULL,
  `centre_id` int(8) DEFAULT NULL,
  `practice_id` int(8) DEFAULT NULL,
  `include_all` int(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_weights`
--

CREATE TABLE `rescue_weights` (
  `weight_id` int(8) NOT NULL,
  `patient_id` int(8) NOT NULL,
  `date` datetime NOT NULL,
  `weight` decimal(8,2) NOT NULL DEFAULT 0.00,
  `weight_unit` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rescue_words`
--

CREATE TABLE `rescue_words` (
  `word_1` varchar(255) DEFAULT NULL,
  `word_2` varchar(255) DEFAULT NULL,
  `word_3` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `rescue_words`
--

INSERT INTO `rescue_words` (`word_1`, `word_2`, `word_3`) VALUES
('Antique White', 'Hedgehog', 'Scissors'),
('Aqua', 'Stork', 'Lamp'),
('Aquamarine', 'Woodpecker', 'Toolbox'),
('Beige', 'Walrus', 'Scraper'),
('Bisque', 'Elk', 'Magnets'),
('Blanched Almond', 'Llama', 'Watch'),
('Blue', 'Grosbeak', 'have'),
('Blue Violet', 'Clam', 'come'),
('Brown', 'Bear', 'Hearth'),
('Burlywood', 'Giraffe', 'Plate'),
('Cadet Blue', 'Sparrow', 'Raincoat'),
('Chartreuse', 'Pigeon', 'Casket'),
('Chocolate', 'Beaver', 'Clock'),
('Coral', 'Sloth', 'Almirah'),
('Cornflower Blue', 'Nuthatch', 'Can Opener'),
('Cornsilk', 'Raccoon', 'Garbage'),
('Crimson', 'Sea lion', 'point'),
('Cyan', 'Vulture', 'Headphones'),
('Dark Blue', 'Grackle', 'do'),
('Dark Cyan', 'Lark', 'Iron Box'),
('Dark Goldenrod', 'Pangolin', 'Carpet'),
('Dark Green', 'Eagle', 'Pen'),
('Dark Khaki', 'Tapir', 'Printer'),
('Dark Magenta', 'Pelican', 'ask'),
('Dark Olive Green', 'Sparrow', 'Bottle Opener'),
('Dark Orange', 'Fox', 'Nursery'),
('Dark Orchid', 'Shrimp', 'look'),
('Dark Red', 'Zebra', 'Mug'),
('Dark Salmon', 'Lemur', 'Zipper'),
('Dark Sea Green', 'Swallow', 'Gate'),
('Dark Slate', 'Blackbird', 'Cutlery'),
('Dark Slate Blue', 'Condor', 'go'),
('Dark Turquoise', 'Gull', 'Pencil'),
('Dark Violet', 'Squid', 'want'),
('Deep Pink', 'Manatee', 'leave'),
('Deep Sky Blue', 'Tanager', 'Vase'),
('Dodger Blue', 'Quail', 'Blender'),
('Firebrick', 'Elephant', 'Jar'),
('Forest Green', 'Raven', 'Hanger'),
('Fuchsia', 'Tuna', 'work'),
('Gold', 'Sea lion', 'Hose'),
('Goldenrod', 'Porcupine', 'Cloth'),
('Green', 'Parrot', 'Ice Cream'),
('Green Yellow', 'Finch', 'Button'),
('Hot Pink', 'Porpoise', 'call'),
('Indian Red', 'Tiger', 'Grater'),
('Indigo', 'Oyster', 'think'),
('Khaki', 'Snow leopard', 'Makeup Brush'),
('Lavender', 'Hummingbird', 'be'),
('Lawn Green', 'Seagull', 'Container'),
('Lemon Chiffon', 'Skunk', 'Kettle'),
('Light Blue', 'Warbler', 'Thermometer'),
('Light Coral', 'Giraffe', 'Lock'),
('Light Goldenrod', 'Seal', 'Hammer'),
('Light Goldenrod Yellow', 'Weasel', 'Table Fan'),
('Light Green', 'Peacock', 'Key'),
('Light Pink', 'Plankton', 'company'),
('Light Salmon', 'Anteater', 'Bolster'),
('Light Sea Green', 'Cuckoo', 'Camera'),
('Light Sky Blue', 'Sparrow', 'Kit'),
('Light Steel Blue', 'Pheasant', 'Broom'),
('Light Yellow', 'Whale', 'Umbrella'),
('Lime', 'Owl', 'Sofa'),
('Linen', 'Deer', 'Juicer'),
('Magenta', 'Coral', 'try'),
('Medium Aquamarine', 'Turkey', 'Attic'),
('Medium Blue', 'Goldfinch', 'say'),
('Medium Orchid', 'Eel', 'give'),
('Medium Purple', 'Lobster', 'take'),
('Medium Sea Green', 'Duck', 'Vacuum'),
('Medium Slate Blue', 'Starfish', 'know'),
('Medium Spring Green', 'Falcon', 'Drill'),
('Medium Turquoise', 'Robin', 'Cauldron'),
('Midnight Blue', 'Kestrel', 'Desk'),
('Misty Rose', 'Gorilla', 'Stapler'),
('Moccasin', 'Mole', 'Apartment'),
('Navajo White', 'Koala', 'Towel'),
('Navy', 'Flicker', 'get'),
('Old Lace', 'Otter', 'Bulb'),
('Olive', 'Wolf', 'Water'),
('Olive Drab', 'Bison', 'Ash'),
('Orange', 'Muskox', 'Bed'),
('Orange Red', 'Armadillo', 'Basket'),
('Orchid', 'Ray', 'feel'),
('Pale Goldenrod', 'Squirrel', 'Paint Brush'),
('Pale Green', 'Osprey', 'Microwave Oven'),
('Pale Turquoise', 'Crow', 'Glass'),
('Pale Viovar Red', 'Sea turtle', 'case'),
('Papaya Whip', 'Lynx', 'Blanket'),
('Peach Puff', 'Buffalo', 'Gym'),
('Peru', 'Coyote', 'Hook'),
('Pink', 'Sea cucumber', 'government'),
('Plum', 'Walrus', 'find'),
('Powder Blue', 'Wren', 'Stamp'),
('Rebecca Purple', 'Crab', 'see'),
('Red', 'Rhinoceros', 'Rug'),
('Rosy Brown', 'Lion', 'Dryer'),
('Royal Blue', 'Loon', 'Chair'),
('Saddle Brown', 'Bat', 'Candle'),
('Salmon', 'Chimpanzee', 'Torch'),
('Sandy Brown', 'Bobcat', 'Fan'),
('Sea Green', 'Goose', 'Teapot'),
('Sienna', 'Aardvark', 'Bucket'),
('Sky Blue', 'Starling', 'Yarn'),
('Slate Blue', 'Finch', 'make'),
('Spring Green', 'Pelican', 'Whip'),
('Steel Blue', 'Redwing', 'Pillows'),
('Tan', 'Jaguar', 'Table'),
('Teal', 'Heron', 'Mop'),
('Thistle', 'Seal', 'use'),
('Tomato', 'Monkey', 'Washer'),
('Turquoise', 'Blue Jay', 'Box'),
('Violet', 'Seagull', 'tell'),
('Web Green', 'Hawk', 'Radio'),
('Web Maroon', 'Hippopotamus', 'Pestle'),
('Web Purple', 'Swordfish', 'seem'),
('Wheat', 'Opossum', 'Bolt'),
('Yellow', 'Wombat', 'Charger'),
('Yellow Green', 'Cheetah', 'Bedroom');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lat_search`
--
ALTER TABLE `lat_search`
  ADD PRIMARY KEY (`centre_id`);

--
-- Indexes for table `network_cons`
--
ALTER TABLE `network_cons`
  ADD PRIMARY KEY (`net_con_id`);

--
-- Indexes for table `outcodepostcodes`
--
ALTER TABLE `outcodepostcodes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `postcodelatlng`
--
ALTER TABLE `postcodelatlng`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ID` (`id`),
  ADD KEY `postcode` (`postcode`);

--
-- Indexes for table `rescue_admissions`
--
ALTER TABLE `rescue_admissions`
  ADD PRIMARY KEY (`admission_id`);

--
-- Indexes for table `rescue_alerts`
--
ALTER TABLE `rescue_alerts`
  ADD PRIMARY KEY (`alert_id`);

--
-- Indexes for table `rescue_animal_orders`
--
ALTER TABLE `rescue_animal_orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexes for table `rescue_animal_species`
--
ALTER TABLE `rescue_animal_species`
  ADD PRIMARY KEY (`species_id`);

--
-- Indexes for table `rescue_animal_types`
--
ALTER TABLE `rescue_animal_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `rescue_areas`
--
ALTER TABLE `rescue_areas`
  ADD PRIMARY KEY (`area_id`);

--
-- Indexes for table `rescue_centres`
--
ALTER TABLE `rescue_centres`
  ADD PRIMARY KEY (`rescue_id`);

--
-- Indexes for table `rescue_comments`
--
ALTER TABLE `rescue_comments`
  ADD PRIMARY KEY (`comment_id`);

--
-- Indexes for table `rescue_connections`
--
ALTER TABLE `rescue_connections`
  ADD PRIMARY KEY (`connection_id`),
  ADD UNIQUE KEY `connection_id` (`connection_id`);

--
-- Indexes for table `rescue_dispositions`
--
ALTER TABLE `rescue_dispositions`
  ADD PRIMARY KEY (`disposition_id`),
  ADD UNIQUE KEY `disposition_id` (`disposition_id`);

--
-- Indexes for table `rescue_dose_size`
--
ALTER TABLE `rescue_dose_size`
  ADD PRIMARY KEY (`dose_id`);

--
-- Indexes for table `rescue_duties`
--
ALTER TABLE `rescue_duties`
  ADD PRIMARY KEY (`duty_id`);

--
-- Indexes for table `rescue_duty_type`
--
ALTER TABLE `rescue_duty_type`
  ADD PRIMARY KEY (`duty_type_id`),
  ADD UNIQUE KEY `duty_type_id` (`duty_type_id`);

--
-- Indexes for table `rescue_finders`
--
ALTER TABLE `rescue_finders`
  ADD PRIMARY KEY (`finder_id`);

--
-- Indexes for table `rescue_frequencies`
--
ALTER TABLE `rescue_frequencies`
  ADD PRIMARY KEY (`frequency_id`);

--
-- Indexes for table `rescue_frequency_times`
--
ALTER TABLE `rescue_frequency_times`
  ADD PRIMARY KEY (`frequency_time_id`);

--
-- Indexes for table `rescue_images`
--
ALTER TABLE `rescue_images`
  ADD PRIMARY KEY (`image_id`),
  ADD UNIQUE KEY `image_id` (`image_id`);

--
-- Indexes for table `rescue_incidents`
--
ALTER TABLE `rescue_incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD UNIQUE KEY `incident_id` (`incident_id`);

--
-- Indexes for table `rescue_incident_related`
--
ALTER TABLE `rescue_incident_related`
  ADD PRIMARY KEY (`inc_rel_id`),
  ADD UNIQUE KEY `inc_rel_id` (`inc_rel_id`);

--
-- Indexes for table `rescue_injury_record`
--
ALTER TABLE `rescue_injury_record`
  ADD PRIMARY KEY (`injury_id`);

--
-- Indexes for table `rescue_labs`
--
ALTER TABLE `rescue_labs`
  ADD PRIMARY KEY (`lab_id`),
  ADD UNIQUE KEY `lab_id` (`lab_id`);

--
-- Indexes for table `rescue_labs_tests`
--
ALTER TABLE `rescue_labs_tests`
  ADD PRIMARY KEY (`l_test_id`),
  ADD UNIQUE KEY `l_test_id` (`l_test_id`);

--
-- Indexes for table `rescue_locations`
--
ALTER TABLE `rescue_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD UNIQUE KEY `location_id` (`location_id`);

--
-- Indexes for table `rescue_measurements`
--
ALTER TABLE `rescue_measurements`
  ADD PRIMARY KEY (`weight_id`);

--
-- Indexes for table `rescue_medications`
--
ALTER TABLE `rescue_medications`
  ADD PRIMARY KEY (`medication_id`);

--
-- Indexes for table `rescue_medications_given`
--
ALTER TABLE `rescue_medications_given`
  ADD PRIMARY KEY (`med_adm_id`);

--
-- Indexes for table `rescue_medication_trans`
--
ALTER TABLE `rescue_medication_trans`
  ADD PRIMARY KEY (`med_trans_id`),
  ADD UNIQUE KEY `med_trans_id` (`med_trans_id`);

--
-- Indexes for table `rescue_messages`
--
ALTER TABLE `rescue_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD UNIQUE KEY `message_id` (`message_id`);

--
-- Indexes for table `rescue_month_data`
--
ALTER TABLE `rescue_month_data`
  ADD PRIMARY KEY (`month_id`);

--
-- Indexes for table `rescue_networks`
--
ALTER TABLE `rescue_networks`
  ADD PRIMARY KEY (`network_id`);

--
-- Indexes for table `rescue_net_chat`
--
ALTER TABLE `rescue_net_chat`
  ADD PRIMARY KEY (`chat_id`),
  ADD UNIQUE KEY `chat_id` (`chat_id`);

--
-- Indexes for table `rescue_notes_patients`
--
ALTER TABLE `rescue_notes_patients`
  ADD PRIMARY KEY (`note_id`);

--
-- Indexes for table `rescue_observations`
--
ALTER TABLE `rescue_observations`
  ADD PRIMARY KEY (`obs_id`),
  ADD UNIQUE KEY `obs_id` (`obs_id`);

--
-- Indexes for table `rescue_orgs`
--
ALTER TABLE `rescue_orgs`
  ADD PRIMARY KEY (`org_id`);

--
-- Indexes for table `rescue_partner_log`
--
ALTER TABLE `rescue_partner_log`
  ADD PRIMARY KEY (`p_log_id`),
  ADD UNIQUE KEY `p_lo_id` (`p_log_id`);

--
-- Indexes for table `rescue_partner_types`
--
ALTER TABLE `rescue_partner_types`
  ADD PRIMARY KEY (`p_type_id`);

--
-- Indexes for table `rescue_patients`
--
ALTER TABLE `rescue_patients`
  ADD PRIMARY KEY (`patient_id`);

--
-- Indexes for table `rescue_prescriptions`
--
ALTER TABLE `rescue_prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD UNIQUE KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `rescue_presenting_complaints`
--
ALTER TABLE `rescue_presenting_complaints`
  ADD PRIMARY KEY (`pc_id`);

--
-- Indexes for table `rescue_query`
--
ALTER TABLE `rescue_query`
  ADD PRIMARY KEY (`q_id`),
  ADD UNIQUE KEY `centre_id` (`centre_id`);

--
-- Indexes for table `rescue_roles`
--
ALTER TABLE `rescue_roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `rescue_roost_record`
--
ALTER TABLE `rescue_roost_record`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `record_id` (`record_id`);

--
-- Indexes for table `rescue_sample_types`
--
ALTER TABLE `rescue_sample_types`
  ADD PRIMARY KEY (`s_type_id`);

--
-- Indexes for table `rescue_search_user`
--
ALTER TABLE `rescue_search_user`
  ADD PRIMARY KEY (`centre_id`),
  ADD UNIQUE KEY `centre_id` (`centre_id`);

--
-- Indexes for table `rescue_severity_score`
--
ALTER TABLE `rescue_severity_score`
  ADD PRIMARY KEY (`ss_id`);

--
-- Indexes for table `rescue_stock_medication`
--
ALTER TABLE `rescue_stock_medication`
  ADD PRIMARY KEY (`medication_profile_id`);

--
-- Indexes for table `rescue_todo`
--
ALTER TABLE `rescue_todo`
  ADD PRIMARY KEY (`todo_id`);

--
-- Indexes for table `rescue_todo_completions`
--
ALTER TABLE `rescue_todo_completions`
  ADD PRIMARY KEY (`completion_id`),
  ADD UNIQUE KEY `completion_id` (`completion_id`);

--
-- Indexes for table `rescue_treatments`
--
ALTER TABLE `rescue_treatments`
  ADD PRIMARY KEY (`treatment_given_id`);

--
-- Indexes for table `rescue_triages`
--
ALTER TABLE `rescue_triages`
  ADD PRIMARY KEY (`triage_id`);

--
-- Indexes for table `rescue_vets`
--
ALTER TABLE `rescue_vets`
  ADD PRIMARY KEY (`practice_id`);

--
-- Indexes for table `rescue_vet_centres`
--
ALTER TABLE `rescue_vet_centres`
  ADD PRIMARY KEY (`rel_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rescue_weights`
--
ALTER TABLE `rescue_weights`
  ADD PRIMARY KEY (`weight_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `network_cons`
--
ALTER TABLE `network_cons`
  MODIFY `net_con_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `outcodepostcodes`
--
ALTER TABLE `outcodepostcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `postcodelatlng`
--
ALTER TABLE `postcodelatlng`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_admissions`
--
ALTER TABLE `rescue_admissions`
  MODIFY `admission_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_alerts`
--
ALTER TABLE `rescue_alerts`
  MODIFY `alert_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_animal_orders`
--
ALTER TABLE `rescue_animal_orders`
  MODIFY `order_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `rescue_animal_species`
--
ALTER TABLE `rescue_animal_species`
  MODIFY `species_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=456;

--
-- AUTO_INCREMENT for table `rescue_animal_types`
--
ALTER TABLE `rescue_animal_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `rescue_areas`
--
ALTER TABLE `rescue_areas`
  MODIFY `area_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_centres`
--
ALTER TABLE `rescue_centres`
  MODIFY `rescue_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_comments`
--
ALTER TABLE `rescue_comments`
  MODIFY `comment_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_connections`
--
ALTER TABLE `rescue_connections`
  MODIFY `connection_id` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_dispositions`
--
ALTER TABLE `rescue_dispositions`
  MODIFY `disposition_id` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rescue_dose_size`
--
ALTER TABLE `rescue_dose_size`
  MODIFY `dose_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_duties`
--
ALTER TABLE `rescue_duties`
  MODIFY `duty_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_duty_type`
--
ALTER TABLE `rescue_duty_type`
  MODIFY `duty_type_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_finders`
--
ALTER TABLE `rescue_finders`
  MODIFY `finder_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_frequencies`
--
ALTER TABLE `rescue_frequencies`
  MODIFY `frequency_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rescue_frequency_times`
--
ALTER TABLE `rescue_frequency_times`
  MODIFY `frequency_time_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `rescue_images`
--
ALTER TABLE `rescue_images`
  MODIFY `image_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_incidents`
--
ALTER TABLE `rescue_incidents`
  MODIFY `incident_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_incident_related`
--
ALTER TABLE `rescue_incident_related`
  MODIFY `inc_rel_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_injury_record`
--
ALTER TABLE `rescue_injury_record`
  MODIFY `injury_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_labs`
--
ALTER TABLE `rescue_labs`
  MODIFY `lab_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_labs_tests`
--
ALTER TABLE `rescue_labs_tests`
  MODIFY `l_test_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rescue_locations`
--
ALTER TABLE `rescue_locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_measurements`
--
ALTER TABLE `rescue_measurements`
  MODIFY `weight_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_medications`
--
ALTER TABLE `rescue_medications`
  MODIFY `medication_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `rescue_medications_given`
--
ALTER TABLE `rescue_medications_given`
  MODIFY `med_adm_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_medication_trans`
--
ALTER TABLE `rescue_medication_trans`
  MODIFY `med_trans_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_messages`
--
ALTER TABLE `rescue_messages`
  MODIFY `message_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_month_data`
--
ALTER TABLE `rescue_month_data`
  MODIFY `month_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `rescue_networks`
--
ALTER TABLE `rescue_networks`
  MODIFY `network_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_net_chat`
--
ALTER TABLE `rescue_net_chat`
  MODIFY `chat_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_notes_patients`
--
ALTER TABLE `rescue_notes_patients`
  MODIFY `note_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_observations`
--
ALTER TABLE `rescue_observations`
  MODIFY `obs_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_orgs`
--
ALTER TABLE `rescue_orgs`
  MODIFY `org_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_partner_log`
--
ALTER TABLE `rescue_partner_log`
  MODIFY `p_log_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_partner_types`
--
ALTER TABLE `rescue_partner_types`
  MODIFY `p_type_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_patients`
--
ALTER TABLE `rescue_patients`
  MODIFY `patient_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_prescriptions`
--
ALTER TABLE `rescue_prescriptions`
  MODIFY `prescription_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_presenting_complaints`
--
ALTER TABLE `rescue_presenting_complaints`
  MODIFY `pc_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `rescue_query`
--
ALTER TABLE `rescue_query`
  MODIFY `q_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_roost_record`
--
ALTER TABLE `rescue_roost_record`
  MODIFY `record_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_sample_types`
--
ALTER TABLE `rescue_sample_types`
  MODIFY `s_type_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rescue_severity_score`
--
ALTER TABLE `rescue_severity_score`
  MODIFY `ss_id` int(8) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `rescue_stock_medication`
--
ALTER TABLE `rescue_stock_medication`
  MODIFY `medication_profile_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_todo`
--
ALTER TABLE `rescue_todo`
  MODIFY `todo_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_todo_completions`
--
ALTER TABLE `rescue_todo_completions`
  MODIFY `completion_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_treatments`
--
ALTER TABLE `rescue_treatments`
  MODIFY `treatment_given_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_triages`
--
ALTER TABLE `rescue_triages`
  MODIFY `triage_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_vets`
--
ALTER TABLE `rescue_vets`
  MODIFY `practice_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_vet_centres`
--
ALTER TABLE `rescue_vet_centres`
  MODIFY `rel_id` int(8) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rescue_weights`
--
ALTER TABLE `rescue_weights`
  MODIFY `weight_id` int(8) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

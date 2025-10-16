-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 16, 2025 at 08:15 PM
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

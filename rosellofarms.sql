-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 16, 2025 at 02:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rosellofarms`
--

-- --------------------------------------------------------

--
-- Table structure for table `animal`
--

CREATE TABLE `animal` (
  `animal_id` int(11) NOT NULL,
  `weight` float NOT NULL,
  `price` float NOT NULL,
  `animal_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animal`
--

INSERT INTO `animal` (`animal_id`, `weight`, `price`, `animal_type_id`) VALUES
(1, 85.5, 18000, 1),
(2, 2.8, 280, 2),
(3, 480, 75000, 3),
(4, 38.5, 9500, 4),
(5, 3.5, 350, 5),
(6, 45, 12500, 6),
(9, 90, 20000, 1),
(11, 999, 2000000, 8);

-- --------------------------------------------------------

--
-- Table structure for table `animal_type`
--

CREATE TABLE `animal_type` (
  `animal_type_id` int(11) NOT NULL,
  `animal` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animal_type`
--

INSERT INTO `animal_type` (`animal_type_id`, `animal`) VALUES
(1, 'Pig'),
(2, 'Chicken'),
(3, 'Cow'),
(4, 'Goat'),
(5, 'Duck'),
(6, 'Sheep'),
(7, 'Rabbit'),
(8, 'Turkey');

-- --------------------------------------------------------

--
-- Table structure for table `crop`
--

CREATE TABLE `crop` (
  `crop_id` int(11) NOT NULL,
  `weight` float NOT NULL,
  `price` float NOT NULL,
  `crop_type_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crop`
--

INSERT INTO `crop` (`crop_id`, `weight`, `price`, `crop_type_id`) VALUES
(1, 50, 2800, 1),
(2, 25, 1500, 2),
(3, 15, 450, 3),
(4, 20, 300, 4),
(5, 10, 250, 5),
(6, 12, 180, 6),
(7, 30, 600, 7),
(8, 8, 160, 8),
(9, 45, 2500, 1),
(10, 22, 1300, 2),
(12, 1, 100, 5);

-- --------------------------------------------------------

--
-- Table structure for table `crop_type`
--

CREATE TABLE `crop_type` (
  `crop_type_id` int(11) NOT NULL,
  `crop` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crop_type`
--

INSERT INTO `crop_type` (`crop_type_id`, `crop`) VALUES
(1, 'Rice'),
(2, 'Corn'),
(3, 'Tomato'),
(4, 'Cabbage'),
(5, 'Carrot'),
(6, 'Onion'),
(7, 'Potato'),
(8, 'Lettuce');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `contact_info` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `contact_info`, `user_id`) VALUES
(1, '096999999', 2),
(2, '', 3),
(3, '09230854497', 4),
(4, '09230854497', 10);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `Order_ID` int(11) NOT NULL,
  `User_ID` int(11) NOT NULL,
  `Order_Date_Time` date NOT NULL,
  `Order_Status` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`Order_ID`, `User_ID`, `Order_Date_Time`, `Order_Status`) VALUES
(1, 4, '2025-07-15', 'processing'),
(2, 4, '2025-07-15', 'processing'),
(3, 4, '2025-07-15', 'processing'),
(4, 4, '2025-07-15', 'processing'),
(5, 4, '2025-07-15', 'processing'),
(6, 4, '2025-07-15', 'processing'),
(7, 10, '2025-07-15', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_pool`
--

CREATE TABLE `order_pool` (
  `OrderPoolID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Subtotal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_pool`
--

INSERT INTO `order_pool` (`OrderPoolID`, `OrderID`, `ProductID`, `Quantity`, `Subtotal`) VALUES
(2, 1, 3, 13, 975000),
(3, 2, 2, 14, 3920),
(4, 3, 2, 4, 1120),
(5, 3, 3, 3, 225000),
(6, 3, 1, 4, 72000),
(8, 4, 11, 4, 8000000),
(9, 5, 2, 4, 1120),
(10, 6, 2, 10, 2800),
(13, 7, 3, 2, 150000);

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `personnel_type_id` int(11) NOT NULL,
  `personnel_type` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`personnel_type_id`, `personnel_type`, `user_id`) VALUES
(1, '2', 1),
(2, 'Farm Worker', 7),
(3, 'Farm Worker', 11);

-- --------------------------------------------------------

--
-- Table structure for table `produce`
--

CREATE TABLE `produce` (
  `produce_type_id` int(11) NOT NULL,
  `produce_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` int(11) NOT NULL,
  `product_type` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `product_type`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(9, 1),
(11, 1),
(12, 2);

-- --------------------------------------------------------

--
-- Table structure for table `product_type`
--

CREATE TABLE `product_type` (
  `product_type_id` int(11) NOT NULL,
  `product_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_type_id` int(11) NOT NULL,
  `account_status` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `email`, `password`, `full_name`, `user_type_id`, `account_status`) VALUES
(1, 'arjoemara@gmail.com', '$2y$10$dmLqWedmjAjpcF22e4dI7uCLVuPE2ZcrKMA.JGEXzX2M.MutnAOXq', 'Arjoe Marata', 2, 0),
(2, 'maru@gmail.com', '$2y$10$qTWXLmQjQwaAXC8uedTkK.D94kzlqAOpsDf8O.yHnr03Kqv82cCyC', 'Mauris', 1, 0),
(3, 'arjoemarata52@gmail.com', '$2y$10$/tcjGVSBW0v8f/z2Q5giUOJ9zsjJkVDeLKri/dGb/y7c4YydNvvfG', 'arjoe', 1, 0),
(4, 'dyap3186@gmail.com', '$2y$10$K3YuiTp7BWd2ApLvb0KhmeSS0PgMcRKhNNsM/Ws3I3lFz/qTKfGc2', 'David Pay', 1, 0),
(5, 'admin@rf.com', '$2y$10$zPziSJ5Ax/OhgKfJV9SWFOUMoalAGKwCd8UU2fvcNU04DLx8hEcQ6', 'AD MIN', 3, 0),
(6, 'pooper@gmail.com', '$2y$10$eW/YQT1BCQYwmxB6z4W1XexcKBMY2kvnajtaszH05tSxO.AP5l8SG', 'Bo Gart', 2, 0),
(7, 'emp@rf.com', '$2y$10$0pyEkApyAvZRyzE/aKbCPui9DQgxIW56w6uCC4qRO5UVlYxAQX/sm', 'employ lee', 2, 0),
(8, 'sucker@gmail.com', '$2y$10$TwXHpLcinnUsPY9dflHRBu5yoGzSS6N/FxwKeYvnBmPXytGDAn5VC', 'fern ilius', 3, 1),
(9, 'myrrh@rf.com', '$2y$10$hyEJkIatgsx.BmP0cn/WG.PwS31eOAdKSvICuGWP/DKmwFenrJcUy', 'Myriush Russiana', 3, 1),
(10, 'rosello@gmail.com', '$2y$10$S5ohtdexo6Sf/Lz4EzHNpemHkrVO2H342cY5zDUDeREe2aZJqaHOS', 'Fern Andrei', 1, 0),
(11, 'fern@gmail.com', '$2y$10$aByliLaTH5DTKDZIOTcCCevSglQvtzIW7wxq7NkvrOXYp24GejwRS', 'Worker Rosello', 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_type`
--

CREATE TABLE `user_type` (
  `user_type_id` int(11) NOT NULL,
  `user_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type`
--

INSERT INTO `user_type` (`user_type_id`, `user_type`) VALUES
(1, 'Customer'),
(2, 'Employee'),
(3, 'Admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `animal`
--
ALTER TABLE `animal`
  ADD PRIMARY KEY (`animal_id`);

--
-- Indexes for table `animal_type`
--
ALTER TABLE `animal_type`
  ADD PRIMARY KEY (`animal_type_id`);

--
-- Indexes for table `crop`
--
ALTER TABLE `crop`
  ADD PRIMARY KEY (`crop_id`);

--
-- Indexes for table `crop_type`
--
ALTER TABLE `crop_type`
  ADD PRIMARY KEY (`crop_type_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`Order_ID`);

--
-- Indexes for table `order_pool`
--
ALTER TABLE `order_pool`
  ADD PRIMARY KEY (`OrderPoolID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`personnel_type_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `produce`
--
ALTER TABLE `produce`
  ADD PRIMARY KEY (`produce_type_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `product_type`
--
ALTER TABLE `product_type`
  ADD PRIMARY KEY (`product_type_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_type`
--
ALTER TABLE `user_type`
  ADD PRIMARY KEY (`user_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `animal`
--
ALTER TABLE `animal`
  MODIFY `animal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `animal_type`
--
ALTER TABLE `animal_type`
  MODIFY `animal_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `crop`
--
ALTER TABLE `crop`
  MODIFY `crop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `crop_type`
--
ALTER TABLE `crop_type`
  MODIFY `crop_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_pool`
--
ALTER TABLE `order_pool`
  MODIFY `OrderPoolID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `personnel_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `produce`
--
ALTER TABLE `produce`
  MODIFY `produce_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_type`
--
ALTER TABLE `product_type`
  MODIFY `product_type_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_type`
--
ALTER TABLE `user_type`
  MODIFY `user_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_pool`
--
ALTER TABLE `order_pool`
  ADD CONSTRAINT `order_pool_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_pool_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

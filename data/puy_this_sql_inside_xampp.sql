-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 02:44 PM
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
-- Database: `msystem_db`
--
CREATE DATABASE IF NOT EXISTS `msystem_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `msystem_db`;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT 'Home',
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postcode` varchar(10) NOT NULL,
  `country` varchar(50) DEFAULT 'Malaysia',
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `label`, `recipient_name`, `phone`, `address_line`, `city`, `state`, `postcode`, `country`, `is_default`) VALUES
(1, 1, 'Home', 'Test User', '0120000000', 'No. 1, Jalan Test', 'Putrajaya', 'Wilayah Persekutuan Putrajaya', '00000', 'Malaysia', 1),
(2, 1, 'Home', 'Oscar', '601112312312', 'No. 1, Jalan Test', 'Labuan', 'Wilayah Persekutuan Labuan', '90909', 'Malaysia', 0),
(3, 3, 'Home', '999999999', '011-12312312', '999999999', 'Masjid Tanah', 'Melaka', '99999', 'Malaysia', 1);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`, `profile_pic`) VALUES
(2, 'admin', '$2y$12$r7oqxw2n1JqdwFBqSTnheOcLFDB43y/VTXNPVPvgKjjJ5qUzB9Y46', '2026-03-22 14:58:52', 'avatar_2_1775274596.gif'),
(3, 'admin12', '$2y$10$fcwDkFZczy4VL5ClJXLg1eurSMx7iDswbR/gKTuwMNAzXAHvpAXtO', '2026-03-28 03:25:58', 'avatar_3_1774827510.gif'),
(4, 'admin123', '$2y$12$U1PUAFBpVonhMTaIx60kIeVBpXj2tPvDem65UXZ0l3K3Q4zZ8uTgu', '2026-04-05 13:20:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(20, 3, 25, 1, '2026-03-29 15:26:47'),
(21, 3, 23, 1, '2026-03-29 15:26:48');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `icon` varchar(10) DEFAULT '?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Blind Box', 'blind-box', '🎁'),
(2, 'Doll', 'doll', '🧸');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `change_qty` int(11) NOT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `attempt_time` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'QR Pay',
  `tracking_number` varchar(50) DEFAULT NULL,
  `receipt_token` varchar(64) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `address_id`, `voucher_id`, `subtotal`, `discount_amount`, `total_amount`, `status`, `payment_method`, `tracking_number`, `receipt_token`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 150.00, 0.00, 150.00, 'pending', 'QR Pay', NULL, 'cd9e5e6baabdbc643130507e8daa9074', '', '2026-03-28 13:22:23', '2026-03-28 13:22:23'),
(2, 3, 3, NULL, 148.00, 0.00, 148.00, 'pending', 'QR Pay', NULL, '923baa86d5a631729f1b5edc4c49dcb5', '', '2026-03-28 13:23:03', '2026-03-28 13:23:03'),
(3, 1, 2, NULL, 99.00, 0.00, 99.00, 'delivered', 'QR Pay', '', '43ead328933fa01a49644db3da8e2bbd', '', '2026-04-07 10:52:16', '2026-04-07 10:54:05'),
(4, 1, 1, NULL, 500.00, 0.00, 500.00, 'pending', 'QR Pay', NULL, 'd19585aa945b804975970c53142d9196', '', '2026-04-10 15:07:37', '2026-04-10 15:07:37');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 21, 3, 50.00),
(2, 2, 21, 1, 50.00),
(3, 2, 23, 2, 49.00),
(4, 3, 18, 1, 50.00),
(5, 3, 23, 1, 49.00),
(6, 4, 25, 1, 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 5,
  `discount` decimal(5,2) DEFAULT 0.00,
  `is_blind_box` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_top_sale` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `stock`, `low_stock_threshold`, `discount`, `is_blind_box`, `is_active`, `is_featured`, `is_top_sale`, `created_at`) VALUES
(8, '罗小黑', '', 50.00, 1, 50, 5, 5.00, 0, 1, 0, 1, '2026-03-27 14:11:27'),
(9, 'Pingu', '', 50.00, 1, 50, 5, 0.00, 0, 1, 0, 0, '2026-03-27 14:12:42'),
(10, 'Jeddy LULU', '', 50.00, 1, 50, 5, 0.00, 0, 1, 0, 1, '2026-03-27 14:13:39'),
(11, 'Bikini Bottom Buddies', '', 50.00, 1, 50, 5, 3.00, 0, 1, 0, 0, '2026-03-27 14:16:31'),
(12, 'Reshape', '', 50.00, 1, 50, 5, 0.00, 0, 1, 0, 0, '2026-03-27 14:17:31'),
(13, 'Cry Baby', '', 50.00, 1, 50, 5, 0.00, 0, 1, 0, 0, '2026-03-27 14:18:16'),
(14, 'Crush On You', '', 50.00, 1, 50, 5, 0.00, 0, 1, 0, 0, '2026-03-27 14:19:26'),
(15, 'Le Petit Primce', '', 50.00, 1, 48, 5, 0.00, 0, 1, 0, 0, '2026-03-27 14:20:16'),
(16, 'Amkisan\'s Macical Theatre', '', 50.00, 1, 50, 5, 10.00, 0, 1, 0, 0, '2026-03-27 14:21:23'),
(17, 'Capybara', '', 50.00, 2, 50, 5, 10.00, 0, 1, 0, 1, '2026-03-27 14:23:53'),
(18, 'SHIRLEY', '', 50.00, 2, 49, 5, 0.00, 0, 1, 0, 1, '2026-03-27 14:28:19'),
(19, 'ELEPHANT', '', 50.00, 2, 50, 5, 0.00, 0, 1, 0, 0, '2026-03-27 14:29:00'),
(20, 'PLUTO', '', 50.00, 2, 50, 5, 5.00, 0, 1, 0, 0, '2026-03-27 14:29:33'),
(21, 'buzhidao', '', 50.00, 2, 44, 5, 0.00, 0, 1, 0, 1, '2026-03-27 14:31:29'),
(22, '小熊妮娜', '', 50.00, 2, 50, 5, 0.00, 0, 0, 0, 1, '2026-03-27 14:32:29'),
(23, 'Rabbit', '', 50.00, 2, 47, 5, 2.00, 0, 1, 0, 0, '2026-03-27 14:33:10'),
(24, 'Teddy', '', 50.00, 2, 50, 5, 0.00, 0, 0, 0, 0, '2026-03-27 14:38:57'),
(25, 'Milk Puppy', '', 500.00, 2, 3, 5, 0.00, 0, 1, 0, 0, '2026-03-27 14:39:37'),
(26, 'RABBIT (柠檬大福)', '', 50.00, 2, 30, 5, 3.00, 0, 0, 0, 1, '2026-03-27 14:40:34'),
(27, 'JHAO', 'bdfbfb', 99999999.99, 2, 3000, 5, 0.00, 0, 1, 0, 0, '2026-03-28 03:42:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `sort_order`) VALUES
(13, 8, 'products/product_8_1774620687_0.png', NULL, 0),
(14, 9, 'products/product_9_1774620762_0.png', NULL, 0),
(15, 10, 'products/product_10_1774620819_0.png', NULL, 0),
(16, 11, 'products/product_11_1774620991_0.png', NULL, 0),
(17, 12, 'products/product_12_1774621051_0.png', NULL, 0),
(18, 13, 'products/product_13_1774621096_0.png', NULL, 0),
(19, 14, 'products/product_14_1774621166_0.png', NULL, 0),
(20, 15, 'products/product_15_1774621216_0.png', NULL, 0),
(21, 16, 'products/product_16_1774621283_0.png', NULL, 0),
(22, 17, 'products/product_17_1774621433_0.png', NULL, 0),
(23, 18, 'products/product_18_1774621699_0.png', NULL, 0),
(24, 19, 'products/product_19_1774621740_0.png', NULL, 0),
(25, 20, 'products/product_20_1774621773_0.png', NULL, 0),
(26, 21, 'products/product_21_1774621889_0.png', NULL, 0),
(27, 22, 'products/product_22_1774621949_0.png', NULL, 0),
(28, 23, 'products/product_23_1774621990_0.png', NULL, 0),
(29, 24, 'products/product_24_1774622337_0.png', NULL, 0),
(31, 26, 'products/product_26_1774622434_0.png', NULL, 0),
(32, 26, 'products/product_26_1774622853_1.png', NULL, 1),
(35, 25, 'products/product_25_1774666578_0.png', NULL, 0),
(36, 27, 'products/product_27_1774669378_0.gif', NULL, 0),
(38, 27, 'products/product_27_1775274438_1.jpg', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `remember_token` varchar(64) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_locked`, `remember_token`, `profile_pic`, `created_at`) VALUES
(1, 'JHAO', 'junhao2401@gmail.com', '$2y$12$mVEbt.N3fSttXz3DiCCHrOsJUeVVo8qXxrU4O0oNnvkjv8tZskq6a', 0, NULL, 'avatar_1_1774247985.png', '2026-03-23 04:07:51'),
(2, 'hihi', 'hihi@gmail.com', '$2y$12$tyJQMQDpj9q4jcrINafDj.1xwEcAg72UKn7ZfvTzCRlhmsJxEzBu2', 0, NULL, 'avatar_2_1774602621.png', '2026-03-27 09:09:45'),
(3, 'ZZQ', 'zz@qq.com', '$2y$12$22a8bJCAGx2DYgGHD8DIJezJ4SDnZUvjy4wl.WXBsbV34h3N08KGa', 0, NULL, 'avatar_3_1774668479.png', '2026-03-28 03:17:11'),
(4, 'qiuqiu', 'qiuqiu@qiuqiushop.com', '$2y$12$snt748f9s2EkZv8cb6aGm.807d4vXEnZcg2Pq9TS0sKAL1ebCYjRe', 0, NULL, NULL, '2026-04-04 02:23:38');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `code` varchar(30) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `usage_limit` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `discount_type`, `discount_value`, `min_purchase`, `usage_limit`, `used_count`, `expiry_date`, `is_active`) VALUES
(1, 'FREE10', 'fixed', 10.00, 0.00, 100, 1, NULL, 1),
(2, 'FREE5', 'fixed', 5.00, 20.00, 100, 0, NULL, 1),
(3, 'FREE20', 'fixed', 20.00, 50.00, 100, 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `added_at`) VALUES
(7, 3, 16, '2026-03-28 03:17:46'),
(8, 3, 15, '2026-03-28 03:17:47'),
(9, 3, 14, '2026-03-28 03:17:48'),
(15, 1, 27, '2026-03-28 03:43:41'),
(16, 1, 8, '2026-04-10 16:16:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_token` (`receipt_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

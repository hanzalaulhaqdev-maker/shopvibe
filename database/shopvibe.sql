-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 01:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shopvibe`
--
CREATE DATABASE IF NOT EXISTS `shopvibe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `shopvibe`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Admin User', 'admin@shopvibe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-04-23 11:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `image`, `created_at`) VALUES
(1, 'Women', 'women', 'cat-women.png', '2026-04-23 11:35:44'),
(2, 'Men', 'men', 'cat-men.png', '2026-04-23 11:35:44'),
(3, 'Accessories', 'accessories', 'cat-accessories.png', '2026-04-23 11:35:44'),
(4, 'Shoes', 'shoes', 'cat-shoes.png', '2026-04-23 11:35:44'),
(5, 'Sale', 'sale', 'cat-sale.png', '2026-04-23 11:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` enum('percent','fixed') DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `min_order` decimal(10,2) DEFAULT 0.00,
  `max_uses` int(11) DEFAULT 100,
  `used_count` int(11) DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `items_json` longtext DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `paypal_transaction_id` varchar(255) DEFAULT NULL,
  `payoneer_payment_id` varchar(255) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_main` varchar(255) DEFAULT NULL,
  `image_hover` varchar(255) DEFAULT NULL,
  `images_json` text DEFAULT NULL,
  `sizes_json` varchar(255) DEFAULT NULL,
  `colors_json` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `category_id`, `price`, `sale_price`, `description`, `image_main`, `image_hover`, `images_json`, `sizes_json`, `colors_json`, `stock`, `featured`, `status`, `created_at`) VALUES
(1, 'Floral Summer Dress', 'floral-summer-dress', 1, 89.99, 69.99, 'A beautiful floral summer dress perfect for warm days. Features a flowing silhouette and breathable fabric.', 'dress1.png', 'dress1-hover.png', '[\"dress1.png\",\"dress1-2.png\",\"dress1-3.png\"]', '[\"XS\",\"S\",\"M\",\"L\",\"XL\"]', '[\"Red\",\"Blue\",\"White\"]', 45, 1, 'active', '2026-04-23 11:35:44'),
(2, 'Classic White Shirt', 'classic-white-shirt', 2, 59.99, NULL, 'A timeless white shirt crafted from premium cotton. Perfect for both casual and formal occasions.', 'shirt1.png', 'shirt1-hover.png', '[\"shirt1.png\",\"shirt1-2.png\"]', '[\"S\",\"M\",\"L\",\"XL\",\"XXL\"]', '[\"White\",\"Light Blue\"]', 120, 1, 'active', '2026-04-23 11:35:44'),
(3, 'Leather Crossbody Bag', 'leather-crossbody-bag', 3, 129.99, 99.99, 'Genuine leather crossbody bag with adjustable strap and multiple compartments.', 'bag1.png', 'bag1-hover.png', '[\"bag1.png\",\"bag1-2.png\"]', '[\"One Size\"]', '[\"Brown\",\"Black\",\"Tan\"]', 30, 1, 'active', '2026-04-23 11:35:44'),
(4, 'Running Sneakers', 'running-sneakers', 4, 149.99, NULL, 'High-performance running sneakers with cushioned sole and breathable mesh upper.', 'shoes1.png', 'shoes1-hover.png', '[\"shoes1.png\",\"shoes1-2.png\",\"shoes1-3.png\"]', '[\"7\",\"8\",\"9\",\"10\",\"11\",\"12\"]', '[\"Black\",\"White\",\"Grey\"]', 80, 1, 'active', '2026-04-23 11:35:44'),
(5, 'Denim Jacket', 'denim-jacket', 1, 79.99, NULL, 'Classic denim jacket with vintage wash. Features button closure and chest pockets.', 'jacket1.png', 'jacket1-hover.png', '[\"jacket1.png\",\"jacket1-2.png\"]', '[\"XS\",\"S\",\"M\",\"L\",\"XL\"]', '[\"Blue\",\"Black\",\"Light Blue\"]', 60, 1, 'active', '2026-04-23 11:35:44'),
(6, 'Chino Pants', 'chino-pants', 2, 69.99, 49.99, 'Slim-fit chino pants in versatile colors. Perfect for smart-casual looks.', 'pants1.png', 'pants1-hover.png', '[\"pants1.png\",\"pants1-2.png\"]', '[\"28\",\"30\",\"32\",\"34\",\"36\"]', '[\"Navy\",\"Khaki\",\"Olive\"]', 95, 0, 'active', '2026-04-23 11:35:44'),
(7, 'Silk Scarf', 'silk-scarf', 3, 39.99, NULL, 'Luxurious silk scarf with artistic print. Adds elegance to any outfit.', 'scarf1.png', 'scarf1-hover.png', '[\"scarf1.png\",\"scarf1-2.png\"]', '[\"One Size\"]', '[\"Multi\",\"Red\",\"Blue\"]', 50, 0, 'active', '2026-04-23 11:35:44'),
(8, 'Ankle Boots', 'ankle-boots', 4, 189.99, 159.99, 'Stylish ankle boots with block heel and side zipper. Comfortable for all-day wear.', 'boots1.png', 'boots1-hover.png', '[\"boots1.png\",\"boots1-2.png\"]', '[\"6\",\"7\",\"8\",\"9\",\"10\"]', '[\"Black\",\"Brown\",\"Tan\"]', 40, 1, 'active', '2026-04-23 11:35:44'),
(9, 'Knit Sweater', 'knit-sweater', 1, 69.99, NULL, 'Cozy knit sweater with ribbed cuffs and hem. Soft and warm for cooler days.', 'sweater1.png', 'sweater1-hover.png', '[\"sweater1.png\",\"sweater1-2.png\"]', '[\"XS\",\"S\",\"M\",\"L\",\"XL\"]', '[\"Cream\",\"Grey\",\"Navy\"]', 70, 0, 'active', '2026-04-23 11:35:44'),
(10, 'Polo Shirt', 'polo-shirt', 2, 54.99, 39.99, 'Classic polo shirt with embroidered logo. Breathable pique cotton fabric.', 'polo1.png', 'polo1-hover.png', '[\"polo1.png\",\"polo1-2.png\"]', '[\"S\",\"M\",\"L\",\"XL\",\"XXL\"]', '[\"Navy\",\"White\",\"Red\",\"Black\"]', 100, 0, 'active', '2026-04-23 11:35:44'),
(11, 'Statement Necklace', 'statement-necklace', 3, 49.99, NULL, 'Bold statement necklace with geometric design. Perfect for elevating any outfit.', 'necklace1.png', 'necklace1-hover.png', '[\"necklace1.png\",\"necklace1-2.png\"]', '[\"One Size\"]', '[\"Gold\",\"Silver\"]', 25, 0, 'active', '2026-04-23 11:35:44'),
(12, 'Canvas Sneakers', 'canvas-sneakers', 4, 59.99, NULL, 'Casual canvas sneakers with vulcanized rubber sole. Everyday comfort.', 'canvas1.png', 'canvas1-hover.png', '[\"canvas1.png\",\"canvas1-2.png\"]', '[\"6\",\"7\",\"8\",\"9\",\"10\",\"11\"]', '[\"White\",\"Black\",\"Navy\"]', 150, 0, 'active', '2026-04-23 11:35:44'),
(13, 'Maxi Skirt', 'maxi-skirt', 1, 79.99, 59.99, 'Flowing maxi skirt with elastic waistband. Elegant and comfortable.', 'skirt1.png', 'skirt1-hover.png', '[\"skirt1.png\",\"skirt1-2.png\"]', '[\"XS\",\"S\",\"M\",\"L\"]', '[\"Black\",\"Floral\",\"Navy\"]', 55, 0, 'active', '2026-04-23 11:35:44'),
(14, 'Blazer', 'blazer', 2, 149.99, NULL, 'Tailored blazer with notch lapels. Perfect for business or formal occasions.', 'blazer1.png', 'blazer1-hover.png', '[\"blazer1.png\",\"blazer1-2.png\"]', '[\"S\",\"M\",\"L\",\"XL\"]', '[\"Navy\",\"Black\",\"Grey\"]', 35, 1, 'active', '2026-04-23 11:35:44'),
(15, 'Leather Belt', 'leather-belt', 3, 44.99, NULL, 'Genuine leather belt with classic buckle. A wardrobe essential.', 'belt1.png', 'belt1-hover.png', '[\"belt1.png\",\"belt2.png\"]', '[\"S\",\"M\",\"L\",\"XL\"]', '[\"Brown\",\"Black\"]', 80, 0, 'active', '2026-04-23 11:35:44'),
(16, 'High Heels', 'high-heels', 4, 119.99, 89.99, 'Elegant high heels with pointed toe. Perfect for evening events.', 'heels1.png', 'heels1-hover.png', '[\"heels1.png\",\"heels1-2.png\"]', '[\"5\",\"6\",\"7\",\"8\",\"9\",\"10\"]', '[\"Black\",\"Nude\",\"Red\"]', 45, 0, 'active', '2026-04-23 11:35:44'),
(17, 'Crop Top', 'crop-top', 1, 34.99, NULL, 'Trendy crop top with ribbed texture. Pairs perfectly with high-waisted bottoms.', 'crop1.png', 'crop1-hover.png', '[\"crop1.png\",\"crop1-2.png\"]', '[\"XS\",\"S\",\"M\",\"L\"]', '[\"White\",\"Black\",\"Pink\"]', 90, 0, 'active', '2026-04-23 11:35:44'),
(18, 'Hoodie', 'hoodie', 2, 74.99, 59.99, 'Comfortable hoodie with kangaroo pocket and drawstring hood.', 'hoodie1.png', 'hoodie1-hover.png', '[\"hoodie1.png\",\"hoodie1-2.png\"]', '[\"S\",\"M\",\"L\",\"XL\",\"XXL\"]', '[\"Grey\",\"Black\",\"Navy\",\"Olive\"]', 110, 1, 'active', '2026-04-23 11:35:44'),
(19, 'Sunglasses', 'sunglasses', 3, 89.99, NULL, 'UV protection sunglasses with polarized lenses. Stylish and functional.', 'sunglasses1.png', 'sunglasses1-hover.png', '[\"sunglasses1.png\",\"sunglasses1-2.png\"]', '[\"One Size\"]', '[\"Black\",\"Tortoise\",\"Gold\"]', 60, 0, 'active', '2026-04-23 11:35:44'),
(20, 'Sandals', 'sandals', 4, 49.99, 34.99, 'Comfortable flat sandals with cushioned footbed. Perfect for summer.', 'sandals1.png', 'sandals1-hover.png', '[\"sandals1.png\",\"sandals1-2.png\"]', '[\"5\",\"6\",\"7\",\"8\",\"9\",\"10\",\"11\"]', '[\"Brown\",\"Black\",\"Tan\"]', 120, 0, 'active', '2026-04-23 11:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_paypal_txn` (`paypal_transaction_id`),
  ADD KEY `idx_payoneer_id` (`payoneer_payment_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_review` (`product_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

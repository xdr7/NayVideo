-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 01, 2025 at 05:03 AM
-- Server version: 8.0.18
-- PHP Version: 7.3.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `naysvideo`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@naysvideo.com', 'admin', '2025-08-31 12:05:18'),
(2, 'hamid', '$2y$10$jJYkgbbsbvR3nYJgHkwy0eilkVNib3dZHsZ0iMrepwgHS7ajlF7bS', 'hamid@gmail.com', 'user', '2025-09-01 01:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `filename` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `thumbnail` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `views` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `title`, `description`, `filename`, `thumbnail`, `category`, `tags`, `user_id`, `views`, `created_at`) VALUES
(5, 'Database', '-', '68b44c32eaed5.mp4', '68b44c32eb122.png', 'Database', 'web, database', 1, 0, '2025-08-31 13:20:50'),
(6, 'programming', '-', '68b44c720b459.mp4', '68b44c720dc01.png', 'Programming', 'web', 1, 4, '2025-08-31 13:21:54'),
(7, 'Membuat Perintah Php dasar ', '-', '68b4e33fcddd9.mp4', '68b4e33fce022.jpg', 'Programming', 'ph, web', 1, 6, '2025-09-01 00:05:19'),
(8, 'Visual Studio', '-', '68b4e40ced8aa.mp4', '68b4e40cedb2c.png', 'Mobile Development', 'Mobile', 1, 3, '2025-09-01 00:08:44'),
(9, 'Belajar Dasar PHP', '-', '68b515c31a76b.mp4', '68b515c31aa49.jpg', 'Programming', 'php, web, ', 2, 1, '2025-09-01 03:40:51'),
(11, 'Mobile APP', '-', '68b5168f8e1ed.mp4', '68b5168f8e494.jpg', 'Mobile Development', 'Mobile development, android', 2, 1, '2025-09-01 03:44:15'),
(14, 'pemberitahuan ', 'SHO goes to bali ', '68b51a50edb5d.mp4', '68b51a50ede4d.jpeg', 'IT Education', '', 2, 3, '2025-09-01 04:00:16'),
(15, 'Membangun Aplikasi Mobile', '-', '68b51de108904.mp4', '68b51de108bc6.png', 'Mobile Development', 'mobile, web, app', 1, 2, '2025-09-01 04:15:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

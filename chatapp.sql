-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 13, 2026 at 07:33 PM
-- Server version: 12.2.2-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chatapp`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `msg_id` int(11) NOT NULL,
  `incoming_msg_id` int(255) NOT NULL,
  `outgoing_msg_id` int(255) NOT NULL,
  `msg` longtext NOT NULL,
  `Private` tinyint(1) NOT NULL DEFAULT 0,
  `end_time` int(11) DEFAULT NULL,
  `see` int(1) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `msg_pic_passw`
--

CREATE TABLE `msg_pic_passw` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Active` int(11) NOT NULL,
  `end_time` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `private_chat_active`
--

CREATE TABLE `private_chat_active` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `Active` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `private_chat_u2u`
--

CREATE TABLE `private_chat_u2u` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `to_user_id` int(11) NOT NULL,
  `Active` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `unique_id` int(255) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_code`
--

CREATE TABLE `user_login_code` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_code` varchar(6) NOT NULL,
  `end_time` int(11) NOT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_verification`
--

CREATE TABLE `user_verification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `confirm` int(1) DEFAULT NULL,
  `time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`msg_id`),
  ADD KEY `idx_messages_incoming_msg_id` (`incoming_msg_id`),
  ADD KEY `idx_messages_outgoing_msg_id` (`outgoing_msg_id`);

--
-- Indexes for table `msg_pic_passw`
--
ALTER TABLE `msg_pic_passw`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_pic_passw_user_id` (`user_id`);

--
-- Indexes for table `private_chat_active`
--
ALTER TABLE `private_chat_active`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_private_chat_active_user_id` (`user_id`);

--
-- Indexes for table `private_chat_u2u`
--
ALTER TABLE `private_chat_u2u`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_private_chat_u2u_pair` (`user_id`,`to_user_id`),
  ADD KEY `idx_private_chat_u2u_user_id` (`user_id`),
  ADD KEY `idx_private_chat_u2u_to_user_id` (`to_user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `unique_id` (`unique_id`);

--
-- Indexes for table `user_login_code`
--
ALTER TABLE `user_login_code`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_login_code_user_id` (`user_id`);

--
-- Indexes for table `user_verification`
--
ALTER TABLE `user_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `msg_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=232;

--
-- AUTO_INCREMENT for table `msg_pic_passw`
--
ALTER TABLE `msg_pic_passw`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `private_chat_active`
--
ALTER TABLE `private_chat_active`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `private_chat_u2u`
--
ALTER TABLE `private_chat_u2u`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_login_code`
--
ALTER TABLE `user_login_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `user_verification`
--
ALTER TABLE `user_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_incoming` FOREIGN KEY (`incoming_msg_id`) REFERENCES `users` (`unique_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_messages_outgoing` FOREIGN KEY (`outgoing_msg_id`) REFERENCES `users` (`unique_id`) ON UPDATE CASCADE;

--
-- Constraints for table `msg_pic_passw`
--
ALTER TABLE `msg_pic_passw`
  ADD CONSTRAINT `fk_msg_pic_passw_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`unique_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `private_chat_active`
--
ALTER TABLE `private_chat_active`
  ADD CONSTRAINT `fk_private_chat_active_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`unique_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `private_chat_u2u`
--
ALTER TABLE `private_chat_u2u`
  ADD CONSTRAINT `fk_private_chat_u2u_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`unique_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_private_chat_u2u_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`unique_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_login_code`
--
ALTER TABLE `user_login_code`
  ADD CONSTRAINT `fk_user_login_code_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`unique_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_verification`
--
ALTER TABLE `user_verification`
  ADD CONSTRAINT `user_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`unique_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

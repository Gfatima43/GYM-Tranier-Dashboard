-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 13, 2026 at 01:05 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym_trainer`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int NOT NULL,
  `firstName` varchar(100) DEFAULT NULL,
  `lastName` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `plan` enum('Weight Loss','Muscle Gain','Cardio','Flexibility','General Fitness') NOT NULL DEFAULT 'Weight Loss',
  `status` enum('Active','Inactive','Pending') NOT NULL DEFAULT 'Active',
  `progress` int DEFAULT '0',
  `sessions` int DEFAULT '0',
  `join_date` date DEFAULT (curdate()),
  `trainer_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `firstName`, `lastName`, `email`, `phone`, `plan`, `status`, `progress`, `sessions`, `join_date`, `trainer_id`, `user_id`) VALUES
(1, 'Ali', 'Khan', 'ali@gmail.com', '1245678900', 'Weight Loss', 'Active', 22, 6, '2026-02-24', 4, NULL),
(3, 'Safa', 'Ali', 'safa@gmail.com', '4839657948', 'Flexibility', 'Active', 10, 1, '2026-02-28', 6, NULL),
(4, 'Rahim', 'Khan', 'rahim@gmail.com', '24629767', 'Muscle Gain', 'Pending', 90, 4, '2026-02-28', 2, NULL),
(5, 'Sara', 'Hasmi', 'sara@gmail.com', '56476587697', 'Flexibility', 'Active', 69, 1, '2026-03-10', 4, NULL),
(6, 'Haris', 'Abro', 'h@gym.com', '5625472', 'Cardio', 'Active', 56, 3, '2026-03-10', 4, 16);

-- --------------------------------------------------------

--
-- Table structure for table `client_schedules`
--

CREATE TABLE `client_schedules` (
  `id` int NOT NULL,
  `client_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `MON` int DEFAULT '0',
  `TUE` int DEFAULT '0',
  `WED` int DEFAULT '0',
  `THU` int DEFAULT '0',
  `FRI` int DEFAULT '0',
  `SAT` int DEFAULT '0',
  `SUN` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `client_schedules`
--

INSERT INTO `client_schedules` (`id`, `client_id`, `plan_id`, `MON`, `TUE`, `WED`, `THU`, `FRI`, `SAT`, `SUN`) VALUES
(23, 3, 9, 0, 0, 0, 1, 1, 1, 0),
(28, 5, 9, 0, 0, 0, 0, 0, 1, 1),
(35, 6, 13, 0, 1, 1, 1, 1, 0, 0),
(40, 1, 14, 0, 0, 0, 1, 1, 1, 1),
(41, 4, 12, 1, 0, 1, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `experience_years` int DEFAULT '0',
  `bio` text,
  `status` enum('Active','Inactive') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`id`, `user_id`, `name`, `email`, `phone`, `specialization`, `experience_years`, `bio`, `status`) VALUES
(2, NULL, 'Gori', 'gori@gym.com', '123256787', 'Fitness', 10, '', 'Active'),
(4, 4, 'Dali', 'd@gym.com', '658946578465', 'Muscle', 20, '', 'Inactive'),
(5, 16, 'Haris', 'h@gym.com', '5625472', 'Weight', 6, '', 'Active'),
(6, 17, 'Imran Khan', 'i@gym.com', '257864838', 'Physical Fitness', 3, 'My career started after COVID', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `trainer_clients`
--

CREATE TABLE `trainer_clients` (
  `id` int NOT NULL,
  `trainer_id` int NOT NULL,
  `client_id` int NOT NULL,
  `assigned_date` date DEFAULT (curdate()),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `trainer_clients`
--

INSERT INTO `trainer_clients` (`id`, `trainer_id`, `client_id`, `assigned_date`, `status`) VALUES
(9, 6, 3, '2026-03-10', 'active'),
(10, 2, 6, '2026-03-10', 'active'),
(11, 2, 4, '2026-03-10', 'active'),
(14, 4, 1, '2026-03-12', 'active'),
(15, 4, 6, '2026-03-12', 'active'),
(16, 4, 5, '2026-03-12', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','trainer','client') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`) VALUES
(1, 'Ali', 'a@gmail.com', '', '$2y$10$qzZJgqWPWlpwEadjrjH1q.J48YXBJbNzvWpvquD3hOvkhQVaRLyfS', 'admin'),
(2, 'Ball', 'b@gmail.com', '', '$2y$10$sKF7y/sh5jLx3RENy.MM3edrTj5RvccQOW3oBAv8sSBUDp2HDx3ES', 'client'),
(3, 'Cato', 'c@gmail.com', '', '$2y$10$GFQVhzSl.NDQAW.kF6P2feR1eU3jDHfW9BSHvcaDOCcD894CzQKt.', 'admin'),
(4, 'Dali', 'd@gym.com', '', '$2y$10$FE31j1c9dAec9tTKfhTyN.yhE9fwu9jWTRo3qeRju83i03FMkW21G', 'trainer'),
(7, 'Emily', 'e@gmail.com', '1234567890', '$2y$10$r2uKj6AhRh4cCZnuAq1ZGujqsM0Y3vbaB17VcJIXDta5lL0ebVqfS', 'admin'),
(8, 'Fasial', 'f@gmail.com', '12456789', '$2y$10$.22ddXvcWOBP7W3djBtYveHTcCWkr7dtv4mrnEem.SqXoYOlxdDTK', 'admin'),
(9, 'Goli', 'g@gym.com', '12345678990', '$2y$10$QDfliGk67cA.Veo8r5wMCOLKMNoJC9lw21THynTSs5Ul7ZeY7d.TW', 'trainer'),
(16, 'Haris', 'h@gym.com', '5625472', '$2y$10$bzaUtsZy6CIofToWRIwLBOVPX5dyXhWDWpoekE0E5NNwvxOMZkhJW', 'client'),
(17, 'Imran Khan', 'i@gym.com', '257864838', '$2y$10$vXVhdkUkARKIHk1LrbICMep3VqGaPFy.TfLRIe4qT84s7DbJUdNru', 'trainer');

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `id` int NOT NULL,
  `plan_id` int NOT NULL,
  `workout_name` varchar(150) NOT NULL,
  `sets` int NOT NULL DEFAULT '3',
  `set_counter` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workouts`
--

INSERT INTO `workouts` (`id`, `plan_id`, `workout_name`, `sets`, `set_counter`) VALUES
(1, 9, 'Jumping', 3, 5),
(2, 10, 'Legs', 3, 6),
(3, 10, 'Shoulder', 5, 4),
(8, 12, 'Bicep', 3, 3),
(9, 12, 'Sholder', 3, 3),
(10, 12, 'Hit Ness', 3, 5),
(12, 14, 'Belly', 3, 3),
(16, 13, 'Bicep', 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `workout_plans`
--

CREATE TABLE `workout_plans` (
  `id` int NOT NULL,
  `plan_name` varchar(150) NOT NULL,
  `trainer_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `workout_plans`
--

INSERT INTO `workout_plans` (`id`, `plan_name`, `trainer_id`) VALUES
(9, 'Cardio', 1),
(10, 'Muscle', 1),
(12, 'Physical Fitness', 1),
(13, 'Fitness', 4),
(14, 'Weight Loss', 4);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_plan` (`plan`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `fk_client_user` (`user_id`);

--
-- Indexes for table `client_schedules`
--
ALTER TABLE `client_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_id` (`client_id`),
  ADD KEY `idx_plan_id` (`plan_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `trainer_clients`
--
ALTER TABLE `trainer_clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`trainer_id`,`client_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plan_id` (`plan_id`);

--
-- Indexes for table `workout_plans`
--
ALTER TABLE `workout_plans`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `client_schedules`
--
ALTER TABLE `client_schedules`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `trainer_clients`
--
ALTER TABLE `trainer_clients`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `workouts`
--
ALTER TABLE `workouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `workout_plans`
--
ALTER TABLE `workout_plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE ON UPDATE CASCADE SET NULL,
  ADD CONSTRAINT `fk_client_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `client_schedules`
--
ALTER TABLE `client_schedules`
  ADD CONSTRAINT `fk_schedule_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_schedule_plan` FOREIGN KEY (`plan_id`) REFERENCES `workout_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainers`
--
ALTER TABLE `trainers`
  ADD CONSTRAINT `fk_trainer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trainer_clients`
--
ALTER TABLE `trainer_clients`
  ADD CONSTRAINT `trainer_clients_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainer_clients_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `fk_workout_plan` FOREIGN KEY (`plan_id`) REFERENCES `workout_plans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

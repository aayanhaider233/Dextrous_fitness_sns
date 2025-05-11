-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2025 at 07:00 AM
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
-- Database: `fitness_sns`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned`
--

CREATE TABLE `assigned` (
  `trainee_email` varchar(100) NOT NULL,
  `trainer_email` varchar(100) NOT NULL,
  `start_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favworkout`
--

CREATE TABLE `favworkout` (
  `user_email` varchar(100) NOT NULL,
  `workout_name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favworkout`
--

INSERT INTO `favworkout` (`user_email`, `workout_name`) VALUES
('1oakil@gmail.com', 'Arnold Press'),
('1oakil@gmail.com', 'Barbell Row'),
('a@a.com', 'Squat'),
('a@b.com', 'Barbell Row');

-- --------------------------------------------------------

--
-- Table structure for table `followerlist`
--

CREATE TABLE `followerlist` (
  `user_email` varchar(100) NOT NULL,
  `follower` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `followerlist`
--

INSERT INTO `followerlist` (`user_email`, `follower`) VALUES
('a@a.com', 'a@b.com'),
('a@a.com', 'aayan@haider.com'),
('a@a.com', 'aayanaayan@gmla.com'),
('aayan@haider.com', '1oakil@gmail.com'),
('adreed.saadad.hasan@g.bracu.ac.bd', 'a@a.com'),
('adreed.saadad.hasan@g.bracu.ac.bd', 'a@b.com');

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `follower_email` varchar(100) NOT NULL,
  `followed_email` varchar(100) NOT NULL,
  `follow_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`follower_email`, `followed_email`, `follow_date`) VALUES
('1oakil@gmail.com', 'aayan@haider.com', '2025-05-10 22:38:15'),
('a@a.com', 'adreed.saadad.hasan@g.bracu.ac.bd', '2025-05-08 04:54:08'),
('a@b.com', 'a@a.com', '2025-05-08 14:59:48'),
('a@b.com', 'adreed.saadad.hasan@g.bracu.ac.bd', '2025-05-08 15:00:58'),
('aayan@haider.com', 'a@a.com', '2025-05-08 14:30:39'),
('aayanaayan@gmla.com', 'a@a.com', '2025-05-11 10:38:09');

-- --------------------------------------------------------

--
-- Table structure for table `gym`
--

CREATE TABLE `gym` (
  `founding_date` date DEFAULT NULL,
  `owner` varchar(60) DEFAULT NULL,
  `member_count` int(11) DEFAULT 0,
  `gym_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gym`
--

INSERT INTO `gym` (`founding_date`, `owner`, `member_count`, `gym_name`) VALUES
('2018-11-01', 'Carlos Ray', 95, 'Flex Haven'),
('2016-05-22', 'Frank West', 220, 'Grind Zone'),
('2015-06-12', 'Alice Kim', 150, 'Iron Temple'),
('2010-03-20', 'Bob Lee', 200, 'Muscle Forge'),
('2012-07-09', 'Ella Stone', 180, 'Powerhouse Fit'),
('2020-01-15', 'Dana White', 300, 'Titan Gym');

-- --------------------------------------------------------

--
-- Table structure for table `gymhours`
--

CREATE TABLE `gymhours` (
  `opening_hour` varchar(50) NOT NULL,
  `gym_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gymlocations`
--

CREATE TABLE `gymlocations` (
  `location` varchar(100) NOT NULL,
  `gym_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gymlocations`
--

INSERT INTO `gymlocations` (`location`, `gym_name`) VALUES
('Austin, TX', 'Flex Haven'),
('Seattle, WA', 'Grind Zone'),
('Brooklyn, NY', 'Iron Temple'),
('New York, NY', 'Iron Temple'),
('Los Angeles, CA', 'Muscle Forge'),
('San Francisco, CA', 'Muscle Forge'),
('Miami, FL', 'Powerhouse Fit'),
('Chicago, IL', 'Titan Gym'),
('Houston, TX', 'Titan Gym');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `likes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `user_email`, `content`, `image_path`, `created_at`, `likes`) VALUES
(12, '102oakil@gmail.com', 'dfgdfgfdasg', NULL, '2025-05-07 11:56:10', 0),
(13, '102oakil@gmail.com', 'post 1', NULL, '2025-05-07 16:53:28', 0),
(14, '102oakil@gmail.com', 'post 2', NULL, '2025-05-07 16:53:35', 0),
(15, '102oakil@gmail.com', 'post 3', NULL, '2025-05-07 16:53:41', 0),
(16, '102oakil@gmail.com', 'post 4', NULL, '2025-05-07 16:53:47', 0),
(17, '102oakil@gmail.com', 'post 5', NULL, '2025-05-07 16:53:53', 0),
(18, '102oakil@gmail.com', 'post 6', NULL, '2025-05-07 16:54:01', 0),
(31, 'adreed.saadad.hasan@g.bracu.ac.bd', '#newpfp', '89f8853cbb87739c47240ce92371d7d5.jpg', '2025-05-08 04:30:09', 0),
(32, 'adreed.saadad.hasan@g.bracu.ac.bd', 'new pr', 'da9b579e48874e2c420b908fe77a18bf.jpg', '2025-05-08 04:32:58', 0),
(33, 'adreed.saadad.hasan@g.bracu.ac.bd', 'RAW FIT SQUAD GYM 🔥🔥🔥', 'b9e83248ead2ae90d4e43a71c11153da.jpg', '2025-05-08 04:34:32', 0),
(34, 'a@a.com', 'mhmm', '3f0cc207e41b9bea8ffa1407992ed6a0.jpg', '2025-05-08 04:39:49', 1),
(35, 'a@a.com', 'looking for ambitious people to build', NULL, '2025-05-08 04:45:36', 0),
(36, 'adreed.saadad.hasan@g.bracu.ac.bd', 'too lazy to hit the gym today', NULL, '2025-05-08 04:49:12', 1),
(37, 'a@a.com', 'my newest project', '93bf69250fb16afe5f57275eb6501b77.jpg', '2025-05-08 05:02:34', 2),
(38, 'a@b.com', 'test', NULL, '2025-05-08 14:56:27', 0),
(42, '1oakil@gmail.com', 'test', NULL, '2025-05-11 00:30:42', 0),
(43, '1oakil@gmail.com', '1', NULL, '2025-05-11 00:31:37', 0),
(44, '1oakil@gmail.com', '2', NULL, '2025-05-11 00:31:39', 0),
(45, '1oakil@gmail.com', '3', NULL, '2025-05-11 00:31:42', 0),
(46, '1oakil@gmail.com', '12', NULL, '2025-05-11 00:31:49', 0),
(49, 'aayan@aayan.com', 'assasas', NULL, '2025-05-11 01:20:20', 0);

-- --------------------------------------------------------

--
-- Table structure for table `post_comments`
--

CREATE TABLE `post_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_comments`
--

INSERT INTO `post_comments` (`comment_id`, `post_id`, `user_email`, `comment_text`, `created_at`) VALUES
(8, 36, 'a@a.com', 'you cant be saying that bro', '2025-05-08 04:49:50'),
(9, 37, 'aayan@haider.com', 'nice', '2025-05-08 14:26:09'),
(10, 34, 'aayan@haider.com', 'mmmmmm', '2025-05-08 14:26:20');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_email`, `created_at`) VALUES
(22, 36, 'a@a.com', '2025-05-07 17:49:52'),
(23, 37, 'a@a.com', '2025-05-07 18:03:01'),
(25, 37, 'aayan@haider.com', '2025-05-08 08:26:06'),
(26, 34, 'aayan@haider.com', '2025-05-08 08:26:16');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `trainer_email` varchar(100) DEFAULT NULL,
  `review` text DEFAULT NULL,
  `date_of_review` date DEFAULT NULL,
  `gym_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainees`
--

CREATE TABLE `trainees` (
  `email` varchar(100) NOT NULL,
  `duration` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainees`
--

INSERT INTO `trainees` (`email`, `duration`) VALUES
('102oakil@gmail.com', 0),
('1oakil@gmail.com', 0),
('aayan@haider.com', 0),
('adreed.saadad.hasan@g.bracu.ac.bd', 0);

-- --------------------------------------------------------

--
-- Table structure for table `trainergym`
--

CREATE TABLE `trainergym` (
  `trainer_email` varchar(100) NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `gym_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `email` varchar(100) NOT NULL,
  `total_trainees` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`email`, `total_trainees`) VALUES
('a@a.com', 0),
('a@b.com', 0),
('aayan@aayan.com', 0),
('aayanaayan@gmla.com', 0);

-- --------------------------------------------------------

--
-- Table structure for table `usergym`
--

CREATE TABLE `usergym` (
  `user_email` varchar(100) NOT NULL,
  `start_date` date DEFAULT current_timestamp(),
  `membership_duration` int(11) DEFAULT NULL,
  `gym_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usergym`
--

INSERT INTO `usergym` (`user_email`, `start_date`, `membership_duration`, `gym_name`) VALUES
('102oakil@gmail.com', '2025-05-08', NULL, 'Iron Temple'),
('a@b.com', '2025-05-08', NULL, 'Grind Zone'),
('adreed.saadad.hasan@g.bracu.ac.bd', '2025-05-08', NULL, 'Grind Zone');

-- --------------------------------------------------------

--
-- Table structure for table `userreview`
--

CREATE TABLE `userreview` (
  `user_email` varchar(100) DEFAULT NULL,
  `review_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `fname` varchar(256) DEFAULT NULL,
  `lname` varchar(256) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `weight` float DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `location` varchar(50) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `username` varchar(30) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `position` varchar(20) DEFAULT NULL CHECK (`position` in ('trainer','trainee')),
  `profile_pic` varchar(255) DEFAULT 'default.jpg',
  `bio` varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`fname`, `lname`, `age`, `height`, `weight`, `gender`, `location`, `password`, `username`, `email`, `position`, `profile_pic`, `bio`) VALUES
('Oakil', 'Oakil', 1, 4, -3, 'Male', 'Basha', '$2y$10$wR13kFSfg/B5ChEwPpb00.WZkYieYPw9KWc9SdHSV37JTCw2GmIf2', 'oakil', '102oakil@gmail.com', 'Trainee', 'default.jpg', ''),
('Oakil1', 'Oakil1', 1, 1, 1, 'Male', 'Basha', '$2y$10$is8V/dCsYoicQJ0/CRb6seoYtfVUl/pJkgzCFCJ/VBq2V0/1df9LO', 'Oakil1', '1oakil@gmail.com', 'Trainee', 'default.jpg', 'bio test\r\nline1\r\nline5\r\nline3'),
('Adib', 'Bida', 22, 160, 100, 'Male', 'Dhaka', '$2y$10$7l69wFR.Y59H1F6LG5MVN.75/VkOWm49feRO5UiXSQLSJuUrnPdaS', 'adib', 'a@a.com', 'Trainer', '681b9a92dfc39_profile.jpeg', 'new trainer looking for trainees'),
('A', 'B', 24, 170, 80, 'Male', 'Sadas', '$2y$10$y36q1tQwhx7uy3ONW89OTOAbQYOkjKcH/zu7mRqBYYBLLvrncQCg2', 'adr', 'a@b.com', 'Trainer', 'default.jpg', ''),
('Aayan', 'Haider', 123, 9907, 324, 'Male', '2', '$2y$10$VokZpaJxXH0cPaTHMz1XZ.Zjwsln.2ttxpxRx/97OrpiwUauRB.k.', 'aayanhaider1234', 'aayan@aayan.com', 'Trainer', 'default.jpg', ''),
('Aayan ', 'Haider', 22, 177, 88, 'Male', 'Dhk', '$2y$10$fekNUFsynySyIfVkeQVAveSpo7X/nh3spw7lGTBd8HGdhevKWoJOq', 'aayanhaider', 'aayan@haider.com', 'Trainee', '6814d557edd72_profile.jpeg', 'test bio'),
('Aayan ', '彗星', 1773, 1872, 600, 'Male', 'Real', '$2y$10$kRDjK2yA6d1Xr2JqDkrCuuiRNKlD3P1e1RpMLm9YlKmwoQ0vsMUUO', 'すい', 'aayanaayan@gmla.com', 'Trainer', 'default.jpg', ''),
('Adreed saadad', 'Hasan', 23, 171, 86, 'Male', 'Dhaka', '$2y$10$PpuOVPHSg35z5kdC1NAdz.44ZFdTfuBojEXjEKHa2OGp.Nll1VmFO', 'adruid', 'adreed.saadad.hasan@g.bracu.ac.bd', 'Trainee', 'ea8133482097e4a45d6b32cfd4fbc083.jpeg', 'I own this website');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.position = 'Trainer' THEN
    	INSERT INTO trainers (email) VALUES (NEW.email);
    ELSEIF NEW.position = 'Trainee' THEN	
    	INSERT INTO trainees (email) VALUES (NEW.email);
	END IF;

	
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `workouts`
--

CREATE TABLE `workouts` (
  `name` varchar(60) NOT NULL,
  `muscle_group` varchar(50) DEFAULT NULL,
  `rec_set_reps` varchar(10) DEFAULT NULL,
  `best_pr` int(11) DEFAULT NULL,
  `community_avg` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workouts`
--

INSERT INTO `workouts` (`name`, `muscle_group`, `rec_set_reps`, `best_pr`, `community_avg`) VALUES
('Arnold Press', 'Shoulders', '3x10', 30, 20),
('Barbell Row', 'Back', '4x10', 90, 70),
('Bench Press', 'Chest', '4x8', 100, 75),
('Cable Pushdown', 'Triceps', '3x12', 40, 30),
('Calf Raise', 'Calves', '4x15', 80, 60),
('Chest Fly', 'Chest', '3x15', 30, 20),
('Deadlift', 'Back', '4x6', 180, 140),
('Dumbbell Curl', 'Biceps', '3x12', 20, 15),
('Face Pull', 'Shoulders', '3x15', 25, 20),
('Hammer Curl', 'Biceps', '3x12', 22, 16),
('Hanging Leg Raise', 'Core', '3x15', 25, 15),
('Hip Thrust', 'Glutes', '4x10', 120, 90),
('Lat Pulldown', 'Back', '3x12', 80, 60),
('Leg Press', 'Legs', '4x12', 300, 220),
('Lunges', 'Legs', '3x12/leg', 40, 30),
('Overhead Press', 'Shoulders', '3x8', 60, 45),
('Plank', 'Core', '3x60s', 3, 2),
('Pull-Ups', 'Back', '3xMax', 20, 12),
('Squat', 'Legs', '4x10', 150, 110),
('Tricep Dip', 'Triceps', '3xMax', 25, 15);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned`
--
ALTER TABLE `assigned`
  ADD PRIMARY KEY (`trainee_email`,`trainer_email`),
  ADD KEY `trainer_email` (`trainer_email`);

--
-- Indexes for table `favworkout`
--
ALTER TABLE `favworkout`
  ADD PRIMARY KEY (`user_email`,`workout_name`),
  ADD KEY `workout_name` (`workout_name`);

--
-- Indexes for table `followerlist`
--
ALTER TABLE `followerlist`
  ADD PRIMARY KEY (`user_email`,`follower`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`follower_email`,`followed_email`),
  ADD KEY `followed_email` (`followed_email`);

--
-- Indexes for table `gym`
--
ALTER TABLE `gym`
  ADD PRIMARY KEY (`gym_name`);

--
-- Indexes for table `gymhours`
--
ALTER TABLE `gymhours`
  ADD PRIMARY KEY (`gym_name`,`opening_hour`);

--
-- Indexes for table `gymlocations`
--
ALTER TABLE `gymlocations`
  ADD PRIMARY KEY (`gym_name`,`location`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_post_id` (`post_id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_post_unique` (`post_id`,`user_email`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainer_email` (`trainer_email`),
  ADD KEY `reviews_ibfk_2` (`gym_name`);

--
-- Indexes for table `trainees`
--
ALTER TABLE `trainees`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `trainergym`
--
ALTER TABLE `trainergym`
  ADD PRIMARY KEY (`trainer_email`),
  ADD KEY `trainergym_ibfk_2` (`gym_name`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `usergym`
--
ALTER TABLE `usergym`
  ADD PRIMARY KEY (`user_email`),
  ADD KEY `usergym_ibfk_2` (`gym_name`);

--
-- Indexes for table `userreview`
--
ALTER TABLE `userreview`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned`
--
ALTER TABLE `assigned`
  ADD CONSTRAINT `assigned_ibfk_1` FOREIGN KEY (`trainee_email`) REFERENCES `trainees` (`email`),
  ADD CONSTRAINT `assigned_ibfk_2` FOREIGN KEY (`trainer_email`) REFERENCES `trainers` (`email`);

--
-- Constraints for table `favworkout`
--
ALTER TABLE `favworkout`
  ADD CONSTRAINT `favworkout_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `favworkout_ibfk_2` FOREIGN KEY (`workout_name`) REFERENCES `workouts` (`name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `followerlist`
--
ALTER TABLE `followerlist`
  ADD CONSTRAINT `followerlist_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_email`) REFERENCES `users` (`email`),
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`followed_email`) REFERENCES `users` (`email`);

--
-- Constraints for table `gymhours`
--
ALTER TABLE `gymhours`
  ADD CONSTRAINT `gymhours_ibfk_1` FOREIGN KEY (`gym_name`) REFERENCES `gym` (`gym_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `gymlocations`
--
ALTER TABLE `gymlocations`
  ADD CONSTRAINT `gymlocations_ibfk_1` FOREIGN KEY (`gym_name`) REFERENCES `gym` (`gym_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `fk_post_comments_user_email` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_post_id` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`trainer_email`) REFERENCES `trainers` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`gym_name`) REFERENCES `gym` (`gym_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainees`
--
ALTER TABLE `trainees`
  ADD CONSTRAINT `trainees_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainergym`
--
ALTER TABLE `trainergym`
  ADD CONSTRAINT `trainergym_ibfk_1` FOREIGN KEY (`trainer_email`) REFERENCES `trainers` (`email`),
  ADD CONSTRAINT `trainergym_ibfk_2` FOREIGN KEY (`gym_name`) REFERENCES `gym` (`gym_name`);

--
-- Constraints for table `trainers`
--
ALTER TABLE `trainers`
  ADD CONSTRAINT `trainers_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `usergym`
--
ALTER TABLE `usergym`
  ADD CONSTRAINT `usergym_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `usergym_ibfk_2` FOREIGN KEY (`gym_name`) REFERENCES `gym` (`gym_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `userreview`
--
ALTER TABLE `userreview`
  ADD CONSTRAINT `userreview_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`),
  ADD CONSTRAINT `userreview_ibfk_2` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`);

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `increment_duration_daily` ON SCHEDULE EVERY 1 DAY STARTS '2025-04-29 01:43:04' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE trainees
  SET duration = duration + 1$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

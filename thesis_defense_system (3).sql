-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2026 at 12:52 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `thesis_defense_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `advisers`
--

CREATE TABLE `advisers` (
  `adviser_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `advisers`
--

INSERT INTO `advisers` (`adviser_id`, `user_id`, `department`) VALUES
(1, 5, 'it'),
(2, 7, 'Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `defense_requests`
--

CREATE TABLE `defense_requests` (
  `request_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `defense_type` enum('Proposal','Pre-Oral','Final') DEFAULT 'Proposal',
  `requested_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Scheduled') DEFAULT 'Pending',
  `adviser_comments` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `defense_requests`
--

INSERT INTO `defense_requests` (`request_id`, `project_id`, `defense_type`, `requested_date`, `preferred_time`, `reason`, `status`, `adviser_comments`, `submitted_at`) VALUES
(1, 2, 'Proposal', '2026-10-30', '17:00:00', 'sana maka pass', 'Scheduled', NULL, '2026-10-23 07:25:40'),
(2, 3, 'Pre-Oral', '2026-10-31', '07:30:00', 'sana maka pass', 'Rejected', NULL, '2026-10-23 07:49:01'),
(3, 4, 'Proposal', '2026-10-29', '15:59:00', 'okie', 'Scheduled', NULL, '2026-10-23 07:59:39'),
(4, 5, 'Proposal', '2026-10-28', '09:00:00', 'shet', 'Scheduled', NULL, '2026-10-23 12:16:19'),
(5, 6, 'Pre-Oral', '2026-10-28', '10:40:00', 'awray', 'Approved', 'tangina', '2026-10-23 12:19:16'),
(6, 7, 'Proposal', '2026-10-28', '10:24:00', 'k', 'Approved', 'pwede to', '2026-10-23 12:24:37'),
(7, 8, 'Proposal', '2026-11-01', '08:00:00', 'k', 'Scheduled', 'bigay mo sakin saber', '2026-10-24 11:21:25'),
(8, 9, 'Proposal', '2026-11-01', '08:00:00', 'k', 'Scheduled', 'HAHAHAHA', '2026-10-24 11:55:21'),
(9, 10, 'Final', '2026-11-03', '00:00:00', 'Testing defense request workflow.', 'Scheduled', 'Please review your documentation and make the necessary revisions before your defense.', '2026-10-24 13:58:07'),
(10, 11, 'Final', '2026-11-07', '09:08:00', 'ano', 'Scheduled', 'wise tago mo si venus', '2026-10-27 13:09:06'),
(11, 12, 'Pre-Oral', '2026-10-28', '13:07:00', 'Test', 'Scheduled', '', '2026-10-28 13:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `defense_schedules`
--

CREATE TABLE `defense_schedules` (
  `schedule_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `defense_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `defense_schedules`
--

INSERT INTO `defense_schedules` (`schedule_id`, `request_id`, `room_id`, `defense_date`, `start_time`, `end_time`, `status`) VALUES
(1, 1, 1, '2026-10-30', '17:40:00', '19:40:00', 'Scheduled'),
(2, 3, 1, '2026-10-28', '09:00:00', '10:00:00', 'Scheduled'),
(3, 4, 1, '2026-10-28', '10:30:00', '11:30:00', 'Completed'),
(4, 7, 1, '2026-11-01', '08:00:00', '09:00:00', 'Scheduled'),
(5, 8, 1, '2026-11-02', '09:08:00', '11:08:00', 'Scheduled'),
(6, 9, 1, '2026-11-05', '13:00:00', '14:00:00', 'Scheduled'),
(7, 10, 1, '2026-10-27', '21:32:00', '21:35:00', 'Completed'),
(8, 11, 1, '2026-11-18', '09:28:00', '09:34:00', 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `panel_id` int(11) DEFAULT NULL,
  `rating` decimal(5,2) NOT NULL,
  `comments` text DEFAULT NULL,
  `recommendation` enum('Pass','Revise','Fail') DEFAULT 'Revise',
  `evaluated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`evaluation_id`, `schedule_id`, `panel_id`, `rating`, `comments`, `recommendation`, `evaluated_at`) VALUES
(1, 7, 4, 85.00, 'Good presentation. Some revisions are needed.', 'Revise', '2026-10-28 12:36:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 6, 'Your adviser has added comments to your defense request for: kim to', 0, '2026-10-27 10:41:09'),
(2, 6, 'Your adviser has added comments to your defense request for: motibo', 0, '2026-10-27 10:48:56'),
(3, 6, 'Your adviser approved your defense request for: bigay mo saber patayin ko si wise lvl 4', 0, '2026-10-27 13:29:07'),
(4, 6, 'Your adviser approved your defense request for: TEST_01', 0, '2026-10-28 13:11:40');

-- --------------------------------------------------------

--
-- Table structure for table `panel_assignments`
--

CREATE TABLE `panel_assignments` (
  `assignment_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `panel_id` int(11) NOT NULL,
  `panel_role` enum('Chair','Member','Secretary') DEFAULT 'Member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panel_assignments`
--

INSERT INTO `panel_assignments` (`assignment_id`, `schedule_id`, `panel_id`, `panel_role`) VALUES
(1, 1, 1, 'Chair'),
(2, 4, 1, 'Member'),
(3, 2, 1, 'Chair'),
(4, 4, 3, 'Member'),
(5, 2, 2, 'Secretary'),
(6, 2, 3, 'Member'),
(7, 1, 2, 'Secretary'),
(8, 4, 2, 'Member'),
(9, 7, 4, 'Member'),
(10, 8, 4, 'Member');

-- --------------------------------------------------------

--
-- Table structure for table `panel_members`
--

CREATE TABLE `panel_members` (
  `panel_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `panel_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `specialization` varchar(150) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panel_members`
--

INSERT INTO `panel_members` (`panel_id`, `user_id`, `panel_name`, `department`, `specialization`, `is_active`) VALUES
(1, NULL, 'Alex Reyes', 'Information Technology', 'Web Development', 1),
(2, NULL, 'Maria Santos', 'Information Technology', 'Database Systems', 1),
(3, NULL, 'Daniel Cruz', 'Computer Science', 'Systems Analysis', 1),
(4, 8, 'Grace Buban', 'Information Technology', 'Web Developer', 1);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `project_type` enum('Thesis','Capstone') DEFAULT 'Capstone',
  `status` enum('In Progress','Ready for Defense','Completed') DEFAULT 'Ready for Defense',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `review_status` enum('Pending Review','Under Review','Revision Required','Approved') NOT NULL DEFAULT 'Pending Review',
  `adviser_feedback` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`project_id`, `student_id`, `adviser_id`, `title`, `project_type`, `status`, `created_at`, `file_name`, `file_path`, `review_status`, `adviser_feedback`, `reviewed_at`) VALUES
(1, 3, 2, 'tds', 'Thesis', 'Ready for Defense', '2026-10-23 05:04:49', NULL, NULL, 'Pending Review', NULL, NULL),
(2, 3, 2, 'thesis defense system hahaha', 'Thesis', 'Ready for Defense', '2026-10-23 07:23:36', NULL, NULL, 'Pending Review', NULL, NULL),
(3, 3, 2, 'capstone ni kim', 'Capstone', 'Ready for Defense', '2026-10-23 07:48:06', NULL, NULL, 'Pending Review', NULL, NULL),
(4, 3, 2, 'hirap', 'Thesis', 'Ready for Defense', '2026-10-23 07:58:28', NULL, NULL, 'Pending Review', NULL, NULL),
(5, 3, 2, 'shet shet nakita ako', 'Thesis', 'Ready for Defense', '2026-10-23 12:14:33', NULL, NULL, 'Pending Review', NULL, NULL),
(6, 3, 2, 'tangina lala', 'Thesis', 'Ready for Defense', '2026-10-23 12:18:23', NULL, NULL, 'Pending Review', NULL, NULL),
(7, 3, 2, 'pwede to gago', 'Thesis', 'Ready for Defense', '2026-10-23 12:23:56', NULL, NULL, 'Pending Review', NULL, NULL),
(8, 3, 2, 'kim to', 'Thesis', 'Ready for Defense', '2026-10-24 11:20:08', NULL, NULL, 'Pending Review', NULL, NULL),
(9, 3, 2, 'motibo', 'Thesis', 'Ready for Defense', '2026-10-24 11:54:24', NULL, NULL, 'Pending Review', NULL, NULL),
(10, 3, 2, 'This defense already has a secretary.', 'Capstone', 'Ready for Defense', '2026-10-24 13:33:05', NULL, NULL, 'Pending Review', NULL, NULL),
(11, 3, 2, 'bigay mo saber patayin ko si wise lvl 4', 'Thesis', 'Ready for Defense', '2026-10-27 13:07:59', NULL, NULL, 'Pending Review', NULL, NULL),
(12, 3, 2, 'TEST_01', 'Thesis', 'Ready for Defense', '2026-10-28 13:05:36', NULL, NULL, 'Pending Review', NULL, NULL),
(13, 3, 2, 'test file upload', 'Capstone', 'In Progress', '2026-10-29 11:43:26', 'Argggghhhhh2.docx', 'uploads/project_files/fc463dd08391d8653a6c61f7dc36a35e.docx', 'Under Review', 'di ko knows', '2026-10-29 20:30:57');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 10,
  `location` varchar(150) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_name`, `capacity`, `location`, `is_active`) VALUES
(1, 'Room 204', 38, 'Anex 2', 1);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_number` varchar(30) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `student_number`, `course`) VALUES
(1, 3, '26-000', 'bsit'),
(2, 4, '26-002', 'bsit'),
(3, 6, 'TEST2026001', 'BSIT');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Student','Adviser','Admin','Panel Member') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'kimmmmmmm', 'admin', '$2y$10$5icMc1b1IC7qMFZ42qzOx.smCVPvX.Rb9Uwtyvq8dW.t1cEHMZaii', 'Admin', '2026-10-22 06:01:52'),
(3, 'student', 'student1', '$2y$10$AAJgmjgkjMWZyaz1EaGHSOanyUHp4uggWVQzixf7/kAIcBeKQU.Qy', 'Student', '2026-10-23 03:24:07'),
(4, 'student2', 'student02', '$2y$10$9U/9WXwV25cMJtuMtsDBq.QuJtgdLuE98EeW1Csv5JZJcBOjJ1I.G', 'Student', '2026-10-23 03:34:04'),
(5, 'adviser', 'adviser1', '$2y$10$fVaUA7VNtVV1OZEeU9MY4uvJsg5DAXijO7y.nvK42Vvv6KbeLYS4G', 'Adviser', '2026-10-23 04:03:21'),
(6, 'Test Student', 'student01', '$2y$10$tXAdS1JmrF8U5GpPbVNDyO1Ig3ADjCqfzm/n0kNvoZ3ghu8Z1Nkca', 'Student', '2026-10-23 04:40:26'),
(7, 'Test Adviser', 'adviser01', '$2y$10$wVEOcEFzAp.SzF6TJN0BHOJWbZtG552inpAOE79CH2MUuOo25.MtK', 'Adviser', '2026-10-23 04:42:12'),
(8, 'Grace Buban', 'Grace', '$2y$10$Y8GsVoBfLSByYZOeRj.Suuc9r91EHjcnMQWd9C2OyEOjeSlv0A6wi', 'Panel Member', '2026-10-27 14:44:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advisers`
--
ALTER TABLE `advisers`
  ADD PRIMARY KEY (`adviser_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `defense_requests`
--
ALTER TABLE `defense_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `idx_request_status_date` (`status`,`requested_date`);

--
-- Indexes for table `defense_schedules`
--
ALTER TABLE `defense_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `idx_schedule_room_date` (`room_id`,`defense_date`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `schedule_id` (`schedule_id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `panel_assignments`
--
ALTER TABLE `panel_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `unique_panel` (`schedule_id`,`panel_id`),
  ADD KEY `panel_id` (`panel_id`);

--
-- Indexes for table `panel_members`
--
ALTER TABLE `panel_members`
  ADD PRIMARY KEY (`panel_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `adviser_id` (`adviser_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `student_number` (`student_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advisers`
--
ALTER TABLE `advisers`
  MODIFY `adviser_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `defense_requests`
--
ALTER TABLE `defense_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `defense_schedules`
--
ALTER TABLE `defense_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `panel_assignments`
--
ALTER TABLE `panel_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `panel_members`
--
ALTER TABLE `panel_members`
  MODIFY `panel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advisers`
--
ALTER TABLE `advisers`
  ADD CONSTRAINT `advisers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `defense_requests`
--
ALTER TABLE `defense_requests`
  ADD CONSTRAINT `defense_requests_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `defense_schedules`
--
ALTER TABLE `defense_schedules`
  ADD CONSTRAINT `defense_schedules_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `defense_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `defense_schedules_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `defense_schedules` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`panel_id`) REFERENCES `panel_members` (`panel_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `panel_assignments`
--
ALTER TABLE `panel_assignments`
  ADD CONSTRAINT `panel_assignments_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `defense_schedules` (`schedule_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `panel_assignments_ibfk_2` FOREIGN KEY (`panel_id`) REFERENCES `panel_members` (`panel_id`);

--
-- Constraints for table `panel_members`
--
ALTER TABLE `panel_members`
  ADD CONSTRAINT `fk_panel_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`adviser_id`) REFERENCES `advisers` (`adviser_id`) ON DELETE SET NULL;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

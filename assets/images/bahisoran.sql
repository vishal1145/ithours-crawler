-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 20, 2017 at 05:07 AM
-- Server version: 10.1.13-MariaDB
-- PHP Version: 5.6.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bahisoran`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(32) COLLATE utf8mb4_turkish_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `status` enum('waiting_activation','active','inactive') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'waiting_activation',
  `password` varchar(40) COLLATE utf8mb4_turkish_ci NOT NULL,
  `auto_login_code` varchar(40) COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT '',
  `email_activation_code` varchar(52) COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT '',
  `password_reset_code` varchar(52) COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT '',
  `created_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `status`, `password`, `auto_login_code`, `email_activation_code`, `password_reset_code`, `created_time`) VALUES
(11, 'admin', 'istemci+admin@gmail.com', 'active', '92429d82a41e930486c6de5ebda9602d55c39986', '', '', '', '2017-09-06 05:17:38');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(32) COLLATE utf8mb4_turkish_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `status` enum('waiting_activation','active','inactive') COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT 'waiting_activation',
  `password` varchar(40) COLLATE utf8mb4_turkish_ci NOT NULL,
  `auto_login_code` varchar(40) COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT '',
  `email_activation_code` varchar(52) COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT '',
  `password_reset_code` varchar(52) COLLATE utf8mb4_turkish_ci NOT NULL DEFAULT '',
  `created_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `username`, `email`, `status`, `password`, `auto_login_code`, `email_activation_code`, `password_reset_code`, `created_time`) VALUES
(3, 'marsel', 'marselkozak@gmail.com', 'active', '442858d310751a9b6a2bc626120d01ef918a9142', '', '41c41fa2231ab2e361400206edeca00e5386ac6d-1505946828', '', '2017-09-20 00:33:48'),
(4, 'hasanhasan', 'hasan@hasan.com', 'active', 'c129b324aee662b04eccf68babba85851346dff9', '', 'df1a5ad2d31f275048d101ab19ff815cb3c9b458-1505959551', 'cadd5976d0731b785be2b70201692d5ee11f7c80-1505960231', '2017-09-20 04:05:51'),
(5, 'mrsolo', 'hasan@casan.com', 'waiting_activation', 'c129b324aee662b04eccf68babba85851346dff9', '', 'aed9e3672c62838fafa8a5c830303768d4a09c01-1505959799', '', '2017-09-20 04:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `online_members`
--

CREATE TABLE `online_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `last_activity` datetime NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `online_members`
--
ALTER TABLE `online_members`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

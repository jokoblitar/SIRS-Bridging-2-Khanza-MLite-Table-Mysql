-- phpMyAdmin SQL Dump
-- version 4.9.10
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 26, 2025 at 07:33 AM
-- Server version: 5.7.42-0ubuntu0.18.04.1-log
-- PHP Version: 7.2.24-0ubuntu0.18.04.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sik`
--

-- --------------------------------------------------------

--
-- Table structure for table `rsk_sirs_bed_updates`
--

CREATE TABLE `rsk_sirs_bed_updates` (
  `update_id` int(11) NOT NULL,
  `rsk_map_id_t_tt` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID referensi dari sistem RSK',
  `rsk_id_t_tt` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ID tempat tidur di SIRS',
  `rsk_ruang` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama ruangan',
  `rsk_jumlah_ruang` int(11) DEFAULT '0',
  `rsk_jumlah` int(11) DEFAULT '0',
  `rsk_terpakai` int(11) DEFAULT '0',
  `simrs_terpakai` int(11) DEFAULT '0',
  `rsk_terpakai_suspek` int(11) DEFAULT '0',
  `rsk_terpakai_konfirmasi` int(11) DEFAULT '0',
  `rsk_antrian` int(11) DEFAULT '0',
  `rsk_prepare` int(11) DEFAULT '0',
  `rsk_prepare_plan` int(11) DEFAULT '0',
  `rsk_covid` int(11) DEFAULT '0',
  `rsk_terpakai_dbd` int(11) DEFAULT '0',
  `rsk_terpakai_dbd_anak` int(11) DEFAULT '0',
  `rsk_jumlah_dbd` int(11) DEFAULT '0',
  `rsk_is_synced` tinyint(1) DEFAULT '0' COMMENT 'Flag sync ke SIRS',
  `rsk_last_sync_at` timestamp NULL DEFAULT NULL,
  `rsk_created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Operator yang membuat',
  `rsk_updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rsk_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rsk_sirs_bed_updates`
--

INSERT INTO `rsk_sirs_bed_updates` (`update_id`, `rsk_map_id_t_tt`, `rsk_id_t_tt`, `rsk_ruang`, `rsk_jumlah_ruang`, `rsk_jumlah`, `rsk_terpakai`, `simrs_terpakai`, `rsk_terpakai_suspek`, `rsk_terpakai_konfirmasi`, `rsk_antrian`, `rsk_prepare`, `rsk_prepare_plan`, `rsk_covid`, `rsk_terpakai_dbd`, `rsk_terpakai_dbd_anak`, `rsk_jumlah_dbd`, `rsk_is_synced`, `rsk_last_sync_at`, `rsk_created_by`, `rsk_updated_at`, `rsk_created_at`) VALUES
(1, 'K12', '34968463', 'Paviliun II KM 12', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-03-26 04:47:57', 'SIRS_API_UPDATE', '2025-07-26 00:09:02', '2025-07-25 04:59:30'),
;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rsk_sirs_bed_updates`
--
ALTER TABLE `rsk_sirs_bed_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `rsk_map_id_t_tt` (`rsk_map_id_t_tt`),
  ADD KEY `rsk_id_t_tt` (`rsk_id_t_tt`),
  ADD KEY `rsk_is_synced` (`rsk_is_synced`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rsk_sirs_bed_updates`
--
ALTER TABLE `rsk_sirs_bed_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

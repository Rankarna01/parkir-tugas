-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2025 at 05:06 PM
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
-- Database: `db_parkir`
--

-- --------------------------------------------------------

--
-- Table structure for table `jenis_kendaraan`
--

CREATE TABLE `jenis_kendaraan` (
  `id` int(11) NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `tarif_per_jam` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenis_kendaraan`
--

INSERT INTO `jenis_kendaraan` (`id`, `nama_jenis`, `tarif_per_jam`) VALUES
(1, 'Motor', 2000.00),
(2, 'Mobil', 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` int(11) NOT NULL,
  `plat_nomor` varchar(20) NOT NULL,
  `jenis` enum('mobil','motor') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `plat_nomor`, `jenis`, `foto`, `created_at`) VALUES
(1, 'BK 4032', 'mobil', NULL, '2025-10-18 21:00:34'),
(2, 'BK 2010', 'motor', NULL, '2025-10-18 22:37:18'),
(3, 'BK 1931', 'motor', NULL, '2025-10-18 22:44:58'),
(4, 'BK 1932', 'mobil', NULL, '2025-10-18 22:47:50'),
(5, 'BK1234', 'motor', NULL, '2025-10-18 22:50:17'),
(6, 'BK2341', 'motor', NULL, '2025-10-19 19:21:48'),
(7, 'DD2010', 'mobil', NULL, '2025-10-20 20:19:19'),
(8, 'BK 4548', 'motor', NULL, '2025-11-01 23:27:05'),
(9, '1002BK', 'motor', NULL, '2025-11-01 23:27:22'),
(10, 'BK 4547', 'motor', NULL, '2025-11-01 23:42:55'),
(11, 'BK5050', 'motor', NULL, '2025-11-16 12:39:21'),
(12, 'BK1010', 'motor', NULL, '2025-11-26 22:58:22');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_keuangan`
--

CREATE TABLE `laporan_keuangan` (
  `id` int(11) NOT NULL,
  `periode` varchar(50) NOT NULL,
  `total_pendapatan` text DEFAULT NULL,
  `total_pengeluaran` text DEFAULT NULL,
  `laba_bersih` text DEFAULT NULL,
  `tanggal_dibuat` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_keuangan`
--

INSERT INTO `laporan_keuangan` (`id`, `periode`, `total_pendapatan`, `total_pengeluaran`, `laba_bersih`, `tanggal_dibuat`) VALUES
(5, 'November 2025', 'd2R1Y05wb3YzamZiNVh5cFRYZlQyQT09', 'QWxUL3VqTm9yUE53TkJPWnI3WDJ5dz09', 'QnlwUHBlRklGbmJTMkQ2Y0RKeXpKdz09', '2025-11-24 20:16:50'),
(6, 'December 2025', 'QWxUL3VqTm9yUE53TkJPWnI3WDJ5dz09', 'QWxUL3VqTm9yUE53TkJPWnI3WDJ5dz09', 'QWxUL3VqTm9yUE53TkJPWnI3WDJ5dz09', '2025-11-26 23:01:59');

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran_operasional`
--

CREATE TABLE `pengeluaran_operasional` (
  `id` int(11) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `tanggal` date NOT NULL,
  `dibuat_oleh` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tarif_parkir`
--

CREATE TABLE `tarif_parkir` (
  `id` int(11) NOT NULL,
  `jenis_kendaraan` enum('mobil','motor') NOT NULL,
  `tarif_per_jam` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tarif_flat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tarif_parkir`
--

INSERT INTO `tarif_parkir` (`id`, `jenis_kendaraan`, `tarif_per_jam`, `tarif_flat`, `updated_at`) VALUES
(1, 'motor', 2000.00, 0.00, '2025-10-18 17:13:43'),
(2, 'mobil', 5000.00, 0.00, '2025-10-18 17:13:43');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_parkir`
--

CREATE TABLE `transaksi_parkir` (
  `id` int(11) NOT NULL,
  `id_kendaraan` int(11) NOT NULL,
  `kode_barcode` varchar(50) NOT NULL,
  `waktu_masuk` datetime NOT NULL,
  `waktu_keluar` datetime DEFAULT NULL,
  `biaya` decimal(10,2) DEFAULT NULL,
  `status` enum('masuk','keluar') NOT NULL DEFAULT 'masuk',
  `foto_masuk` varchar(255) DEFAULT NULL,
  `foto_keluar` varchar(255) DEFAULT NULL,
  `id_petugas_masuk` int(11) NOT NULL,
  `id_petugas_keluar` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_parkir`
--

INSERT INTO `transaksi_parkir` (`id`, `id_kendaraan`, `kode_barcode`, `waktu_masuk`, `waktu_keluar`, `biaya`, `status`, `foto_masuk`, `foto_keluar`, `id_petugas_masuk`, `id_petugas_keluar`, `created_at`) VALUES
(1, 1, 'PK-1760796034', '2025-10-18 21:00:34', '2025-10-18 22:57:42', 10000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 21:00:34'),
(2, 2, 'PK-1760801838', '2025-10-18 22:37:18', '2025-10-19 19:23:11', 42000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:37:18'),
(3, 3, 'PK-1760802298', '2025-10-18 22:44:58', '2025-10-19 19:22:58', 42000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:44:58'),
(4, 4, 'PK-20251018-00004', '2025-10-18 22:47:50', '2025-10-19 19:22:18', 105000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:47:50'),
(5, 5, 'PK-20251018-00005', '2025-10-18 22:50:17', '2025-10-18 22:50:33', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-18 22:50:17'),
(6, 6, 'PK-20251019-00006', '2025-10-19 19:21:48', '2025-10-19 19:23:21', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-19 19:21:48'),
(7, 7, 'PK-20251020-00007', '2025-10-20 20:19:19', '2025-10-20 20:20:30', 5000.00, 'keluar', NULL, NULL, 2, 2, '2025-10-20 20:19:19'),
(8, 8, 'PK-20251101-00008', '2025-11-01 23:27:05', '2025-11-24 19:27:12', 1098000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-01 23:27:05'),
(9, 9, 'PK-20251101-00009', '2025-11-01 23:27:22', '2025-11-01 23:28:25', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-01 23:27:22'),
(10, 10, 'PK-20251101-00010', '2025-11-01 23:42:55', '2025-11-24 19:27:00', 1096000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-01 23:42:55'),
(11, 11, 'PK-20251116-00011', '2025-11-16 12:39:21', '2025-11-16 12:40:46', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-16 12:39:21'),
(12, 1, 'PK-20251118-00012', '2025-11-18 13:59:26', '2025-11-18 14:00:20', 5000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-18 13:59:26'),
(13, 12, 'PK-20251126-00013', '2025-11-26 22:58:22', '2025-11-26 22:59:11', 2000.00, 'keluar', NULL, NULL, 2, 2, '2025-11-26 22:58:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','pekerja') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin Owner', 'owner@parkir.com', '$2y$10$ApD/.sHlVzNS4eNMVMlEgePhrwRFtmCN90Ek2Mrh2Yyz5MjLrvhcG', 'owner', '2025-10-18 17:13:43', '2025-10-18 17:18:33'),
(2, 'Budi Pekerja', 'pekerja@parkir.com', '$2y$10$Bqjj4NFIEjvxsViyHGcu2Ohz5X/q2H8lMilGJERx0OTElfC0nZzWm', 'pekerja', '2025-10-18 17:13:43', '2025-10-18 17:19:46'),
(3, 'jaya', 'jaya@parkir.gmail', '$2y$10$dbstPYccBKNegK2BVO8vbe4wKLXIU5lpohJTY9yWeSamRO1N9iTMO', 'pekerja', '2025-11-18 19:20:53', '2025-11-18 19:20:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jenis_kendaraan`
--
ALTER TABLE `jenis_kendaraan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plat_nomor` (`plat_nomor`);

--
-- Indexes for table `laporan_keuangan`
--
ALTER TABLE `laporan_keuangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengeluaran_operasional`
--
ALTER TABLE `pengeluaran_operasional`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`);

--
-- Indexes for table `tarif_parkir`
--
ALTER TABLE `tarif_parkir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jenis_kendaraan` (`jenis_kendaraan`);

--
-- Indexes for table `transaksi_parkir`
--
ALTER TABLE `transaksi_parkir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barcode` (`kode_barcode`),
  ADD KEY `id_kendaraan` (`id_kendaraan`),
  ADD KEY `id_petugas_masuk` (`id_petugas_masuk`),
  ADD KEY `id_petugas_keluar` (`id_petugas_keluar`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jenis_kendaraan`
--
ALTER TABLE `jenis_kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `laporan_keuangan`
--
ALTER TABLE `laporan_keuangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pengeluaran_operasional`
--
ALTER TABLE `pengeluaran_operasional`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tarif_parkir`
--
ALTER TABLE `tarif_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaksi_parkir`
--
ALTER TABLE `transaksi_parkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pengeluaran_operasional`
--
ALTER TABLE `pengeluaran_operasional`
  ADD CONSTRAINT `pengeluaran_operasional_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaksi_parkir`
--
ALTER TABLE `transaksi_parkir`
  ADD CONSTRAINT `transaksi_parkir_ibfk_1` FOREIGN KEY (`id_kendaraan`) REFERENCES `kendaraan` (`id`),
  ADD CONSTRAINT `transaksi_parkir_ibfk_2` FOREIGN KEY (`id_petugas_masuk`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transaksi_parkir_ibfk_3` FOREIGN KEY (`id_petugas_keluar`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

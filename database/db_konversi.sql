-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 28, 2025 at 04:31 AM
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
-- Database: `db_konversi`
--

-- --------------------------------------------------------

--
-- Table structure for table `nilai`
--

CREATE TABLE `nilai` (
  `nip` varchar(20) DEFAULT NULL,
  `predikat` varchar(10) DEFAULT NULL,
  `persentase` int(3) DEFAULT NULL,
  `koefisien` decimal(5,2) DEFAULT NULL,
  `angka_kredit` decimal(6,3) DEFAULT NULL,
  `periode` varchar(50) DEFAULT NULL,
  `bulan` varchar(15) NOT NULL,
  `tahun` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai`
--

INSERT INTO `nilai` (`nip`, `predikat`, `persentase`, `koefisien`, `angka_kredit`, `periode`, `bulan`, `tahun`) VALUES
('12341234', 'baik', 3, 12.50, 3.125, 'Januari - Maret', 'Januari', '2025'),
('12341234', 'baik', 9, 12.50, 9.375, 'April - Desember', 'April', '2024');

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `nip` varchar(20) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `no_seri_karpeg` varchar(20) DEFAULT NULL,
  `tempat_tanggal_lahir` varchar(150) DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL,
  `pangkat_golongan_tmt` varchar(100) DEFAULT NULL,
  `jabatan_tmt` varchar(100) DEFAULT NULL,
  `unit_kerja` varchar(150) DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`nip`, `nama`, `no_seri_karpeg`, `tempat_tanggal_lahir`, `jenis_kelamin`, `pangkat_golongan_tmt`, `jabatan_tmt`, `unit_kerja`, `instansi`, `password`, `role`) VALUES
('12341234', 'Gabriel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$h2i8xbG8kkQcIYBfXF.1BukahUaOfLZZt88W7jCZyBY.HmTHTgqiS', 'user'),
('admin', 'Admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$EYYEPF9KgIbVEE/AtRp0PuF2F.AaKZ3cVam1gpDuwVjxqKvu5BUK2', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `nilai`
--
ALTER TABLE `nilai`
  ADD KEY `nip` (`nip`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`nip`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`nip`) REFERENCES `pegawai` (`nip`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

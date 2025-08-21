-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Waktu pembuatan: 21 Agu 2025 pada 11.42
-- Versi server: 10.4.27-MariaDB
-- Versi PHP: 8.0.25

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
-- Struktur dari tabel `nilai`
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
-- Dumping data untuk tabel `nilai`
--

INSERT INTO `nilai` (`nip`, `predikat`, `persentase`, `koefisien`, `angka_kredit`, `periode`, `bulan`, `tahun`) VALUES
('221401026', 'Baik', 9, '12.50', '9.375', 'April - Desember', 'April', '2024'),
('221401026', 'Baik', 3, '12.50', '3.125', 'Januari - Maret', 'Januari', '2025'),
('221401026', 'Baik', 9, '12.50', '9.375', 'April - Desember', 'April', '2023'),
('221401026', 'Baik', 3, '12.50', '3.125', 'Januari - Maret', 'Januari', '2024'),
('221401026', 'Baik', 9, '12.50', '9.375', 'April - Desember', 'April', '2024'),
('221401036', 'Baik', 9, '12.50', '9.375', 'April - Desember', 'April', '2024'),
('12345678', 'Baik', 9, '12.50', '9.375', 'April - Desember', 'April', '2024'),
('199306192024042001', 'Baik', 9, '12.50', '9.375', 'April - Desember', 'April', '2022');

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai_format2`
--

CREATE TABLE `nilai_format2` (
  `tahun` year(4) NOT NULL,
  `periode` varchar(50) NOT NULL,
  `predikat` varchar(20) DEFAULT NULL,
  `prosentase` varchar(10) DEFAULT NULL,
  `koefisien` decimal(5,2) DEFAULT NULL,
  `angka_kredit` decimal(6,3) DEFAULT NULL,
  `nip` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
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
-- Dumping data untuk tabel `pegawai`
--

INSERT INTO `pegawai` (`nip`, `nama`, `no_seri_karpeg`, `tempat_tanggal_lahir`, `jenis_kelamin`, `pangkat_golongan_tmt`, `jabatan_tmt`, `unit_kerja`, `instansi`, `password`, `role`) VALUES
('081133', 'risa', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$dVkQq0musiMPsI9OEywVIuZIwHpWTUEMlGqv19IW05CgDVO1tfZiy', 'user'),
('12345678', 'gabriel', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$hDNElSWD9imbyFvU.UDxLevXmxheOujU3GyEnax0ZEJ8dNnKEWSIC', 'user'),
('199306192024042001', 'Rosa Vella Erdizon', 'A202500007764', 'Kab. Padang Pariaman, 19 Juni 1993', 'Perempuan', 'Penata Muda Tk.I/III/b/01 Juni 2025', '-', 'Politeknik Teknologi Kimia Industri (PTKI) Medan', 'Kementrian Perindustrian', '$2y$10$ltzsGzEAyxTv1CKhAA3jhu/rmwqic8B6vIIKwZKR9FFJg0G3xZtG.', 'user'),
('218844', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '00000000', 'admin'),
('2188888', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '00000000', 'admin'),
('221401026', 'Nur Adilah', 'A202500007765', 'Medan, 28 Agustus 2004', 'Perempuan', '', '', '', 'Kementrian Pertahanan', '$2y$10$lkYEGnKOZnrhch5dR/4LZ.MgZ7CRVyahWDbYu1M/I13XEXp9CM3x6', 'user'),
('221401036', 'Nur Cahaya', '', '', 'Laki-laki', '', '', '', '', '$2y$10$FfZs6bq5td3r2v3Fv2HUk.L0s/aJf8Rues1gtqtS.wNzvYHqr95o2', 'user'),
('223344', 'faiza', 'A202500007764', 'Kab. Padang Pariaman, 19 Juni 1993', 'Perempuan', 'Penata Muda Tk.I/III/b/01 Juni 2025', '-', 'Politeknik Teknologi Kimia Industri (PTKI) Medan', 'Kementrian Perindustrian', '$2y$10$x..E.KAjcqx4FYGySKw0fOwTWzah1LgCx06vGyhxdGdTp3k4IEDVG', 'user'),
('88888888', 'Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('ADMIN001', 'admin1', '', '', 'Laki-laki', '', '', '', '', '$2y$10$KcRRo9kDDHeRd/xga30T5udcVrGB3k5CZChmtYh4pNKQ/HclbaQhO', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `nilai`
--
ALTER TABLE `nilai`
  ADD KEY `nip` (`nip`);

--
-- Indeks untuk tabel `nilai_format2`
--
ALTER TABLE `nilai_format2`
  ADD PRIMARY KEY (`tahun`,`periode`,`nip`),
  ADD KEY `nip` (`nip`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`nip`);

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`nip`) REFERENCES `pegawai` (`nip`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `nilai_format2`
--
ALTER TABLE `nilai_format2`
  ADD CONSTRAINT `nilai_format2_ibfk_1` FOREIGN KEY (`nip`) REFERENCES `pegawai` (`nip`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

<?php
session_start();
include "db.php";

// Cek login user
if (!isset($_SESSION['nip'])) {
    header("Location: login.php");
    exit();
}

$nip = $_SESSION['nip'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bulan_awal = $_POST['bulan_awal'];
    $bulan_akhir = $_POST['bulan_akhir'];
    $tahun = $_POST['tahun'];
    $predikat = $_POST['predikat'];
    $persentase = $_POST['persentase'];
    $koefisien = $_POST['koefisien'];
    $angka_kredit = $_POST['angka_kredit'];
    
    // Gabungkan bulan ke periode
    $periode = $bulan_awal . " - " . $bulan_akhir;
    
    // Validasi: Cek apakah periode sudah ada untuk user ini di tahun yang sama
    $stmt_check = $conn->prepare("SELECT nip FROM nilai WHERE nip = ? AND periode = ? AND tahun = ?");
    $stmt_check->bind_param("sss", $nip, $periode, $tahun);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Periode sudah ada, tampilkan pesan error
        $_SESSION['error_message'] = "Data untuk periode " . $periode . " tahun " . $tahun . " sudah ada. Silakan pilih periode yang berbeda atau edit data yang sudah ada.";
        header("Location: dashboard.php");
        exit();
    }
    
    // Jika tidak ada duplikasi, lanjutkan simpan data menggunakan prepared statement
    $stmt = $conn->prepare("INSERT INTO nilai (nip, predikat, persentase, koefisien, angka_kredit, periode, bulan, tahun) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddssss", $nip, $predikat, $persentase, $koefisien, $angka_kredit, $periode, $bulan_awal, $tahun);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Data konversi berhasil disimpan!";
    } else {
        $_SESSION['error_message'] = "Terjadi kesalahan saat menyimpan data: " . $conn->error;
    }
    
    header("Location: dashboard.php");
    exit();
} else {
    // Jika bukan POST request, redirect ke dashboard
    header("Location: dashboard.php");
    exit();
}
?>
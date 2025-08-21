<?php
session_start();
include "db.php"; //connect database

$nip = $_SESSION['nip'];

$bulan_awal   = $_POST['bulan_awal'];
$bulan_akhir  = $_POST['bulan_akhir'];
$tahun        = $_POST['tahun'];
$predikat     = $_POST['predikat'];
$persentase   = $_POST['persentase'];
$koefisien    = $_POST['koefisien'];
$angka_kredit = $_POST['angka_kredit'];

// Gabungkan bulan ke periode
$periode = $bulan_awal . " - " . $bulan_akhir;

$query = "INSERT INTO nilai (nip, predikat, persentase, koefisien, angka_kredit, periode, bulan, tahun) 
          VALUES ('$nip', '$predikat', '$persentase', '$koefisien', '$angka_kredit', '$periode', '$bulan_awal', '$tahun')";

if (mysqli_query($conn, $query)) {
    echo "<script>alert('Data berhasil disimpan!'); window.location='dashboard.php';</script>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>

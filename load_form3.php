<?php
session_start();
include "db.php";

// Cek login user
if (!isset($_SESSION['nip'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$nip = $_SESSION['nip'];

// Ambil tahun dari POST
$tahun = isset($_POST['tahun_pilih']) ? intval($_POST['tahun_pilih']) : 0;

if ($tahun <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Tahun tidak valid']);
    exit();
}

$tahun_berikutnya = $tahun + 1;

// PERBAIKAN: Query untuk periode akademik (April tahun_pilih - Maret tahun_berikutnya)
// Sama dengan logika di Format 2
$sql = "SELECT * FROM nilai 
        WHERE nip = ? 
        AND (
            (tahun = ? AND bulan IN ('april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember'))
            OR 
            (tahun = ? AND bulan IN ('januari', 'februari', 'maret'))
        )
        ORDER BY 
            CASE 
                WHEN tahun = ? AND bulan IN ('april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember') THEN 1
                WHEN tahun = ? AND bulan IN ('januari', 'februari', 'maret') THEN 2
            END,
            FIELD(bulan, 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', 'januari', 'februari', 'maret')";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "siiii", $nip, $tahun, $tahun_berikutnya, $tahun, $tahun_berikutnya);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    exit();
}

$data = [];
$total_angka_kredit = 0;
$total_koefisien = 0;
$count = 0;

// Ambil semua data
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
    $total_angka_kredit += floatval($row['angka_kredit']);
    $total_koefisien += floatval($row['koefisien']);
    $count++;
}

if (empty($data)) {
    // Tidak ada data
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'no_data',
        'message' => 'Tidak ada data untuk periode akademik ' . $tahun . '/' . $tahun_berikutnya
    ]);
    exit();
}

// Hitung rata-rata koefisien per tahun (sama dengan Format 2)
$koefisien_per_tahun = $count > 0 ? $total_koefisien / $count : 0;

// Ada data, kirim response sukses dengan data yang diperlukan
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'data' => $data,
    'total_angka_kredit' => $total_angka_kredit,
    'koefisien_per_tahun' => $koefisien_per_tahun,
    'tahun' => $tahun,
    'tahun_berikutnya' => $tahun_berikutnya,
    'periode_akademik' => "April {$tahun} - Maret {$tahun_berikutnya}",
    'jumlah_data' => count($data)
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
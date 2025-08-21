<?php
session_start();
include "db.php";

// Header JSON
header('Content-Type: application/json');

// Pastikan user login
if (!isset($_SESSION['nip'])) {
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="6" style="color:red;">Anda harus login terlebih dahulu</td></tr>'
    ]);
    exit;
}

$nip = $_SESSION['nip'];

// Ambil tahun dari POST
$tahun_pilih = $_POST['tahun_pilih'] ?? '';

if ($tahun_pilih === '' || !is_numeric($tahun_pilih)) {
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="6">Tahun tidak dipilih atau tidak valid</td></tr>'
    ]);
    exit;
}

$tahun_berikutnya = (int)$tahun_pilih + 1;

// Query database
$sql = "SELECT * FROM nilai
        WHERE nip = ? 
        AND (tahun = ? OR tahun = ?) 
        ORDER BY tahun ASC, bulan ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="6" style="color:red;">Query error: ' . mysqli_error($conn) . '</td></tr>'
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "sii", $nip, $tahun_pilih, $tahun_berikutnya);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="6">Data tidak ditemukan</td></tr>'
    ]);
    exit;
}

$table_data = '';
$total_angka_kredit = 0;
$total_koefisien = 0;
$count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $periode = ucfirst($row['bulan_awal'] ?? $row['bulan']);
    if (!empty($row['bulan_akhir']) && strtolower($row['bulan_akhir']) != strtolower($row['bulan_awal'])) {
        $periode .= ' - ' . ucfirst($row['bulan_akhir']);
    }

    $persentase = $row['persentase'] ?? ($row['prosentase'] ?? '0');

    $table_data .= "<tr>
        <td>{$row['tahun']}</td>
        <td>{$periode}</td>
        <td>{$row['predikat']}</td>
        <td>{$persentase}/12</td>
        <td>" . number_format($row['koefisien'], 2) . "</td>
        <td>" . number_format($row['angka_kredit'], 3) . "</td>
    </tr>";

    $total_angka_kredit += floatval($row['angka_kredit']);
    $total_koefisien += floatval($row['koefisien']);
    $count++;
}

$koefisien_per_tahun = $count > 0 ? $total_koefisien / $count : 0;

echo json_encode([
    'status' => 'success',
    'table_data' => $table_data,
    'summary_data' => [
        'koefisien_per_tahun' => number_format($koefisien_per_tahun, 2),
        'angka_kredit_yang_didapat' => number_format($total_angka_kredit, 3)
    ]
]);

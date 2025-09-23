<?php
session_start();
include "db.php";

// Header JSON
header('Content-Type: application/json');

// Pastikan user login
if (!isset($_SESSION['nip'])) {
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="7" style="color:red;">Anda harus login terlebih dahulu</td></tr>'
    ]);
    exit;
}

$nip = $_SESSION['nip'];

// Ambil tahun dari POST
$tahun_pilih = $_POST['tahun_pilih'] ?? '';

if ($tahun_pilih === '' || !is_numeric($tahun_pilih)) {
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="7">Tahun tidak dipilih atau tidak valid</td></tr>'
    ]);
    exit;
}

$tahun_berikutnya = (int)$tahun_pilih + 1;

// PERBAIKAN: Query untuk periode akademik (April tahun_pilih - Maret tahun_berikutnya)
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
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="7" style="color:red;">Query error: ' . mysqli_error($conn) . '</td></tr>'
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "siiii", $nip, $tahun_pilih, $tahun_berikutnya, $tahun_pilih, $tahun_berikutnya);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode([
        'status' => 'error',
        'table_data' => '<tr><td colspan="7">Tidak ada data untuk periode akademik ' . $tahun_pilih . '/' . $tahun_berikutnya . '</td></tr>'
    ]);
    exit;
}

$table_data = '';
$total_angka_kredit = 0;
$total_koefisien = 0;
$count = 0;

while ($row = mysqli_fetch_assoc($result)) {
    // Handle periode berdasarkan kolom yang ada di database
    $periode = '';
    
    // Prioritas: gunakan kolom periode jika ada dan tidak kosong
    if (!empty($row['periode'])) {
        $periode = $row['periode'];
    } 
    // Fallback ke kolom bulan jika periode kosong
    else if (!empty($row['bulan'])) {
        $periode = $row['bulan'];
    }
    else {
        $periode = 'Tidak Diketahui';
    }
    
    // Pastikan format periode konsisten (huruf pertama kapital)
    $periode = ucfirst(strtolower($periode));
    
    // Jika periode dalam format "april - desember", pastikan formatnya benar
    if (strpos($periode, ' - ') !== false) {
        $periode_parts = explode(' - ', $periode);
        $periode = ucfirst(trim($periode_parts[0])) . ' - ' . ucfirst(trim($periode_parts[1]));
    }

    $persentase = $row['persentase'] ?? ($row['prosentase'] ?? '0');
    
    // Create unique row key for editing
    $periode_clean = str_replace([' ', '-', ' - '], ['_', '_', '_'], $periode);
    $row_key = $row['tahun'] . '_' . $periode_clean;
    
    // PERBAIKAN: Tampilkan periode akademik yang benar
    $display_year = $row['tahun'];
    if ($row['tahun'] == $tahun_berikutnya && in_array(strtolower($row['bulan']), ['januari', 'februari', 'maret'])) {
        $display_year = $row['tahun'];
    }
    
    $table_data .= "<tr data-row-key='{$row_key}'>
        <td class='editable-field' data-field='tahun' title='Klik untuk edit'>{$display_year}</td>
        <td class='editable-field' data-field='periode' title='Klik untuk edit'>{$periode}</td>
        <td class='editable-field' data-field='predikat' title='Klik untuk edit'>{$row['predikat']}</td>
        <td class='editable-field' data-field='persentase' title='Klik untuk edit'>{$persentase}/12</td>
        <td class='editable-field' data-field='koefisien' title='Klik untuk edit'>" . number_format($row['koefisien'], 2) . "</td>
        <td class='calculated-field'>" . number_format($row['angka_kredit'], 3) . "</td>
        <td class='action-cell'>
            <button type='button' class='delete-row-btn' onclick='deleteKonversiData(\"{$row_key}\")' title='Hapus data'>
                🗑
            </button>
        </td>
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
    ],
    'periode_info' => "Periode Akademik: April {$tahun_pilih} - Maret {$tahun_berikutnya}"
]);
?>
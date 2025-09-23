<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['nip'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired']);
    exit;
}

$rowKey = $_POST['row_key'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

if (empty($rowKey) || empty($field)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit;
}

// Parse row key untuk identifikasi record
$parts = explode('_', $rowKey);
$tahun = $parts[0];

// Validasi field yang diizinkan
$allowedFields = ['tahun', 'periode', 'predikat', 'persentase', 'koefisien'];
if (!in_array($field, $allowedFields)) {
    echo json_encode(['status' => 'error', 'message' => 'Field tidak valid']);
    exit;
}

// Escape field name
$field_escaped = mysqli_real_escape_string($conn, $field);

// Update hanya kolom yang diminta
$sql = "UPDATE nilai SET `{$field_escaped}` = ? WHERE nip = ? AND tahun = ?";
$params = [$value, $_SESSION['nip'], $tahun];
$types = "sss";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Query prepare failed']);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);

if (mysqli_stmt_execute($stmt)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    
    if ($affected_rows > 0) {
        // Recalculate angka_kredit jika perlu
        if ($field === 'persentase' || $field === 'koefisien') {
            $calc_sql = "UPDATE nilai SET angka_kredit = (persentase * koefisien) WHERE nip = ? AND tahun = ?";
            $calc_stmt = mysqli_prepare($conn, $calc_sql);
            if ($calc_stmt) {
                mysqli_stmt_bind_param($calc_stmt, "ss", $_SESSION['nip'], $tahun);
                mysqli_stmt_execute($calc_stmt);
                mysqli_stmt_close($calc_stmt);
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil diupdate']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang diupdate']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate data']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
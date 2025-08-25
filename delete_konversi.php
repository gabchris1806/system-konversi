<?php
session_start();
include "db.php";

// Set header JSON
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['nip'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit;
}

$nip = $_SESSION['nip'];

// Ambil row_key dari POST
$row_key = $_POST['row_key'] ?? '';

if (empty($row_key)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Row key tidak ditemukan'
    ]);
    exit;
}

// Parse row_key untuk mendapatkan tahun dan periode/bulan
$key_parts = explode('_', $row_key);
if (count($key_parts) < 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Format key tidak valid'
    ]);
    exit;
}

$tahun = $key_parts[0];
$periode_raw = implode('_', array_slice($key_parts, 1));

// Kemungkinan format periode yang perlu dicoba
$periode_formats = [
    $periode_raw,                               // Format asli
    str_replace('_', ' - ', $periode_raw),      // Format dengan spasi
    str_replace('_', ' ', $periode_raw),        // Format dengan spasi biasa
];

// Mulai transaction
mysqli_autocommit($conn, false);

try {
    $data_found = false;
    $periode_matched = '';
    
    // Cari data dengan berbagai format periode
    foreach ($periode_formats as $periode_test) {
        $check_sql = "SELECT * FROM nilai WHERE nip = ? AND tahun = ? AND (bulan = ? OR periode = ?)";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if (!$check_stmt) {
            throw new Exception('Prepare check statement gagal: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($check_stmt, "ssss", $nip, $tahun, $periode_test, $periode_test);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $data_found = true;
            $periode_matched = $periode_test;
            mysqli_stmt_close($check_stmt);
            break;
        }
        
        mysqli_stmt_close($check_stmt);
    }
    
    if (!$data_found) {
        throw new Exception("Data tidak ditemukan untuk NIP: $nip, Tahun: $tahun, Periode: " . implode(' / ', $periode_formats));
    }
    
    // Delete record berdasarkan nip, tahun, dan periode/bulan yang cocok
    $delete_sql = "DELETE FROM nilai WHERE nip = ? AND tahun = ? AND (bulan = ? OR periode = ?) LIMIT 1";
    
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    if (!$delete_stmt) {
        throw new Exception('Prepare statement gagal: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($delete_stmt, "ssss", $nip, $tahun, $periode_matched, $periode_matched);
    
    if (!mysqli_stmt_execute($delete_stmt)) {
        throw new Exception('Delete gagal: ' . mysqli_stmt_error($delete_stmt));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
    
    if ($affected_rows === 0) {
        throw new Exception('Gagal menghapus data');
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Data berhasil dihapus'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    // Reset autocommit
    mysqli_autocommit($conn, true);
    
    // Close statements
    if (isset($delete_stmt)) mysqli_stmt_close($delete_stmt);
}
?>
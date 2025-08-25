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

// Ambil data JSON dari request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Debug log
error_log("Update Konversi - Input received: " . print_r($data, true));
error_log("Update Konversi - NIP: " . $nip);

// Validasi input
if (!$data || !isset($data['row_key']) || !isset($data['field']) || !isset($data['value'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

$row_key = $data['row_key'];
$field = $data['field'];
$new_value = trim($data['value']);

// PERBAIKAN 1: Cek apakah row_key adalah ID database atau format lain
if (is_numeric($row_key)) {
    // Jika row_key adalah ID database
    $record_id = (int)$row_key;
    
    // Query berdasarkan ID
    $find_sql = "SELECT * FROM nilai WHERE nip = ? LIMIT 1";
    $find_stmt = mysqli_prepare($conn, $find_sql);
    if (!$find_stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare statement gagal: ' . mysqli_error($conn)
        ]);
        exit;
    }
    
    mysqli_stmt_bind_param($find_stmt, "is", $record_id, $nip);
    
} else {
    // PERBAIKAN 2: Parse row_key untuk mendapatkan tahun dan periode
    $key_parts = explode('_', $row_key);
    if (count($key_parts) < 2) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Format key tidak valid: ' . $row_key
        ]);
        exit;
    }

    $tahun = $key_parts[0];
    $periode_raw = implode('_', array_slice($key_parts, 1));
    
    // PERBAIKAN 3: Coba berbagai variasi pencarian
    $find_sql = "SELECT * FROM nilai 
                 WHERE nip = ? AND tahun = ? 
                 AND (
                     bulan LIKE ? OR 
                     periode LIKE ? OR
                     CONCAT(bulan_awal, ' - ', bulan_akhir) LIKE ? OR
                     bulan_awal = ? OR
                     bulan_akhir = ?
                 )
                 LIMIT 1";
    
    $find_stmt = mysqli_prepare($conn, $find_sql);
    if (!$find_stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Prepare statement gagal: ' . mysqli_error($conn)
        ]);
        exit;
    }
    
    // Prepare search patterns
    $periode_pattern = "%{$periode_raw}%";
    $periode_single = str_replace('_', ' ', $periode_raw);
    
    mysqli_stmt_bind_param($find_stmt, "sisssss", 
        $nip, $tahun, 
        $periode_pattern, $periode_pattern, $periode_pattern,
        $periode_single, $periode_single
    );
}

// Validasi field yang diizinkan untuk diedit
$allowed_fields = ['predikat', 'persentase', 'koefisien', 'tahun', 'periode'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Field tidak diizinkan untuk diedit: ' . $field
    ]);
    exit;
}

// Validasi nilai berdasarkan field
switch ($field) {
    case 'persentase':
        if (!is_numeric($new_value) || $new_value < 1 || $new_value > 12) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Persentase harus berupa angka antara 1-12'
            ]);
            exit;
        }
        $new_value = (int)$new_value;
        break;
        
    case 'koefisien':
        if (!is_numeric($new_value) || $new_value <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Koefisien harus berupa angka positif'
            ]);
            exit;
        }
        $new_value = (float)$new_value;
        break;
        
    case 'tahun':
        if (!is_numeric($new_value) || $new_value < 1900 || $new_value > 2100) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tahun harus berupa angka antara 1900-2100'
            ]);
            exit;
        }
        $new_value = (int)$new_value;
        break;
        
    case 'periode':
        if (empty($new_value)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Periode tidak boleh kosong'
            ]);
            exit;
        }
        // Validasi format periode
        $valid_months = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 
                        'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];
        
        $periode_parts = explode(' - ', strtolower($new_value));
        foreach ($periode_parts as $month) {
            $month = trim($month);
            if (!in_array($month, $valid_months)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Format periode tidak valid. Gunakan nama bulan atau range bulan'
                ]);
                exit;
            }
        }
        break;
        
    case 'predikat':
        if (empty($new_value)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Predikat tidak boleh kosong'
            ]);
            exit;
        }
        break;
}

// Mulai transaction
mysqli_autocommit($conn, false);

try {
    // Eksekusi query pencarian
    mysqli_stmt_execute($find_stmt);
    $find_result = mysqli_stmt_get_result($find_stmt);
    
    if (mysqli_num_rows($find_result) === 0) {
        // PERBAIKAN 4: Debug informasi lebih detail
        error_log("Data tidak ditemukan untuk: NIP={$nip}, row_key={$row_key}");
        
        // Coba query debug untuk melihat data yang ada
        $debug_sql = "SELECT nip, tahun, bulan, bulan_awal, bulan_akhir, periode FROM nilai WHERE nip = ?";
        $debug_stmt = mysqli_prepare($conn, $debug_sql);
        mysqli_stmt_bind_param($debug_stmt, "s", $nip);
        mysqli_stmt_execute($debug_stmt);
        $debug_result = mysqli_stmt_get_result($debug_stmt);
        
        $available_data = [];
        while ($row = mysqli_fetch_assoc($debug_result)) {
            $available_data[] = $row;
        }
        
        error_log("Data yang tersedia: " . print_r($available_data, true));
        
        throw new Exception('Data tidak ditemukan. Row key: ' . $row_key);
    }
    
    $existing_record = mysqli_fetch_assoc($find_result);
    error_log("Record ditemukan: " . print_r($existing_record, true));
    
    // Tentukan field database yang akan diupdate
    $update_fields = [];
    $params = [];
    $param_types = '';
    
    // Map field ke kolom database
    switch ($field) {
        case 'tahun':
            $update_fields[] = 'tahun = ?';
            $params[] = $new_value;
            $param_types .= 'i';
            break;
            
        case 'periode':
            // Update kolom periode dan bulan terkait
            $periode_parts = explode(' - ', $new_value);
            if (count($periode_parts) == 1) {
                // Single month
                $update_fields[] = 'bulan = ?';
                $update_fields[] = 'bulan_awal = ?';
                $update_fields[] = 'bulan_akhir = ?';
                $params[] = ucfirst(trim($periode_parts[0]));
                $params[] = ucfirst(trim($periode_parts[0]));
                $params[] = ucfirst(trim($periode_parts[0]));
                $param_types .= 'sss';
            } else {
                // Range of months
                $update_fields[] = 'bulan_awal = ?';
                $update_fields[] = 'bulan_akhir = ?';
                $update_fields[] = 'periode = ?';
                $params[] = ucfirst(trim($periode_parts[0]));
                $params[] = ucfirst(trim($periode_parts[1]));
                $params[] = $new_value;
                $param_types .= 'sss';
            }
            break;
            
        case 'predikat':
            $update_fields[] = 'predikat = ?';
            $params[] = $new_value;
            $param_types .= 's';
            break;
            
        case 'persentase':
            $update_fields[] = 'persentase = ?';
            $params[] = $new_value;
            $param_types .= 'i';
            break;
            
        case 'koefisien':
            $update_fields[] = 'koefisien = ?';
            $params[] = $new_value;
            $param_types .= 'd';
            break;
    }
    
    // Hitung ulang angka_kredit jika persentase atau koefisien berubah
    $new_angka_kredit = null;
    if ($field === 'persentase' || $field === 'koefisien') {
        $current_persentase = ($field === 'persentase') ? $new_value : $existing_record['persentase'];
        $current_koefisien = ($field === 'koefisien') ? $new_value : $existing_record['koefisien'];
        
        $new_angka_kredit = ($current_persentase / 12) * $current_koefisien;
        
        $update_fields[] = 'angka_kredit = ?';
        $params[] = $new_angka_kredit;
        $param_types .= 'd';
    }
    
    // PERBAIKAN 5: Update berdasarkan NIP untuk konsistensi
    $update_sql = "UPDATE nilai SET " . implode(', ', $update_fields) . " WHERE nip = ?";
    $params[] = $existing_record['nip'];
    $param_types .= 'i';
    
    $update_stmt = mysqli_prepare($conn, $update_sql);
    if (!$update_stmt) {
        throw new Exception('Prepare statement gagal: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($update_stmt, $param_types, ...$params);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Update gagal: ' . mysqli_stmt_error($update_stmt));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($update_stmt);
    
    if ($affected_rows === 0) {
        throw new Exception('Tidak ada data yang diupdate');
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $response = [
        'status' => 'success',
        'message' => 'Data berhasil diupdate',
        'affected_rows' => $affected_rows
    ];
    
    if ($new_angka_kredit !== null) {
        $response['new_angka_kredit'] = number_format($new_angka_kredit, 3);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Update Konversi Error: " . $e->getMessage());
    
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    mysqli_autocommit($conn, true);
    
    if (isset($find_stmt)) mysqli_stmt_close($find_stmt);
    if (isset($update_stmt)) mysqli_stmt_close($update_stmt);
}
?>
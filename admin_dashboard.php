<?php
session_start();
include "db.php";

// Cek login admin
if (!isset($_SESSION['nip']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil data admin
$nip_admin = $_SESSION['nip'];
$query_admin = mysqli_query($conn, "SELECT * FROM pegawai WHERE nip='$nip_admin'");
$admin = mysqli_fetch_assoc($query_admin);

// Ambil semua data user
$query_users = mysqli_query($conn, "SELECT * FROM pegawai ORDER BY nama ASC");

// Ambil statistik - FIXED: ganti dari 'konversi' ke 'nilai'
$total_users = mysqli_num_rows($query_users);
$query_total_konversi = mysqli_query($conn, "SELECT COUNT(*) as total FROM nilai");
$total_konversi = mysqli_fetch_assoc($query_total_konversi)['total'];

// Handle edit user data
if (isset($_POST['edit_user'])) {
    $nip_edit = mysqli_real_escape_string($conn, $_POST['nip_edit']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $no_seri_karpeg = mysqli_real_escape_string($conn, $_POST['no_seri_karpeg']);
    $tempat_tanggal_lahir = mysqli_real_escape_string($conn, $_POST['tempat_tanggal_lahir']);
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $pangkat_golongan_tmt = mysqli_real_escape_string($conn, $_POST['pangkat_golongan_tmt']);
    $jabatan_tmt = mysqli_real_escape_string($conn, $_POST['jabatan_tmt']);
    $unit_kerja = mysqli_real_escape_string($conn, $_POST['unit_kerja']);
    $instansi = mysqli_real_escape_string($conn, $_POST['instansi']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    
    $update_query = "UPDATE pegawai SET 
        nama='$nama',
        no_seri_karpeg='$no_seri_karpeg',
        tempat_tanggal_lahir='$tempat_tanggal_lahir',
        jenis_kelamin='$jenis_kelamin',
        pangkat_golongan_tmt='$pangkat_golongan_tmt',
        jabatan_tmt='$jabatan_tmt',
        unit_kerja='$unit_kerja',
        instansi='$instansi',
        role='$role'
        WHERE nip='$nip_edit'";
    
    if (mysqli_query($conn, $update_query)) {
        echo "<script>alert('Data user berhasil diperbarui!'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data user: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle delete user - FIXED: ganti dari 'konversi' ke 'nilai'
if (isset($_GET['delete_user']) && isset($_GET['nip'])) {
    $nip_to_delete = $_GET['nip'];
    
    // Delete user's data from nilai table first
    mysqli_query($conn, "DELETE FROM nilai WHERE nip='$nip_to_delete'");
    
    // Delete user
    $delete_result = mysqli_query($conn, "DELETE FROM pegawai WHERE nip='$nip_to_delete'");
    
    if ($delete_result) {
        echo "<script>alert('User berhasil dihapus!'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus user!');</script>";
    }
}

// Handle get user data for edit
$edit_user = null;
if (isset($_GET['edit_user']) && isset($_GET['nip'])) {
    $nip_edit = $_GET['nip'];
    $query_edit = mysqli_query($conn, "SELECT * FROM pegawai WHERE nip='$nip_edit'");
    $edit_user = mysqli_fetch_assoc($query_edit);
}

// Handle view user data - FIXED: ganti dari 'konversi' ke 'nilai'
$selected_user = null;
$user_konversi_data = [];
if (isset($_GET['view_user']) && isset($_GET['nip'])) {
    $nip_view = $_GET['nip'];
    $query_selected = mysqli_query($conn, "SELECT * FROM pegawai WHERE nip='$nip_view'");
    $selected_user = mysqli_fetch_assoc($query_selected);
    
    if ($selected_user) {
        // Get all possible NIP variations
        $nip_primary = $selected_user['nip'];
        $nip_karpeg = $selected_user['no_seri_karpeg'];
        
        // Build search conditions for multiple NIP formats
        $search_conditions = array();
        if (!empty($nip_view)) $search_conditions[] = "nip='" . mysqli_real_escape_string($conn, $nip_view) . "'";
        if (!empty($nip_primary) && $nip_primary != $nip_view) $search_conditions[] = "nip='" . mysqli_real_escape_string($conn, $nip_primary) . "'";
        if (!empty($nip_karpeg) && $nip_karpeg != $nip_view && $nip_karpeg != $nip_primary) $search_conditions[] = "nip='" . mysqli_real_escape_string($conn, $nip_karpeg) . "'";
        
        // FIXED: Ganti tabel dari 'konversi' ke 'nilai'
        if (!empty($search_conditions)) {
            $where_clause = implode(' OR ', $search_conditions);
            $konversi_query = "SELECT * FROM nilai WHERE $where_clause ORDER BY tahun DESC, bulan ASC";
            
            // Debug information
            echo "<!-- Debug: Searching in 'nilai' table -->";
            echo "<!-- Debug: Query: $konversi_query -->";
            echo "<!-- Debug: Search conditions: " . $where_clause . " -->";
            
            $query_konversi = mysqli_query($conn, $konversi_query);
            
            if (!$query_konversi) {
                echo "<!-- Debug: Query error: " . mysqli_error($conn) . " -->";
            } else {
                $rows_found = mysqli_num_rows($query_konversi);
                echo "<!-- Debug: Query successful, rows found: $rows_found -->";
                
                while ($row = mysqli_fetch_assoc($query_konversi)) {
                    $user_konversi_data[] = $row;
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - PTPN IV</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #001aff1a, #00ffea1a);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 26, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 234, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(0, 26, 255, 0.05) 0%, transparent 50%);
            z-index: -1;
            pointer-events: none;
        }
        
        /* NAVBAR */
        .navbar {
            background: #001affb4;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 26, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 26, 255, 0.15);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar .navbar-left,
        .navbar .navbar-right {
            display: flex;
            align-items: center;
        }

        .navbar .logo {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }

        .navbar .app-title {
            color: white;
            font-size: 18px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .admin-badge {
            background: linear-gradient(135deg, #001affcc, #00ffea99);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 26, 255, 0.3);
        }

        /* ADMIN HEADER */
        .admin-header {
            background: linear-gradient(135deg, #001aff0d, #00ffea0d);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 26, 255, 0.1);
            color: #001aff;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 25px 25px;
            position: relative;
            overflow: hidden;
        }

        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 234, 0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .admin-header h1 {
            text-align: center;
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
            text-shadow: 0 4px 15px rgba(0, 26, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        /* STATISTICS */
        .stats-container {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 26, 255, 0.15);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 
                0 8px 32px rgba(0, 26, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 220px;
            height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 234, 0.1), transparent);
            transition: left 0.5s;
        }

        .stat-card:hover::before {
            left: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(0, 26, 255, 0.2),
                0 0 0 1px rgba(0, 255, 234, 0.3);
        }
        
        .stat-card.users {
            border-left: 4px solid #001aff;
        }
        
        .stat-card.konversi {
            border-left: 4px solid #0fff37ff;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .stat-card.users .stat-number {
            color: #001aff;
            text-shadow: 0 4px 15px rgba(0, 26, 255, 0.3);
        }
        
        .stat-card.konversi .stat-number {
            color: #00ff40ff;
            text-shadow: 0 4px 15px rgba(0, 255, 234, 0.3);
        }
        
        .stat-label {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        /* SEARCH */
        .search-container {
            margin-bottom: 25px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-input {
            width: 100%;
            padding: 18px 25px;
            border: 2px solid rgba(0, 26, 255, 0.2);
            border-radius: 30px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(20px);
            box-shadow: 
                0 8px 32px rgba(0, 26, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            color: #333;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #001aff;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 
                0 0 0 3px rgba(0, 26, 255, 0.1),
                0 12px 40px rgba(0, 26, 255, 0.2);
            transform: translateY(-2px);
        }

        .search-input::placeholder {
            color: rgba(51, 51, 51, 0.6);
        }
        
        /* TABLE */
        .table-scroll-wrapper {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 26, 255, 0.15);
            border-radius: 20px;
            box-shadow: 
                0 8px 32px rgba(0, 26, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            margin: 25px 0;
            width: 100%;
            overflow-x: auto;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            table-layout: auto;
            min-width: 1200px;
        }
        
        .users-table th {
            background: #001affcc;
            color: white;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid rgba(0, 26, 255, 0.2);
        }

        .users-table th:first-child {
            border-top-left-radius: 20px;
        }

        .users-table th:last-child {
            border-top-right-radius: 20px;
        }
        
        .users-table td {
            padding: 15px 10px;
            border-bottom: 1px solid rgba(0, 26, 255, 0.1);
            color: #333;
            transition: all 0.3s ease;
            vertical-align: middle;
            font-size: 13px;
            text-align: center;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 150px;
        }
        
        .users-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(0, 26, 255, 0.05), rgba(0, 255, 234, 0.03));
            transform: scale(1.001);
        }
        
        .users-table tbody tr:nth-child(even) {
            background: rgba(0, 26, 255, 0.02);
        }

        /* ROLE BADGE STYLING */
        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .role-badge.admin {
            background: linear-gradient(135deg, #001aff, #0066ff);
            color: white;
        }

        .role-badge.user {
            background: linear-gradient(135deg, #00ff40, #00cc32);
            color: white;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            flex-direction: row;
            gap: 8px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 50px;
            height: 38px;
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .action-btn:hover::before {
            left: 100%;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #001aff, #0066ff);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 26, 255, 0.3);
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #0066ff, #001aff);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 26, 255, 0.4);
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }
        
        .btn-edit:hover {
            background: linear-gradient(135deg, #f57c00, #ff9800);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 152, 0, 0.4);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ff1744, #f50057);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 23, 68, 0.3);
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #f50057, #ff1744);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 23, 68, 0.4);
        }

        /* EDIT FORM */
        .edit-form-group {
            margin-bottom: 20px;
        }
        
        .edit-form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #001aff;
            font-size: 14px;
        }
        
        .edit-form-group input,
        .edit-form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(0, 26, 255, 0.2);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(10px);
            color: #333;
        }
        
        .edit-form-group input:focus,
        .edit-form-group select:focus {
            outline: none;
            border-color: #001aff;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(0, 26, 255, 0.1);
        }
        
        .edit-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .edit-form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid rgba(0, 26, 255, 0.1);
        }
        
        .btn-save {
            background: linear-gradient(135deg, #4caf50, #388e3c);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .btn-save:hover {
            background: linear-gradient(135deg, #388e3c, #4caf50);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.4);
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #607d8b, #455a64);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(96, 125, 139, 0.3);
        }
        
        .btn-cancel:hover {
            background: linear-gradient(135deg, #455a64, #607d8b);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(96, 125, 139, 0.4);
        }

        .user-detail-modal,
        .edit-user-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 26, 255, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
            to {
                opacity: 1;
                backdrop-filter: blur(10px);
            }
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(25px);
            border: 1px solid rgba(0, 26, 255, 0.15);
            padding: 35px;
            border-radius: 25px;
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 
                0 20px 40px rgba(0, 26, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(30px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(0, 26, 255, 0.1);
        }

        .modal-header h2 {
            color: #001aff;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .close-modal {
            background: linear-gradient(135deg, #ff1744, #f50057);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 23, 68, 0.3);
        }
        
        .close-modal:hover {
            background: linear-gradient(135deg, #f50057, #ff1744);
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 23, 68, 0.4);
        }
        
        /* INFO CARDS */
        .user-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 26, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid #001aff;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 234, 0.1), transparent);
            transition: left 0.5s;
        }

        .info-card:hover::before {
            left: 100%;
        }

        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 26, 255, 0.15);
        }
        
        .info-label {
            font-weight: 600;
            color: #001aff;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        
        .info-value {
            color: #555;
            font-size: 14px;
            word-wrap: break-word;
            position: relative;
            z-index: 1;
        }
        
        /* KONVERSI TABLE */
        .konversi-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(15px);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 26, 255, 0.15);
            border: 1px solid rgba(0, 26, 255, 0.1);
        }
        
        .konversi-table th {
            background: linear-gradient(135deg, #001aff, #0066ff);
            color: white;
            padding: 15px 12px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .konversi-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 26, 255, 0.1);
            font-size: 13px;
            color: #333;
        }
        
        .konversi-table tbody tr:nth-child(even) {
            background: rgba(0, 26, 255, 0.03);
        }

        .konversi-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(0, 26, 255, 0.05), rgba(0, 255, 234, 0.03));
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-style: italic;
            background: linear-gradient(135deg, rgba(0, 26, 255, 0.03), rgba(0, 255, 234, 0.02));
            border-radius: 15px;
            margin: 20px 0;
        }
        
        /* CONTAINER */
        .tab-container {
            max-width: none;
            margin: 0 20px;
            padding: 0;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                align-items: center;
                gap: 20px;
            }
            
            .stat-card {
                width: 200px;
                height: 120px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .stat-label {
                font-size: 0.9rem;
            }
            
            .modal-content {
                width: 95%;
                padding: 25px;
            }
            
            .user-info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .search-container {
                margin-bottom: 20px;
                margin-left: 10px;
                margin-right: 10px;
            }
            
            .admin-header h1 {
                font-size: 1.8rem;
            }
            
            .tab-container {
                margin: 0 10px;
            }
            
            .table-scroll-wrapper {
                margin: 15px 0;
                border-radius: 15px;
            }
            
            .users-table th,
            .users-table td {
                padding: 12px 8px;
                font-size: 12px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-btn {
                width: 100%;
                min-width: 60px;
                padding: 8px 10px;
                font-size: 11px;
            }
        }
        
        @media (max-width: 480px) {
            .tab-container {
                margin: 0 5px;
            }
            
            .users-table th,
            .users-table td {
                padding: 10px 6px;
                font-size: 11px;
            }
            
            .admin-header {
                padding: 20px 10px;
            }
            
            .admin-header h1 {
                font-size: 1.5rem;
            }
            
            .search-input {
                padding: 15px 20px;
                font-size: 15px;
            }
            
            .modal-content {
                padding: 20px;
            }
        }

        /* SCROLLBAR CUSTOMIZATION */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 26, 255, 0.05);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #001aff, #00ffea);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #0066ff, #00ffea);
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-left">
            <img src="Logo_PTKI_Medan.png" class="logo" alt="Logo">
            <span class="app-title">System Konversi Angka Kredit</span>
        </div>
        <div class="navbar-right">
            <div class="profile-menu">
                <img src="profile.png" class="profile" alt="Profile">
                <div class="dropdown">
                    <a href="edit_profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($admin['nama']); ?></span>
                <span class="user-nip"><?php echo htmlspecialchars($admin['nip']); ?><span class="admin-badge">Admin</span></span>
            </div>
        </div>
    </nav>

    <!-- ADMIN HEADER -->
    <div class="admin-header">
        <h1>🛠 Dashboard Administrator</h1>
    </div>

    <!-- MAIN CONTENT -->
    <div class="tab-container">
        <!-- STATISTICS -->
        <div class="stats-container">
            <div class="stat-card users">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
            <div class="stat-card konversi">
                <div class="stat-number"><?php echo $total_konversi; ?></div>
                <div class="stat-label">Total Data Konversi</div>
            </div>
        </div>

        <!-- SEARCH BAR -->
        <div class="search-container">
            <input type="text" class="search-input" id="searchInput" placeholder="🔍 Cari pengguna berdasarkan nama, NIP, atau instansi...">
        </div>

        <!-- USERS TABLE -->
        <div class="table-scroll-wrapper">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Tempat, Tanggal Lahir</th>
                        <th>Jenis Kelamin</th>
                        <th>Pangkat/Golongan</th>
                        <th>Jabatan</th>
                        <th>Unit Kerja</th>
                        <th>Instansi</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php
                    mysqli_data_seek($query_users, 0);
                    $no = 1;
                    while ($user = mysqli_fetch_assoc($query_users)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($user['nama'] ?: '-') . "</td>";
                        echo "<td>" . htmlspecialchars($user['no_seri_karpeg'] ?: $user['nip']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['tempat_tanggal_lahir'] ?: '-') . "</td>";
                        echo "<td>" . htmlspecialchars($user['jenis_kelamin'] ?: '-') . "</td>";
                        echo "<td>" . htmlspecialchars($user['pangkat_golongan_tmt'] ?: '-') . "</td>";
                        echo "<td>" . htmlspecialchars($user['jabatan_tmt'] ?: '-') . "</td>";
                        echo "<td>" . htmlspecialchars($user['unit_kerja'] ?: '-') . "</td>";
                        echo "<td>" . htmlspecialchars($user['instansi'] ?: '-') . "</td>";
                        echo "<td><span class='role-badge " . strtolower($user['role']) . "'>" . htmlspecialchars(ucfirst($user['role'])) . "</span></td>";
                        echo "<td>";
                        echo "<div class='action-buttons'>";
                        echo "<a href='?view_user=1&nip=" . urlencode($user['nip']) . "' class='action-btn btn-view' title='Lihat Detail'>👁</a>";
                        echo "<a href='?edit_user=1&nip=" . urlencode($user['nip']) . "' class='action-btn btn-edit' title='Edit User'>✏️</a>";
                        echo "<a href='?delete_user=1&nip=" . urlencode($user['nip']) . "' class='action-btn btn-delete' title='Hapus User' onclick='return confirm(\"Yakin ingin menghapus user " . htmlspecialchars($user['nama']) . "?\")'>🗑</a>";
                        echo "</div>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    
                    if ($total_users == 0) {
                        echo "<tr><td colspan='10' class='no-data'>Belum ada pengguna yang terdaftar</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- USER DETAIL MODAL -->
    <?php if ($selected_user): ?>
    <div class="user-detail-modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📋 Detail Pengguna: <?php echo htmlspecialchars($selected_user['nama']); ?></h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <!-- USER INFO -->
            <div class="user-info-grid">
                <div class="info-card">
                    <div class="info-label">Nama Lengkap</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['nama'] ?: '-'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">NIP/No. Seri Karpeg</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['no_seri_karpeg'] ?: $selected_user['nip']); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Tempat, Tanggal Lahir</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['tempat_tanggal_lahir'] ?: '-'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Jenis Kelamin</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['jenis_kelamin'] ?: '-'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Pangkat/Golongan TMT</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['pangkat_golongan_tmt'] ?: '-'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Jabatan TMT</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['jabatan_tmt'] ?: '-'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Unit Kerja</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['unit_kerja'] ?: '-'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Instansi</div>
                    <div class="info-value"><?php echo htmlspecialchars($selected_user['instansi'] ?: '-'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">Role</div>
                    <div class="info-value">
                        <span class="role-badge <?php echo strtolower($selected_user['role']); ?>">
                            <?php echo htmlspecialchars(ucfirst($selected_user['role'])); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- KONVERSI DATA - FIXED: Updated for 'nilai' table -->
            <h3 style="color: #001aff; font-weight: 700; margin-bottom: 15px;">📊 Data Konversi</h3>
            <!-- Debug info -->
            <p style="font-size: 12px; color: #666; margin: 10px 0;">
                Total data konversi ditemukan: <?php echo count($user_konversi_data); ?> | 
                NIP yang dicari: <?php echo htmlspecialchars($nip_view); ?>
            </p>
            
            <?php if (count($user_konversi_data) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="konversi-table">
                    <thead>
                        <tr>
                            <th>Tahun</th>
                            <th>Periode</th>
                            <th>Predikat</th>
                            <th>Persentase</th>
                            <th>Koefisien</th>
                            <th>Total Angka Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_konversi_data as $konversi): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($konversi['tahun']); ?></td>
                            <td>
                                <?php 
                                // Handle different period formats
                                if (isset($konversi['periode'])) {
                                    echo htmlspecialchars($konversi['periode']);
                                } elseif (isset($konversi['bulan'])) {
                                    echo htmlspecialchars($konversi['bulan']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($konversi['predikat'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($konversi['persentase'] ?? '-'); ?></td>
                            <td><?php echo isset($konversi['koefisien']) ? number_format($konversi['koefisien'], 2) : '-'; ?></td>
                            <td><strong><?php echo isset($konversi['angka_kredit']) ? number_format($konversi['angka_kredit'], 3) : '-'; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="no-data">Pengguna ini belum memiliki data konversi</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- EDIT USER MODAL -->
    <?php if ($edit_user): ?>
    <div class="edit-user-modal" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Data Pengguna: <?php echo htmlspecialchars($edit_user['nama']); ?></h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="nip_edit" value="<?php echo htmlspecialchars($edit_user['nip']); ?>">
                
                <div class="edit-form-grid">
                    <div class="edit-form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($edit_user['nama']); ?>" required>
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="no_seri_karpeg">NIP/No. Seri Karpeg</label>
                        <input type="text" id="no_seri_karpeg" name="no_seri_karpeg" value="<?php echo htmlspecialchars($edit_user['no_seri_karpeg']); ?>">
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="tempat_tanggal_lahir">Tempat, Tanggal Lahir</label>
                        <input type="text" id="tempat_tanggal_lahir" name="tempat_tanggal_lahir" value="<?php echo htmlspecialchars($edit_user['tempat_tanggal_lahir']); ?>">
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki" <?php echo ($edit_user['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?php echo ($edit_user['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="pangkat_golongan_tmt">Pangkat/Golongan TMT</label>
                        <input type="text" id="pangkat_golongan_tmt" name="pangkat_golongan_tmt" value="<?php echo htmlspecialchars($edit_user['pangkat_golongan_tmt']); ?>">
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="jabatan_tmt">Jabatan TMT</label>
                        <input type="text" id="jabatan_tmt" name="jabatan_tmt" value="<?php echo htmlspecialchars($edit_user['jabatan_tmt']); ?>">
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="unit_kerja">Unit Kerja</label>
                        <input type="text" id="unit_kerja" name="unit_kerja" value="<?php echo htmlspecialchars($edit_user['unit_kerja']); ?>">
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="instansi">Instansi</label>
                        <input type="text" id="instansi" name="instansi" value="<?php echo htmlspecialchars($edit_user['instansi']); ?>">
                    </div>
                    
                    <div class="edit-form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="user" <?php echo ($edit_user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="edit-form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" name="edit_user" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#usersTableBody tr');
            let visibleCount = 0;
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let found = false;
                
                if (cells.length === 1 && cells[0].classList.contains('no-data')) {
                    return;
                }
                
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchTerm)) {
                        found = true;
                    }
                });
                
                if (found) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            const searchInput = document.getElementById('searchInput');
            if (searchTerm && visibleCount === 0) {
                searchInput.style.borderColor = '#ff1744';
                searchInput.style.background = 'rgba(255, 23, 68, 0.05)';
            } else {
                searchInput.style.borderColor = searchTerm ? '#001aff' : 'rgba(0, 26, 255, 0.2)';
                searchInput.style.background = 'linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7))';
            }
        });

        function closeModal() {
            const modal = document.getElementById('userModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    window.location.href = 'admin_dashboard.php';
                }, 300);
            }
        }

        function closeEditModal() {
            const modal = document.getElementById('editUserModal');
            if (modal) {
                modal.style.opacity = '0';
                modal.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    window.location.href = 'admin_dashboard.php';
                }, 300);
            }
        }

        document.addEventListener('click', function(event) {
            const viewModal = document.getElementById('userModal');
            const editModal = document.getElementById('editUserModal');
            
            if (viewModal && event.target === viewModal) {
                closeModal();
            }
            
            if (editModal && event.target === editModal) {
                closeEditModal();
            }
        });

        setInterval(function() {
            if (!document.getElementById('userModal') && !document.getElementById('editUserModal')) {
                location.reload();
            }
        }, 300000);

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('PERHATIAN: Menghapus user akan menghapus semua data konversi mereka. Lanjutkan?')) {
                    e.preventDefault();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');
            
            statNumbers.forEach(stat => {
                const finalNumber = parseInt(stat.textContent);
                let currentNumber = 0;
                const increment = finalNumber / 50;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        stat.textContent = finalNumber;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(currentNumber);
                    }
                }, 20);
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeEditModal();
            }
            
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
                document.getElementById('searchInput').select();
            }
        });
    </script>
</body>
</html>
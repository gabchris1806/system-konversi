<?php
session_start();
include "db.php";

// Cek login user
if (!isset($_SESSION['nip'])) {
    header("Location: login.php");
    exit();
}

// Redirect admin ke admin dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$nip = $_SESSION['nip'];

// Ambil data user dari database - FIXED SQL INJECTION
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE nip = ?");
$stmt->bind_param("s", $nip);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
    
        .navbar {
            position: relative;
            z-index: 1000;
        }
        
        .dropdown {
            z-index: 1001;
        }
        
        .tab-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            position: relative;
            z-index: 100;
        }
        
        .tab-bar {
            display: flex;
            background: white;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 0;
            position: relative;
            z-index: 101;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            border-right: 1px solid #dee2e6;
            position: relative;
            font-family: 'Poppins', sans-serif;
        }
        
        .tab-button:last-child {
            border-right: none;
        }
        
        .tab-button:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .tab-button.active {
            background: #28a745;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: #28a745;
        }
        
        .tab-content {
            background: white;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 25px;
            min-height: 400px;
        }
        
        .form-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .form-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dashboard-content {
            max-width: none;
            margin: 0;
            background: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        .form-content h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }

        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.3s ease;
            cursor: pointer;
        }

        .form-group select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .form-group select:hover {
            border-color: #28a745;
        }

        .format3-container {
            margin-top: 20px;
        }

        .format3-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .format3-table th, .format3-table td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
            font-size: 13px;
        }

        .format3-table thead th {
            background-color: #f8f9fa;
            font-weight: bold;
            position: sticky;
            top: 0;
        }

        .format3-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .format3-table tbody tr:hover {
            background-color: #e8f5e8;
        }

        .total-row td {
            border: 1px solid #28a745 !important;
            background-color: #28a745 !important;
            color: white !important;
            font-weight: bold !important;
            padding: 12px 8px !important;
        }

        .format3-table tfoot {
            display: table-footer-group !important;
        }

        .format3-table tfoot tr {
            background-color: #28a745 !important;
            color: white !important;
            font-weight: bold !important;
        }

        .format3-table tfoot td {
            border: 1px solid #28a745 !important;
            background-color: #28a745 !important;
            color: white !important;
            padding: 12px 8px !important;
        }

        .row-number {
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
        }

        .editable-cell {
            position: relative;
            cursor: pointer;
            min-height: 30px;
            background-color: #fff3cd;
            transition: all 0.3s ease;
        }

        .editable-cell:hover {
            background-color: #ffeaa7;
            transform: scale(1.02);
        }

        .editable-cell:hover::after {
            content: " ";
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 12px;
            opacity: 0.7;
        }

        .calculated-cell {
            background-color: #d4edda;
            font-weight: bold;
        }

        .cell-input {
            width: 100%;
            border: none;
            background: transparent;
            text-align: center;
            font-size: 13px;
            padding: 4px;
            outline: none;
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
        }

        .format3-table tfoot tr {
            background-color: #28a745 !important;
            color: white;
            font-weight: bold;
        }

        .format3-table tfoot td {
            border-color: #28a745;
        }

        .description-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .editable-description {
            background-color: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .editable-description:hover {
            background-color: #ffeaa7;
        }

        .remove-row-btn {
            opacity: 0;
            transition: opacity 0.3s;
            margin-left: 10px;
            padding: 2px 6px;
            background: #757575;
            color: white;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
        }

        .description-container:hover .remove-row-btn {
            opacity: 1;
        }

        .remove-row-btn:hover {
            background: #757575 !important;
        }

        #add-performance-row {
            padding: 8px 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        #add-performance-row:hover {
            background: #218838 !important;
            transform: translateY(-1px);
        }

        .keterangan-cell {
            background-color: #e8f4f8 !important;
            cursor: text;
            min-height: 30px;
            text-align: left !important;
            padding: 8px !important;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .keterangan-cell:hover {
            background-color: #d1ecf1 !important;
            border-color: #bee5eb;
        }

        .keterangan-cell:focus {
            outline: none;
            background-color: #d4edda !important;
            border-color: #28a745;
            box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
        }

        .keterangan-cell[contenteditable="true"]:empty::before {
            content: "Klik untuk menambah keterangan...";
            color: #6c757d;
            font-style: italic;
        }

        .keterangan-cell:focus::before {
            content: none;
        }

        .alert {
            padding: 15px;
            margin: 20px;
            border-radius: 8px;
            font-weight: 500;
            position: relative;
            animation: slideDown 0.3s ease-out;
            transition: all 0.5s ease-out;
        }

        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-close {
            position: absolute;
            top: 15px;
            right: 20px;
            cursor: pointer;
            font-size: 20px;
            color: inherit;
            opacity: 0.7;
        }

        .alert-close:hover {
            opacity: 1;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.fade-out {
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none;
        }

        /* ===== FORMAT 2 DELETE BUTTON STYLING ===== */
        .delete-row-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .delete-row-btn:hover {
            background: #c82333;
            transform: scale(1.05);
        }

        .format2-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .format2-table th, .format2-table td {
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: center;
            vertical-align: middle;
            font-size: 13px;
        }

        .format2-table thead th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .format2-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .format2-table tbody tr:hover {
            background-color: #e8f5e8;
        }

        .no-data-message, .loading {
            color: #6c757d;
            font-style: italic;
            padding: 20px;
            text-align: center;
        }

        .summary-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }

        .summary-row {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .summary-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-item label {
            font-weight: bold;
            color: #495057;
        }

        .summary-item span {
            background: white;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            font-weight: 500;
            color: #28a745;
        }

        /* ===== FORMAT 2 ENHANCED STYLING ===== */
        .format2-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .format2-table th {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 12px;
            text-align: center;
            border-bottom: 3px solid #1e7e34;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .format2-table td {
            padding: 12px 10px;
            text-align: center;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .format2-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .format2-table tbody tr:hover {
            background-color: #e8f5e8;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);
        }

        /* Editable field styling */
        .editable-field {
            position: relative;
            cursor: pointer;
            padding: 8px 12px !important;
            border-radius: 4px;
            min-height: 30px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .editable-field::after {
            content: "✏️";
            opacity: 0;
            position: absolute;
            top: 2px;
            right: 4px;
            font-size: 10px;
            transition: opacity 0.3s ease;
        }

        .editable-field:hover::after {
            opacity: 0.7;
        }

        /* Calculated field (non-editable) */
        .calculated-field {
            background-color: #e2e3e5;
            color: #e2e3e5;
            font-weight: bold;
        }

        /* Non-editable field */
        .non-editable {
            background-color: #f8f9fa;
            color: #e2e3e5;
            font-weight: 500;
        }

        /* Action cell styling */
        .action-cell {
            width: 80px;
            text-align: center !important;
        }

        .delete-row-btn {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
            padding: 6px 10px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
            min-width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .delete-row-btn:hover {
            background: #fef2f2;
            border-color: #ef4444;
            color: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
        }

        .delete-row-btn:active {
            transform: scale(0.95);
        }

        /* Input field styling for inline editing */
        .cell-input-f2 {
            width: 100% !important;
            border: 2px solid #28a745 !important;
            padding: 6px 8px !important;
            text-align: center !important;
            background: #fff3cd !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1) !important;
            transition: all 0.3s ease !important;
        }

        .cell-input-f2:focus {
            border-color: #20c997 !important;
            box-shadow: 0 0 0 3px rgba(32, 201, 151, 0.2) !important;
            background: #e7f3ff !important;
        }

        /* Enhanced hover effects for editable fields */
        .editable-field:hover {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
            cursor: pointer !important;
            border: 1px dashed #28a745 !important;
            transform: scale(1.02) !important;
            box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2) !important;
        }

        /* Loading state */
        .loading-cell {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            color: #666;
            font-style: italic;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Success animation */
        .cell-success {
            animation: success-pulse 0.6s ease-in-out;
        }

        @keyframes success-pulse {
            0% { background-color: #d4edda; transform: scale(1); }
            50% { background-color: #28a745; color: white; transform: scale(1.05); }
            100% { background-color: #d4edda; transform: scale(1); }
        }

        /* Format 2 header enhancement */
        .format2-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .format2-instructions {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #0066cc;
        }

        .format2-instructions .icon {
            display: inline-block;
            margin-right: 8px;
            font-size: 16px;
        }

        /* Responsive table for smaller screens */
        @media (max-width: 768px) {
            .format2-table {
                font-size: 12px;
            }
            
            .format2-table th,
            .format2-table td {
                padding: 8px 6px;
            }
            
            .delete-row-btn {
                padding: 4px 6px;
                font-size: 12px;
                min-width: 28px;
                height: 28px;
            }
            
            .editable-field::after {
                display: none; /* Hide edit icon on mobile */
            }
        }
        
    </style>
</head>
<body class="dashboard-page">
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-left">
            <img src="ptpn.png" class="logo" alt="Logo">
            <span class="app-title">PT PERKEBUNAN NUSANTARA IV</span>
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
                <span class="user-name"><?php echo htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="user-nip"><?php echo htmlspecialchars($user['nip'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error" id="session-error-alert">
            <span class="alert-close" onclick="hideAlert('session-error-alert')">&times;</span>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" id="session-success-alert">
            <span class="alert-close" onclick="hideAlert('session-success-alert')">&times;</span>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <!-- TAB CONTAINER -->
    <div class="tab-container">
        <!-- TAB BAR -->
        <div class="tab-bar">
            <button class="tab-button active" data-tab="format1">Format 1 - Konversi</button>
            <button class="tab-button" data-tab="format2">Format 2 - Data Tahunan</button>
            <button class="tab-button" data-tab="format3">Format 3 - Laporan</button>
        </div>

        <!-- TAB CONTENT -->
        <div class="tab-content">
            <!-- FORMAT 1 CONTENT -->
            <div class="form-content active" id="format1">
                <h2>Form Konversi</h2>
                <form class="konversi-form" action="simpan_konversi.php" method="POST">
                    <div class="row">
                        <div class="form-group">
                            <label for="bulan_awal">Bulan Awal</label>
                            <select id="bulan_awal" name="bulan_awal" required>
                                <option value="">-- Pilih Bulan Awal --</option>
                                <option value="Januari">Januari</option>
                                <option value="Februari">Februari</option>
                                <option value="Maret">Maret</option>
                                <option value="April">April</option>
                                <option value="Mei">Mei</option>
                                <option value="Juni">Juni</option>
                                <option value="Juli">Juli</option>
                                <option value="Agustus">Agustus</option>
                                <option value="September">September</option>
                                <option value="Oktober">Oktober</option>
                                <option value="November">November</option>
                                <option value="Desember">Desember</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bulan_akhir">Bulan Akhir</label>
                            <select id="bulan_akhir" name="bulan_akhir" required>
                                <option value="">-- Pilih Bulan Akhir --</option>
                                <option value="Januari">Januari</option>
                                <option value="Februari">Februari</option>
                                <option value="Maret">Maret</option>
                                <option value="April">April</option>
                                <option value="Mei">Mei</option>
                                <option value="Juni">Juni</option>
                                <option value="Juli">Juli</option>
                                <option value="Agustus">Agustus</option>
                                <option value="September">September</option>
                                <option value="Oktober">Oktober</option>
                                <option value="November">November</option>
                                <option value="Desember">Desember</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tahun">Tahun</label>
                            <input type="number" id="tahun" name="tahun" placeholder="2025" required>
                        </div>
                    </div>

                    <h3>Hasil Penilaian</h3>
                    <div class="row">
                        <div class="form-group">
                            <label for="predikat">Predikat</label>
                            <input type="text" id="predikat" name="predikat" placeholder="Contoh: Baik" required>
                        </div>
                        <div class="form-group">
                            <label for="persentase">Persentase (%)</label>
                            <div class="input-wrapper">
                                <input type="number" id="persentase" name="persentase" placeholder="Contoh: 5" required>
                                <span>/12</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="koefisien">Koefisien</label>
                            <input type="number" id="koefisien" name="koefisien" value="12.50" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="angka_kredit">Angka Kredit</label>
                            <input type="number" id="angka_kredit" name="angka_kredit" placeholder="" step="0.01" readonly>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Simpan</button>
                </form>
            </div>

            <!-- FORMAT 2 CONTENT -->
            <div class="form-content" id="format2">
                <div class="format2-header">
                    <h2 style="margin: 0; color: #333;">Format 2 - Data Tahunan</h2>
                </div>
                
                <div class="year-selector-container">
                    <label for="tahun_pilih_f2">Tahun:</label>
                    <select id="tahun_pilih_f2" required>
                        <option value="">Pilih Tahun</option>
                        <?php
                        $current_year = date('Y');
                        for ($i = 1990; $i <= $current_year + 20; $i++) {
                            echo "<option value='".$i."'>".$i."</option>";
                        }
                        ?>
                    </select>
                    <button type="button" id="btn-lihat-f2" class="btn-lihat">Lihat Data</button>
                </div>

                <!-- Summary Data -->
                <div class="summary-container" id="summary-container-f2" style="display:none;">
                    <div class="summary-row">
                        <div class="summary-item">
                            <label>Angka Dasar:</label>
                            <span id="angka-dasar-f2">50,0</span>
                        </div>
                        <div class="summary-item">
                            <label>Koefisien Per Tahun:</label>
                            <span id="koefisien-per-tahun-f2">12.5</span>
                        </div>
                        <div class="summary-item">
                            <label>Angka Kredit Yang Didapat:</label>
                            <span id="angka-kredit-didapat-f2">50.0</span>
                        </div>
                    </div>
                </div>

                <!-- Tabel Format 2 -->
                <table class="format2-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Tahun</th>
                            <th style="width: 20%;">Periode (Bulan)</th>
                            <th style="width: 15%;">Predikat</th>
                            <th style="width: 15%;">Persentase</th>
                            <th style="width: 15%;">Koefisien</th>
                            <th style="width: 15%;">Angka Kredit</th>
                            <th style="width: 10%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-format2">
                        <tr>
                            <td colspan="7" class="no-data-message">Silakan pilih tahun terlebih dahulu</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- FORMAT 3 CONTENT -->
            <div class="form-content" id="format3">
                <h2>Format 3 - Laporan</h2>
                
                <div class="year-selector-container">
                    <label for="tahun_pilih_f3">Tahun:</label>
                    <select id="tahun_pilih_f3" required>
                        <option value="">Pilih Tahun</option>
                        <?php
                        for ($i = 1990; $i <= $current_year + 20; $i++) {
                            echo "<option value='".$i."'>".$i."</option>";
                        }
                        ?>
                    </select>
                    <button type="button" id="btn-lihat-f3" class="btn-lihat">Lihat</button>
                </div>

                <!-- Tabel Format 3 - HASIL PENILAIAN ANGKA KREDIT -->
                <div class="format3-container" id="format3-container" style="display:none;">
                    <h3 style="text-align: center; margin: 20px 0; font-weight: bold;">HASIL PENILAIAN ANGKA KREDIT</h3>
                    
                    <div class="table-controls">
                        <button type="button" class="add-row-button" onclick="addPerformanceRow()" title="Tambah Baris">
                            +
                        </button>
                    </div>

                    <table class="format3-table" id="main-performance-table">
                        <thead>
                            <tr>
                                <th>II</th>
                                <th>HASIL PENILAIAN KINERJA</th>
                                <th>LAMA</th>
                                <th>BARU</th>
                                <th>JUMLAH</th>
                                <th>KETERANGAN</th>
                            </tr>
                        </thead>
                        <tbody id="performance-table-body">
                            <tr data-row-id="1">
                                <td rowspan="7" class="row-number">1</td>
                                <td style="text-align: left; padding-left: 10px;" class="row-description">
                                    <div class="description-container">
                                        <span class="description-text">1. AK Dasar yang diberikan</span>
                                    </div>
                                </td>
                                <td class="editable-cell" data-type="ak_dasar_lama">-</td>
                                <td class="editable-cell" data-type="ak_dasar_baru">50.00</td>
                                <td class="calculated-cell" data-type="ak_dasar_jumlah">50.00</td>
                                <td class="editable-cell keterangan-cell" data-type="keterangan_1" contenteditable="true">-</td>
                            </tr>
                            <tr data-row-id="2">
                                <td style="text-align: left; padding-left: 10px;" class="row-description">
                                    <div class="description-container">
                                        <span class="description-text">2. AK JF Lama</span>
                                    </div>
                                </td>
                                <td class="editable-cell" data-type="ak_jf_lama">-</td>
                                <td class="editable-cell" data-type="ak_jf_baru">0.00</td>
                                <td class="calculated-cell" data-type="ak_jf_jumlah">0.00</td>
                                <td class="editable-cell keterangan-cell" data-type="keterangan_2" contenteditable="true">-</td>
                            </tr>
                            <tr data-row-id="3">
                                <td style="text-align: left; padding-left: 10px;" class="row-description">
                                    <div class="description-container">
                                        <span class="description-text">3. AK Penyesuaian/ Penyetaraan</span>
                                    </div>
                                </td>
                                <td class="editable-cell" data-type="ak_penyesuaian_lama">-</td>
                                <td class="editable-cell" data-type="ak_penyesuaian_baru">0.00</td>
                                <td class="calculated-cell" data-type="ak_penyesuaian_jumlah">0.00</td>
                                <td class="editable-cell keterangan-cell" data-type="keterangan_3" contenteditable="true">-</td>
                            </tr>
                            <tr data-row-id="4">
                                <td style="text-align: left; padding-left: 10px;" class="row-description">
                                    <div class="description-container">
                                        <span class="description-text">4. AK Konversi</span>
                                    </div>
                                </td>
                                <td class="editable-cell" data-type="ak_konversi_lama">0,00</td>
                                <td class="editable-cell" data-type="ak_konversi_baru" id="ak_konversi_from_form1">12.50</td>
                                <td class="calculated-cell" data-type="ak_konversi_jumlah">12.50</td>
                                <td class="editable-cell keterangan-cell" data-type="keterangan_4" contenteditable="true">Dari konversi</td>
                            </tr>
                            <tr data-row-id="5">
                                <td style="text-align: left; padding-left: 10px;" class="row-description">
                                    <div class="description-container">
                                        <span class="description-text">5. AK yang diperoleh dari peningkatan pendidikan</span>
                                    </div>
                                </td>
                                <td class="editable-cell" data-type="ak_pendidikan_lama">-</td>
                                <td class="editable-cell" data-type="ak_pendidikan_baru">-</td>
                                <td class="calculated-cell" data-type="ak_pendidikan_jumlah">0.00</td>
                                <td class="editable-cell keterangan-cell" data-type="keterangan_5" contenteditable="true">-</td>
                            </tr>
                            <tr data-row-id="6">
                                <td style="text-align: left; padding-left: 10px;" class="row-description">
                                    <div class="description-container">
                                        <span class="description-text editable-description">6. ................ **)</span>
                                        <button type="button" class="remove-row-btn" onclick="removePerformanceRow(6)">×</button>
                                    </div>
                                </td>
                                <td class="editable-cell" data-type="ak_lainnya_lama">-</td>
                                <td class="editable-cell" data-type="ak_lainnya_baru">-</td>
                                <td class="calculated-cell" data-type="ak_lainnya_jumlah">0.00</td>
                                <td class="editable-cell keterangan-cell" data-type="keterangan_6" contenteditable="true">-</td>
                            </tr>
                            <!-- BARIS TOTAL DIPINDAHKAN KE TBODY -->
                            <tr class="total-row" style="font-weight: bold; background-color: #28a745 !important; color: white !important;">
                                <td colspan="2" style="text-align: left; padding-left: 10px; background-color: #28a745 !important; color: white !important;">
                                    JUMLAH ANGKA KREDIT KUMULATIF
                                </td>
                                <td id="total_lama_kumulatif" style="background-color: #28a745 !important; color: white !important;">0,00</td>
                                <td id="total_baru_kumulatif" style="background-color: #28a745 !important; color: white !important;">75,00</td>
                                <td id="total_jumlah_kumulatif" style="background-color: #28a745 !important; color: white !important;">75,00</td>
                                <td style="background-color: #28a745 !important; color: white !important;"></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- TABEL KEDUA  -->
                    <table class="format3-table" style="margin-top: 30px;">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Keterangan</th>
                                <th style="width: 25%;">Pangkat</th>
                                <th style="width: 25%;">Jenjang Jabatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="text-align: left; padding: 12px;">Angka Kredit minimal yang harus dipenuhi untuk kenaikan pangkat/ jenjang</td>
                                <td class="editable-cell" data-type="ak_minimal_pangkat">50</td>
                                <td class="editable-cell" data-type="ak_minimal_jenjang">50</td>
                            </tr>
                            <tr>
                                <td id="keterangan-pangkat" style="text-align: left; padding: 12px;">Kelebihan/ kekurangan <sup>)</sup> Angka Kredit yang harus dicapai untuk kenaikan pangkat</td>
                                <td class="calculated-cell" data-type="kelebihan_pangkat">25.000</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td id="keterangan-jenjang" style="text-align: left; padding: 12px;">Kelebihan/kekurangan <sup>)</sup> Angka Kredit yang harus dicapai untuk peningkatan jenjang</td>
                                <td></td>
                                <td class="calculated-cell" data-type="kelebihan_jenjang">25.000</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- UPDATED BOTTOM SECTION: KETERANGAN WITH CETAK LAPORAN BUTTON & WARNING MOVED DOWN -->
                    <div class="format3-bottom-section no-print">
                        <div class="format3-notes-section no-print">
                            <h4>Keterangan:</h4>
                            <p>**) Diisi dengan angka kredit yang diperoleh dari hasil konversi</p>
                            <p>***) Diisi dengan jenis kegiatan lainnya yang dapat dinilai angka kreditnya</p>
                            
                            <!-- CETAK LAPORAN BUTTON - MOVED TO RIGHT -->
                            <div class="format3-actions no-print" style="text-align: right; margin: 20px 0;">
                                <button type="button" class="report-btn btn-print" onclick="cetakLaporan()" style="padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px;">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 8px; vertical-align: middle;">
                                        <path d="M19 8h-1V3H6v5H5c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h2v3h10v-3h2c1.1 0 2-.9 2-2v-6c0-1.1-.9-2-2-2zM8 5h8v3H8V5zm8 12v2H8v-2h8zm2-2v-2H6v2H6v-2H4v-2h16v2h-2v2z"/>
                                        <rect x="6" y="11" width="12" height="2"/>
                                    </svg>
                                    Cetak Laporan
                                </button>
                            </div>
                        </div>

                        <!-- WARNING MESSAGE - MOVED BELOW -->
                        <div class="format3-warning" style="display: none; background: #fff3cd; border: 1px solid #ffeaa7; padding: 12px; border-radius: 5px; margin-top: 15px;">
                            <span style="color: #856404; font-weight: bold;">
                                Laporan ini dibuat berdasarkan data konversi yang telah diinput untuk tahun <span id="current-year-display">2025</span>
                            </span>
                        </div>
                    </div>
                </div>    
            </div>
        </div>
    </div>

    <script>
    // Global variable untuk tracking row counter
    let performanceRowCounter = 6;

    // ===== ALERT FUNCTIONS - FIXED =====
    function hideAlert(element) {
        if (typeof element === 'string') {
            element = document.getElementById(element);
        }
        
        if (element) {
            element.classList.add('fade-out');
            setTimeout(() => {
                if (element.parentNode) {
                    element.remove();
                }
            }, 500);
        }
    }

    // ===== TAB SWITCHING =====
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });

    // ===== FORMAT 1: Hitung angka kredit =====
    const persentaseInput = document.getElementById("persentase");
    const koefisienInput = document.getElementById("koefisien");
    const angkaKreditInput = document.getElementById("angka_kredit");

    function hitungAngkaKredit() {
        const nilaiPersentase = parseFloat(persentaseInput.value);
        const nilaiKoefisien = parseFloat(koefisienInput.value);
        if (!isNaN(nilaiPersentase) && !isNaN(nilaiKoefisien)) {
            const persen = (nilaiPersentase / 12) * 100;
            let angkaKredit = persen * nilaiKoefisien / 100;
            angkaKreditInput.value = angkaKredit.toFixed(3);
        } else {
            angkaKreditInput.value = "";
        }
    }

    persentaseInput.addEventListener("input", hitungAngkaKredit);
    koefisienInput.addEventListener("input", hitungAngkaKredit);

    // ===== FORMAT 2: DELETE FUNCTION =====
    function deleteKonversiData(id) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            $.ajax({
                url: 'delete_konversi.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccessMessage('Data berhasil dihapus!');
                        // Reload data Format 2
                        $("#btn-lihat-f2").click();
                    } else {
                        showErrorMessage('Gagal menghapus data: ' + response.message);
                    }
                },
                error: function() {
                    showErrorMessage('Terjadi kesalahan saat menghapus data');
                }
            });
        }
    }

    // ===== CETAK LAPORAN FUNCTION =====
    function cetakLaporan() {
        const tahun = document.getElementById("tahun_pilih_f3").value;
        
        if (!tahun) {
            alert("Silakan pilih tahun terlebih dahulu!");
            return;
        }

        // Generate current date in Indonesian format
        const now = new Date();
        const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        
        const day = now.getDate();
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        
        const formattedDate = `${day} ${month} ${year}`;
        
        // CREATE SIGNATURE ELEMENT DINAMIS HANYA SAAT PRINT
        const signatureHTML = `
            <div class="signature-section" id="temp-signature-section" style="margin-top: 50px; display: flex; justify-content: flex-end; padding-right: 50px; page-break-inside: avoid;">
                <div class="signature-container" style="text-align: left; width: 300px;">
                    <div class="signature-location-date" style="font-size: 14px; margin-bottom: 5px;">
                        Ditetapkan di Medan<br>
                        Pada tanggal ${formattedDate}
                    </div>
                    <div class="signature-title" style="font-size: 14px; font-weight: normal; margin-bottom: 10px;">
                        Pejabat Penilai Kinerja
                    </div>
                    <div style="height: 60px;"></div>
                    <div class="signature-name" style="font-size: 14px; font-weight: bold; margin-bottom: 2px; text-decoration: underline;">
                        Dr. Poltak Evencus Hutajulu, S.T., M.T.
                    </div>
                    <div class="signature-nip" style="font-size: 12px;">
                        NIP. 198211220080301001
                    </div>
                </div>
            </div>
        `;
        
        // HAPUS SEMENTARA ELEMENT KETERANGAN DAN SIGNATURE LAMA
        const keteranganSection = document.querySelector('.format3-bottom-section');
        const oldSignatureSection = document.querySelector('.signature-section');
        const tempParent = keteranganSection ? keteranganSection.parentNode : null;
        const tempNextSibling = keteranganSection ? keteranganSection.nextSibling : null;
        
        // Remove keterangan section
        if (keteranganSection) {
            keteranganSection.remove();
        }
        
        // Remove old signature if exists
        if (oldSignatureSection) {
            oldSignatureSection.remove();
        }
        
        // ADD SIGNATURE TEMPORARILY UNTUK PRINT
        const format3Container = document.querySelector('.format3-container');
        if (format3Container) {
            format3Container.insertAdjacentHTML('beforeend', signatureHTML);
        }

        // Print
        window.print();

        // CLEANUP: Remove temporary signature dan restore keterangan section
        setTimeout(() => {
            const tempSignature = document.getElementById('temp-signature-section');
            if (tempSignature) {
                tempSignature.remove();
            }
            
            // Restore keterangan section
            if (keteranganSection && tempParent) {
                if (tempNextSibling) {
                    tempParent.insertBefore(keteranganSection, tempNextSibling);
                } else {
                    tempParent.appendChild(keteranganSection);
                }
            }
        }, 1000);
    }

    // ===== ADD/REMOVE ROWS FUNCTIONS WITH UPDATED KETERANGAN =====
    function addPerformanceRow() {
        performanceRowCounter++;
        const newRowId = performanceRowCounter;
        
        const newRow = '<tr data-row-id="' + newRowId + '">' +
            '<td style="text-align: left; padding-left: 10px;" class="row-description">' +
                '<div class="description-container">' +
                    '<span class="description-text editable-description" onclick="editDescription(this)">' + newRowId + '. Item Baru</span>' +
                    '<button type="button" class="remove-row-btn" onclick="removePerformanceRow(' + newRowId + ')">×</button>' +
                '</div>' +
            '</td>' +
            '<td class="editable-cell" data-type="ak_custom_' + newRowId + '_lama">-</td>' +
            '<td class="editable-cell" data-type="ak_custom_' + newRowId + '_baru">0.00</td>' +
            '<td class="calculated-cell" data-type="ak_custom_' + newRowId + '_jumlah">0.00</td>' +
            '<td class="editable-cell keterangan-cell" data-type="keterangan_' + newRowId + '" contenteditable="true">-</td>' +
        '</tr>';
        
        // Insert sebelum baris total
        $('.total-row').before(newRow);
        
        // Update rowspan dari kolom pertama
        updateRowspan();
        
        // Recalculate totals
        calculateFormat3Totals();
    }

    function removePerformanceRow(rowId) {
        const rowsCount = $('#performance-table-body tr:not(.total-row)').length;
        
        if (rowsCount <= 1) {
            alert('Minimal harus ada 1 baris!');
            return;
        }
        
        if (confirm('Apakah Anda yakin ingin menghapus baris ini?')) {
            $('tr[data-row-id="' + rowId + '"]').remove();
            
            // Update numbering
            updateRowNumbering();
            
            // Update rowspan
            updateRowspan();
            
            // Recalculate totals
            calculateFormat3Totals();
        }
    }

    function updateRowspan() {
        const dataRows = $('#performance-table-body tr:not(.total-row)').length;
        $('.row-number').attr('rowspan', dataRows + 1); // +1 untuk baris total
    }

    function updateRowNumbering() {
        $('#performance-table-body tr:not(.total-row)').each(function(index) {
            const newNumber = index + 1;
            $(this).attr('data-row-id', newNumber);
            
            // Update text in description
            const descriptionSpan = $(this).find('.description-text');
            let currentText = descriptionSpan.text();
            let newText = currentText.replace(/^\d+\./, newNumber + '.');
            descriptionSpan.text(newText);
            
            // Update remove button onclick
            $(this).find('.remove-row-btn').attr('onclick', 'removePerformanceRow(' + newNumber + ')');
            
            // Update data-type attributes
            const lamaCell = $(this).find('.editable-cell[data-type*="_lama"]');
            const baruCell = $(this).find('.editable-cell[data-type*="_baru"]');
            const jumlahCell = $(this).find('.calculated-cell[data-type*="_jumlah"]');
            const keteranganCell = $(this).find('.keterangan-cell');
            
            if (newNumber > 6) {
                lamaCell.attr('data-type', 'ak_custom_' + newNumber + '_lama');
                baruCell.attr('data-type', 'ak_custom_' + newNumber + '_baru');
                jumlahCell.attr('data-type', 'ak_custom_' + newNumber + '_jumlah');
                keteranganCell.attr('data-type', 'keterangan_' + newNumber);
            }
        });
    }

    function editDescription(element) {
        const currentText = $(element).text();
        const input = $('<input type="text" value="' + currentText + '" style="background: transparent; border: 1px solid #28a745; padding: 2px 5px; border-radius: 3px; width: auto; min-width: 200px;">');
        
        $(element).replaceWith(input);
        input.focus().select();
        
        input.on('blur keypress', function(e) {
            if (e.type === 'blur' || e.which === 13) {
                const newText = $(this).val();
                const newSpan = $('<span class="description-text editable-description" onclick="editDescription(this)">' + newText + '</span>');
                $(this).replaceWith(newSpan);
            }
        });
    }

    // ===== KETERANGAN CELL FUNCTIONS =====
    function saveKeteranganData() {
        const keteranganData = {};
        
        $('.keterangan-cell').each(function() {
            const dataType = $(this).attr('data-type');
            const content = $(this).text();
            keteranganData[dataType] = content;
        });
        
        localStorage.setItem('keterangan_data', JSON.stringify(keteranganData));
        console.log('Keterangan data saved:', keteranganData);
    }

    function loadKeteranganData() {
        const savedData = localStorage.getItem('keterangan_data');
        
        if (savedData) {
            const keteranganData = JSON.parse(savedData);
            
            $('.keterangan-cell').each(function() {
                const dataType = $(this).attr('data-type');
                if (keteranganData[dataType]) {
                    $(this).text(keteranganData[dataType]);
                }
            });
            
            console.log('Keterangan data loaded:', keteranganData);
        }
    }

    // Auto-save when keterangan content changes
    $(document).on('blur', '.keterangan-cell', function() {
        saveKeteranganData();
    });

    // ===== FORMAT 2: AJAX Load Data =====
    $("#btn-lihat-f2").click(function(e){
        e.preventDefault();
        let tahun = $("#tahun_pilih_f2").val();
        
        console.log("Button F2 clicked, tahun:", tahun);
        
        if(tahun === ""){
            alert("Pilih tahun terlebih dahulu!");
            return;
        }

        // Show loading
        $("#tabel-format2").html('<tr><td colspan="7" class="loading">Memuat data...</td></tr>');
        $("#summary-container-f2").hide();
        
        $.ajax({
            url: "load_form2.php",
            type: "POST",
            data: {tahun_pilih: tahun},
            dataType: 'json',
            success: function(response) {
                console.log("Response dari server F2:", response);

                if(response.status === 'success') {
                    $("#tabel-format2").html(response.table_data);

                    if(response.summary_data) {
                        $("#koefisien-per-tahun-f2").text(response.summary_data.koefisien_per_tahun);
                        $("#angka-kredit-didapat-f2").text(response.summary_data.angka_kredit_yang_didapat);
                        $("#angka-dasar-f2").text("50,0");
                        $("#summary-container-f2").show();
                    }
                } else {
                    $("#tabel-format2").html(response.table_data);
                    $("#summary-container-f2").hide();
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error F2:", status, error);
                console.log("Response Text F2:", xhr.responseText);
                console.log("Status Code:", xhr.status);
                $("#tabel-format2").html('<tr><td colspan="7" class="no-data-message" style="color: red;">Terjadi kesalahan saat memuat data</td></tr>');
                $("#summary-container-f2").hide();
            }
        });
    });
    
    // ===== FORMAT 2: INLINE EDITING FUNCTIONS =====//
        // UPDATE: Make Format 2 fields editable when clicked - ENHANCED VERSION
        $(document).on('click', '.editable-field', function() {
            if ($(this).find('input').length > 0) return; // Already editing
            
            const currentValue = $(this).text().replace('/12', ''); // Remove /12 from persentase
            const field = $(this).attr('data-field');
            const rowKey = $(this).closest('tr').attr('data-row-key');
            
            let inputType = 'text';
            let step = '';
            let inputOptions = '';
            
            // Determine input type based on field
            switch(field) {
                case 'persentase':
                    inputType = 'number';
                    inputOptions = 'min="1" max="12"';
                    break;
                case 'koefisien':
                    inputType = 'number';
                    inputOptions = 'step="0.01" min="0.01"';
                    break;
                case 'tahun':
                    inputType = 'number';
                    inputOptions = 'min="1900" max="2100"';
                    break;
                case 'periode':
                    // For periode, we'll use a text input with validation
                    inputType = 'text';
                    inputOptions = 'placeholder="Contoh: April atau April - Juni"';
                    break;
                case 'predikat':
                    inputType = 'text';
                    inputOptions = 'placeholder="Masukkan predikat"';
                    break;
                default:
                    inputType = 'text';
            }
            
            // Create input element
            const input = $(`<input type="${inputType}" ${inputOptions} class="cell-input-f2" value="${currentValue}" style="width: 100%; border: 2px solid #28a745; padding: 4px; text-align: center; background: #fff3cd; border-radius: 4px;">`);
            
            // Replace cell content with input
            $(this).html(input);
            input.focus().select();
            
            // Handle save on blur or Enter
            input.on('blur keypress', function(e) {
                if (e.type === 'blur' || e.which === 13) {
                    const newValue = $(this).val().trim();
                    const cell = $(this).parent();
                    
                    // Validate input based on field type
                    if (!validateFieldInput(field, newValue, currentValue, cell)) {
                        return;
                    }
                    
                    if (newValue !== currentValue) {
                        // Save to database
                        saveFieldValue(rowKey, field, newValue, cell, currentValue);
                    } else {
                        // No change, restore original value
                        const displayValue = getDisplayValue(field, newValue);
                        cell.text(displayValue);
                    }
                }
            });
            
            // Handle Escape key to cancel
            input.on('keyup', function(e) {
                if (e.which === 27) { // Escape key
                    const displayValue = getDisplayValue(field, currentValue);
                    $(this).parent().text(displayValue);
                }
            });
        });

        // Helper function to validate field input
        function validateFieldInput(field, newValue, currentValue, cell) {
            switch(field) {
                case 'persentase':
                    if (newValue < 1 || newValue > 12 || !Number.isInteger(Number(newValue))) {
                        showErrorMessage('Persentase harus berupa bilangan bulat antara 1-12');
                        cell.text(getDisplayValue(field, currentValue));
                        return false;
                    }
                    break;
                    
                case 'koefisien':
                    if (isNaN(newValue) || Number(newValue) <= 0) {
                        showErrorMessage('Koefisien harus berupa angka positif');
                        cell.text(getDisplayValue(field, currentValue));
                        return false;
                    }
                    break;
                    
                case 'tahun':
                    if (isNaN(newValue) || Number(newValue) < 1900 || Number(newValue) > 2100) {
                        showErrorMessage('Tahun harus berupa angka antara 1900-2100');
                        cell.text(getDisplayValue(field, currentValue));
                        return false;
                    }
                    break;
                    
                case 'periode':
                    // Validate periode format
                    const validMonths = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 
                                    'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];
                    const periodeParts = newValue.toLowerCase().split(' - ');
                    let isValidPeriode = true;
                    
                    for (let month of periodeParts) {
                        month = month.trim();
                        if (!validMonths.includes(month)) {
                            isValidPeriode = false;
                            break;
                        }
                    }
                    
                    if (!isValidPeriode || newValue.trim() === '') {
                        showErrorMessage('Format periode tidak valid. Gunakan nama bulan atau range bulan (contoh: "April" atau "April - Juni")');
                        cell.text(getDisplayValue(field, currentValue));
                        return false;
                    }
                    break;
                    
                case 'predikat':
                    if (newValue.trim() === '') {
                        showErrorMessage('Predikat tidak boleh kosong');
                        cell.text(getDisplayValue(field, currentValue));
                        return false;
                    }
                    break;
            }
            return true;
        }

        // Helper function to get display value
        function getDisplayValue(field, value) {
            switch(field) {
                case 'persentase':
                    return value + '/12';
                case 'koefisien':
                    return parseFloat(value).toFixed(2);
                case 'periode':
                    // Capitalize first letter of each word
                    return value.split(' ').map(word => 
                        word === '-' ? word : word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
                    ).join(' ');
                default:
                    return value;
            }
        }

        // UPDATE: Enhanced Save field value to database function
        function saveFieldValue(rowKey, field, newValue, cell, originalValue) {
            // Show loading state
            cell.html('<span style="color: #666; font-style: italic;">💾 Menyimpan...</span>');
            
            $.ajax({
                url: 'update_konversi.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    row_key: rowKey,
                    field: field,
                    value: newValue
                }),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Display success and update cell
                        const displayValue = getDisplayValue(field, newValue);
                        cell.text(displayValue);
                        cell.css('background-color', '#d4edda').animate({'background-color': 'transparent'}, 2000);
                        
                        // If persentase or koefisien was updated, update angka_kredit column
                        if ((field === 'persentase' || field === 'koefisien') && response.new_angka_kredit) {
                            const angkaKreditCell = cell.closest('tr').find('.calculated-field');
                            angkaKreditCell.text(response.new_angka_kredit);
                            angkaKreditCell.css('background-color', '#d4edda').animate({'background-color': 'transparent'}, 2000);
                        }
                        
                        showSuccessMessage('Data berhasil diperbarui!');
                        
                        // Refresh summary data if necessary
                        if (field === 'persentase' || field === 'koefisien' || field === 'tahun') {
                            setTimeout(function() {
                                $("#btn-lihat-f2").click();
                            }, 1500);
                        }
                        
                    } else {
                        // Restore original value on error
                        const displayValue = getDisplayValue(field, originalValue);
                        cell.text(displayValue);
                        showErrorMessage(response.message || 'Gagal menyimpan data');
                    }
                },
                error: function(xhr, status, error) {
                    // Restore original value on error
                    const displayValue = getDisplayValue(field, originalValue);
                    cell.text(displayValue);
                    showErrorMessage('Terjadi kesalahan saat menyimpan data');
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                }
            });
        }

    // ===== FORMAT 2: DELETE FUNCTION =====
    function deleteKonversiData(rowKey) {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            $.ajax({
                url: 'delete_konversi.php',
                type: 'POST',
                data: { row_key: rowKey },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccessMessage('Data berhasil dihapus!');
                        // Reload data Format 2
                        $("#btn-lihat-f2").click();
                    } else {
                        showErrorMessage('Gagal menghapus data: ' + response.message);
                    }
                },
                error: function() {
                    showErrorMessage('Terjadi kesalahan saat menghapus data');
                }
            });
        }
    }

    // ===== FORMAT 3: AJAX Load Data - UPDATED WITH DYNAMIC YEAR =====
    $("#btn-lihat-f3").click(function(e){
        e.preventDefault();
        let tahun = $("#tahun_pilih_f3").val();
        
        console.log("Button F3 clicked, tahun:", tahun);
        
        if(tahun === ""){
            alert("Pilih tahun terlebih dahulu!");
            return;
        }
        
        $("#current-year-display").text(tahun);
        console.log("Memuat data Format 3 untuk tahun:", tahun);
        
        $.ajax({
            url: "load_form3.php",
            type: "POST",
            data: {tahun_pilih: tahun},
            dataType: 'json',
            success: function(response) {
                console.log("Response Format 3:", response);
                
                if(response.status === 'success') {
                    $("#format3-container").show();
                    
                    if(response.total_angka_kredit > 0) {
                        $(".editable-cell[data-type='ak_konversi_baru']").text(parseFloat(response.total_angka_kredit).toFixed(2));
                    } else {
                        loadAKKonversiFromFormat1();
                    }
                    
                    calculateFormat3Totals();
                    $(".format3-warning").hide();
                    
                } else if(response.status === 'no_data') {
                    $("#format3-container").show();
                    $(".format3-warning").show();
                } else {
                    $("#format3-container").hide();
                    alert("Terjadi kesalahan: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error Format 3:", status, error);
                console.log("Response Text F3:", xhr.responseText);
                console.log("Status Code:", xhr.status);
                $("#format3-container").hide();
                alert("Terjadi kesalahan saat memuat data Format 3");
            }
        });
    });

    // ===== FORMAT 3: Editable Cells =====
    $(document).on('click', '.editable-cell:not(.keterangan-cell)', function() {
        if ($(this).find('input').length > 0) return;
        
        var currentValue = $(this).text();
        var input = $('<input type="text" class="cell-input" value="' + currentValue + '">');
        
        $(this).html(input);
        input.focus().select();
        
        input.on('blur keypress', function(e) {
            if (e.type === 'blur' || e.which === 13) {
                var newValue = $(this).val();
                $(this).parent().text(newValue);
                calculateFormat3Totals();
            }
        });
    });

    function loadAKKonversiFromFormat1() {
        var angkaKredit = $("#angka_kredit").val();
        if (angkaKredit && angkaKredit !== '') {
            $(".editable-cell[data-type='ak_konversi_baru']").text(parseFloat(angkaKredit).toFixed(2));
        }
    }

    function calculateFormat3Totals() {
        var akDasarLama = parseFloat($(".editable-cell[data-type='ak_dasar_lama']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;
        var akDasarBaru = parseFloat($(".editable-cell[data-type='ak_dasar_baru']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;
        var akKonversiLama = parseFloat($(".editable-cell[data-type='ak_konversi_lama']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;
        var akKonversiBaru = parseFloat($(".editable-cell[data-type='ak_konversi_baru']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;

        var akDasarJumlah = akDasarLama + akDasarBaru;
        var akKonversiJumlah = akKonversiLama + akKonversiBaru;

        $(".calculated-cell[data-type='ak_dasar_jumlah']").text(akDasarJumlah.toFixed(2));
        $(".calculated-cell[data-type='ak_konversi_jumlah']").text(akKonversiJumlah.toFixed(2));

        var totalKumulatifLama = akDasarLama + akKonversiLama;
        var totalKumulatifBaru = akDasarBaru + akKonversiBaru;
        var totalKumulatifJumlah = akDasarJumlah + akKonversiJumlah;

        $("#total_lama_kumulatif").text(totalKumulatifLama.toFixed(2));
        $("#total_baru_kumulatif").text(totalKumulatifBaru.toFixed(2));
        $("#total_jumlah_kumulatif").text(totalKumulatifJumlah.toFixed(2));

        $('#performance-table-body tr:not(.total-row)').each(function() {
            const lamaCell = $(this).find('.editable-cell[data-type*="_lama"]:not(.keterangan-cell)');
            const baruCell = $(this).find('.editable-cell[data-type*="_baru"]:not(.keterangan-cell)');
            const jumlahCell = $(this).find('.calculated-cell[data-type*="_jumlah"]');
            
            if (lamaCell.attr('data-type') === 'ak_dasar_lama' || 
                lamaCell.attr('data-type') === 'ak_konversi_lama') {
                return;
            }
            
            if (lamaCell.length && baruCell.length && jumlahCell.length) {
                var lamaText = lamaCell.text().replace(',', '.').replace(/[^\d.-]/g, '');
                var baruText = baruCell.text().replace(',', '.').replace(/[^\d.-]/g, '');
                
                var lamaValue = parseFloat(lamaText) || 0;
                var baruValue = parseFloat(baruText) || 0;
                var jumlah = lamaValue + baruValue;
                
                jumlahCell.text(jumlah.toFixed(2));
            }
        });

        calculateKelebihanAngkaKredit(totalKumulatifJumlah);
    }

    function calculateKelebihanAngkaKredit(totalJumlahKumulatif) {
        var totalJumlah = totalJumlahKumulatif || parseFloat($("#total_jumlah_kumulatif").text().replace(',', '.')) || 0;
        
        var akMinimalPangkatText = $(".editable-cell[data-type='ak_minimal_pangkat']").text().replace(',', '.');
        var akMinimalPangkat = parseFloat(akMinimalPangkatText) || 0;
        
        var akMinimalJenjangText = $(".editable-cell[data-type='ak_minimal_jenjang']").text().replace(',', '.');
        var akMinimalJenjang = parseFloat(akMinimalJenjangText) || 0;
        
        var kelebihanPangkat = totalJumlah - akMinimalPangkat;
        var kelebihanJenjang = totalJumlah - akMinimalJenjang;
        
        $(".calculated-cell[data-type='kelebihan_pangkat']").text(kelebihanPangkat.toFixed(3));
        $(".calculated-cell[data-type='kelebihan_jenjang']").text(kelebihanJenjang.toFixed(3));
        
        updateStrikethroughText(kelebihanPangkat, kelebihanJenjang);
        
        console.log("Perhitungan Kelebihan Angka Kredit:");
        console.log("Total Jumlah:", totalJumlah);
        console.log("AK Minimal Pangkat:", akMinimalPangkat);
        console.log("AK Minimal Jenjang:", akMinimalJenjang);
        console.log("Kelebihan Pangkat:", kelebihanPangkat);
        console.log("Kelebihan Jenjang:", kelebihanJenjang);
    }

    function updateStrikethroughText(kelebihanPangkat, kelebihanJenjang) {
    var pangkatCell = $("#keterangan-pangkat");
        if (pangkatCell.length > 0) {
            var newTextPangkat = "";
            
            if (kelebihanPangkat < 0) {
                newTextPangkat = '<span style="text-decoration: line-through;">Kelebihan</span>/ kekurangan <sup>**)</sup> Angka Kredit yang harus dicapai untuk kenaikan pangkat';
            } else {
                newTextPangkat = 'Kelebihan/ <span style="text-decoration: line-through;">kekurangan</span> <sup>**)</sup> Angka Kredit yang harus dicapai untuk kenaikan pangkat';
            }
            
            pangkatCell.html(newTextPangkat);
        }
        
        var jenjangCell = $("#keterangan-jenjang");
        if (jenjangCell.length > 0) {
            var newTextJenjang = "";
            
            if (kelebihanJenjang < 0) {
                newTextJenjang = '<span style="text-decoration: line-through;">Kelebihan</span>/kekurangan <sup>**)</sup> Angka Kredit yang harus dicapai untuk peningkatan jenjang';
            } else {
                newTextJenjang = 'Kelebihan/<span style="text-decoration: line-through;">kekurangan</span> <sup>**)</sup> Angka Kredit yang harus dicapai untuk peningkatan jenjang';
            }
            
            jenjangCell.html(newTextJenjang);
        }
        
        console.log("Strikethrough Update:");
        console.log("Kelebihan Pangkat:", kelebihanPangkat, (kelebihanPangkat < 0 ? "-> Strike 'kelebihan'" : "-> Strike 'kekurangan'"));
        console.log("Kelebihan Jenjang:", kelebihanJenjang, (kelebihanJenjang < 0 ? "-> Strike 'kelebihan'" : "-> Strike 'kekurangan'"));
    } 

    $(document).on('blur', '.editable-cell[data-type="ak_minimal_pangkat"], .editable-cell[data-type="ak_minimal_jenjang"]', function() {
        setTimeout(function() {
            calculateFormat3Totals();
        }, 100);
    });

    // ===== IMPROVED MESSAGE FUNCTIONS =====
    function showErrorMessage(message) {
        const existingAlert = document.getElementById('dynamic-error-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-error';
        alertDiv.id = 'dynamic-error-alert';
        alertDiv.innerHTML = `
            <span class="alert-close" onclick="hideAlert('dynamic-error-alert')">&times;</span>
            ${message}
        `;
        
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.insertAdjacentElement('afterend', alertDiv);
        } else {
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }
        
        setTimeout(function() {
            hideAlert(alertDiv);
        }, 5000);
    }

    function showSuccessMessage(message) {
        const existingAlert = document.getElementById('dynamic-success-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success';
        alertDiv.id = 'dynamic-success-alert';
        alertDiv.innerHTML = `
            <span class="alert-close" onclick="hideAlert('dynamic-success-alert')">&times;</span>
            ${message}
        `;
        
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.insertAdjacentElement('afterend', alertDiv);
        } else {
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }
        
        setTimeout(function() {
            hideAlert(alertDiv);
        }, 5000);
    }

    // ===== FORM VALIDATION AND SUBMISSION - FIXED =====
    function checkPeriodeDuplikasi(bulanAwal, bulanAkhir, tahun, form) {
        $.ajax({
            url: 'check_periode_duplikasi.php',
            type: 'POST',
            data: {
                bulan_awal: bulanAwal,
                bulan_akhir: bulanAkhir,
                tahun: tahun
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    showErrorMessage('Data untuk periode ' + bulanAwal + ' - ' + bulanAkhir + ' tahun ' + tahun + ' sudah ada! Silakan pilih periode yang berbeda.');
                } else {
                    form.submit();
                }
            },
            error: function() {
                console.log('Error checking periode duplikasi, proceeding with server validation');
                form.submit();
            }
        });
    }

    // ===== DOCUMENT READY FUNCTIONS =====
    document.addEventListener("DOMContentLoaded", function() {
        // ===== AUTO-HIDE ALERTS - FIXED =====
        const alerts = document.querySelectorAll('.alert, [style*="background: #f8d7da"], [style*="background: #d4edda"]');
        
        alerts.forEach(function(alert, index) {
            if (!alert.id) {
                alert.id = 'auto-alert-' + index;
            }
            
            if (!alert.querySelector('.alert-close')) {
                const closeBtn = document.createElement('span');
                closeBtn.className = 'alert-close';
                closeBtn.innerHTML = '&times;';
                closeBtn.onclick = function() {
                    hideAlert(alert);
                };
                
                alert.style.position = 'relative';
                alert.appendChild(closeBtn);
            }
            
            setTimeout(function() {
                hideAlert(alert);
            }, 5000);
        });

        // ===== FORM SUBMISSION HANDLER - FIXED =====
        const form = document.querySelector('.konversi-form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const bulanAwal = document.getElementById('bulan_awal').value;
                const bulanAkhir = document.getElementById('bulan_akhir').value;
                const tahun = document.getElementById('tahun').value;
                
                if (bulanAwal && bulanAkhir && tahun) {
                    e.preventDefault();
                    checkPeriodeDuplikasi(bulanAwal, bulanAkhir, tahun, form);
                }
            });
        }

        // ===== LOAD KETERANGAN DATA =====
        loadKeteranganData();
        
        // ===== TRIGGER INITIAL CALCULATION =====
        const koefisienInputDom = document.getElementById("koefisien");
        const persentaseInputDom = document.getElementById("persentase");
        
        if (koefisienInputDom.value && persentaseInputDom.value) {
            hitungAngkaKredit();
        }
        
        // ===== AUTOMATIC PERCENTAGE CALCULATION BASED ON MONTHS =====
        const bulanMap = {
            "januari": 1, "februari": 2, "maret": 3, "april": 4,
            "mei": 5, "juni": 6, "juli": 7, "agustus": 8,
            "september": 9, "oktober": 10, "november": 11, "desember": 12
        };

        const bulanAwalSelect = document.getElementById("bulan_awal");
        const bulanAkhirSelect = document.getElementById("bulan_akhir");

        function hitungPersentase() {
            let awal = bulanMap[bulanAwalSelect.value.toLowerCase()] || 0;
            let akhir = bulanMap[bulanAkhirSelect.value.toLowerCase()] || 0;

            if (awal > 0 && akhir > 0) {
                let selisih = akhir - awal + 1;
                if (selisih <= 0) selisih += 12; 
                persentaseInputDom.value = selisih;
                
                // Trigger angka kredit calculation
                const nilaiPersentase = parseFloat(persentaseInputDom.value);
                const nilaiKoefisien = parseFloat(koefisienInputDom.value);
                if (!isNaN(nilaiPersentase) && !isNaN(nilaiKoefisien)) {
                    const persen = (nilaiPersentase / 12) * 100;
                    let angkaKredit = persen * nilaiKoefisien / 100;
                    document.getElementById("angka_kredit").value = angkaKredit.toFixed(3);
                }
            } else {
                persentaseInputDom.value = "";
            }
        }

        bulanAwalSelect.addEventListener("change", hitungPersentase);
        bulanAkhirSelect.addEventListener("change", hitungPersentase);
    });
    </script>

</body>
</html>
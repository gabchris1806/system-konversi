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
        /* ===== FONT FAMILY GLOBAL ===== */
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        /* ===== FIX NAVBAR Z-INDEX ===== */
        .navbar {
            position: relative;
            z-index: 1000;
        }
        
        .dropdown {
            z-index: 1001;
        }
        
        /* ===== TAB BAR STYLING ===== */
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
            font-family: 'Poppins', sans-serif; /* Explicitly set font family */
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
        
        /* ===== TAB CONTENT STYLING ===== */
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
        
        /* Update dashboard content styling */
        .dashboard-content {
            max-width: none;
            margin: 0;
            background: transparent;
            padding: 0;
            border-radius: 0;
            box-shadow: none;
        }
        
        /* Tab content headers */
        .form-content h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }

        /* ===== DROPDOWN BULAN STYLING ===== */
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

        /* ===== FORMAT 3 TABLE STYLING ===== */
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

        /* Pastikan tfoot juga terlihat jika masih digunakan */
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

        /* Pastikan rowspan di-update dengan benar */
        .row-number {
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
        }

        /* Editable cells styling */
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

        /* Input styling when editing */
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

        /* Total row styling */
        .format3-table tfoot tr {
            background-color: #28a745 !important;
            color: white;
            font-weight: bold;
        }

        .format3-table tfoot td {
            border-color: #28a745;
        }

        /* ===== ADD/REMOVE ROWS STYLING ===== */
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

        /* ===== KETERANGAN CELL STYLING ===== */
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
                <h2>Format 2 - Data Tahunan</h2>
                
                <div class="year-selector-container">
                    <label for="tahun_pilih_f2">Tahun:</label>
                    <select id="tahun_pilih_f2" required>
                        <option value="">Pilih Tahun</option>
                        <?php
                        // Generate tahun dari tahun sekarang - 5 sampai tahun sekarang + 5
                        $current_year = date('Y');
                        for ($i = 1990; $i <= $current_year + 20; $i++) {
                            echo "<option value='".$i."'>".$i."</option>";
                        }
                        ?>
                    </select>
                    <button type="button" id="btn-lihat-f2" class="btn-lihat">Lihat</button>
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
                            <th>Tahun</th>
                            <th>Periode (Bulan)</th>
                            <th>Predikat</th>
                            <th>Persentase</th>
                            <th>Koefisien</th>
                            <th>Angka Kredit</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-format2">
                        <tr>
                            <td colspan="6" class="no-data-message">Silakan pilih tahun terlebih dahulu</td>
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
                                <td id="keterangan-pangkat" style="text-align: left; padding: 12px;">Kelebihan/ kekurangan <sup>**)</sup> Angka Kredit yang harus dicapai untuk kenaikan pangkat</td>
                                <td class="calculated-cell" data-type="kelebihan_pangkat">25.000</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td id="keterangan-jenjang" style="text-align: left; padding: 12px;">Kelebihan/kekurangan <sup>**)</sup> Angka Kredit yang harus dicapai untuk peningkatan jenjang</td>
                                <td></td>
                                <td class="calculated-cell" data-type="kelebihan_jenjang">25.000</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- UPDATED BOTTOM SECTION: KETERANGAN WITH INLINE BUTTONS & WARNING -->
                    <div class="format3-bottom-section">
                        <div class="format3-notes-section">
                            <h4>Keterangan:</h4>
                            <p>**) Diisi dengan angka kredit yang diperoleh dari hasil konversi</p>
                            <p>***) Diisi dengan jenis kegiatan lainnya yang dapat dinilai angka kreditnya</p>
                            
                            <div class="note-footer">
                                Laporan ini dibuat berdasarkan data konversi yang telah diinput untuk tahun <span id="current-year-display">2025</span>
                            </div>
                            
                            <!-- BUTTONS SECTIONS - Properly aligned and styled -->
                            <div class="format3-actions">
                                <button type="button" class="report-btn btn-print" onclick="cetakLaporan()">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 8h-1V3H6v5H5c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h2v3h10v-3h2c1.1 0 2-.9 2-2v-6c0-1.1-.9-2-2-2zM8 5h8v3H8V5zm8 12v2H8v-2h8zm2-2v-2H6v2H6v-2H4v-2h16v2h-2v2z"/>
                                        <rect x="6" y="11" width="12" height="2"/>
                                    </svg>
                                    Cetak Laporan
                                </button>
                                
                                <button type="button" class="report-btn btn-download" onclick="downloadPDF()">
                                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                    </svg>
                                    Download PDF
                                </button>
                            </div>
                        </div>

                        <!-- WARNING MESSAGE -->
                        <div class="format3-warning" style="display: none;">
                            <span style="color: #856404; font-weight: bold;">Belum ada data konversi untuk tahun ini. Silakan tambahkan data di Format 1.</span>
                        </div>
                    </div>
                </div>    
            </div>
        </div>
    </div>

    <script>
    // Global variable untuk tracking row counter
    let performanceRowCounter = 6;

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

    // ===== CETAK LAPORAN & DOWNLOAD PDF FUNCTIONS =====
    function cetakLaporan() {
        const tahun = document.getElementById("tahun_pilih_f3").value;
        
        if (!tahun) {
            alert("Silakan pilih tahun terlebih dahulu!");
            return;
        }

        // Check if there's data to print
        if (document.getElementById("format3-container").style.display === "none") {
            alert("Tidak ada data untuk dicetak. Silakan input data terlebih dahulu.");
            return;
        }

        // Hide action buttons and warning during print
        const actionSection = document.querySelector('.format3-actions');
        const warningSection = document.querySelector('.format3-warning');
        
        if (actionSection) actionSection.style.display = 'none';
        if (warningSection) warningSection.style.display = 'none';

        // Trigger print
        window.print();

        // Show buttons back after print dialog closes
        setTimeout(() => {
            if (actionSection) actionSection.style.display = 'flex';
            if (warningSection) warningSection.style.display = 'flex';
        }, 1000);
    }

    function downloadPDF() {
        const tahun = document.getElementById("tahun_pilih_f3").value;
        
        if (!tahun) {
            alert("Silakan pilih tahun terlebih dahulu!");
            return;
        }

        // Check if there's data to download
        if (document.getElementById("format3-container").style.display === "none") {
            alert("Tidak ada data untuk didownload. Silakan input data terlebih dahulu.");
            return;
        }

        // You can implement PDF generation here
        // For now, show a message
        alert("Fitur download PDF akan segera tersedia. Untuk sementara, gunakan 'Cetak Laporan' dan simpan sebagai PDF melalui browser.");
        
        // Alternative: redirect to a PHP file that generates PDF
        // window.location.href = "generate_pdf.php?tahun=" + tahun;
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
    // Function to save keterangan data (optional - for persistence)
    function saveKeteranganData() {
        const keteranganData = {};
        
        $('.keterangan-cell').each(function() {
            const dataType = $(this).attr('data-type');
            const content = $(this).text();
            keteranganData[dataType] = content;
        });
        
        // Save to localStorage for persistence
        localStorage.setItem('keterangan_data', JSON.stringify(keteranganData));
        
        console.log('Keterangan data saved:', keteranganData);
    }

    // Function to load keterangan data (optional - for persistence)
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
        
        if(tahun === ""){
            alert("Pilih tahun terlebih dahulu!");
            return;
        }

        // Show loading
        $("#tabel-format2").html('<tr><td colspan="6" class="loading">Memuat data...</td></tr>');
        $("#summary-container-f2").hide();
        
        $.ajax({
            url: "load_form2.php",
            type: "POST",
            data: {tahun_pilih: tahun},
            dataType: 'json',
            success: function(response) {
                console.log("Response dari server:", response);

                if(response.status === 'success') {
                    $("#tabel-format2").html(response.table_data);

                    if(response.summary_data) {
                        $("#koefisien-per-tahun-f2").text(response.summary_data.koefisien_per_tahun);
                        $("#angka-kredit-didapat-f2").text(response.summary_data.angka_kredit_yang_didapat);
                        // PERUBAHAN: Set angka dasar menjadi 50,0 bukan dari response
                        $("#angka-dasar-f2").text("50,0");
                        $("#summary-container-f2").show();
                    }
                } else {
                    $("#tabel-format2").html(response.table_data);
                    $("#summary-container-f2").hide();
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error:", status, error);
                console.log("Response Text:", xhr.responseText);
                $("#tabel-format2").html('<tr><td colspan="6" class="no-data-message" style="color: red;">Terjadi kesalahan saat memuat data</td></tr>');
                $("#summary-container-f2").hide();
            }
        });
    });

    // Auto load saat tahun berubah - Format 2
    $("#tahun_pilih_f2").change(function() {
        if($(this).val() !== "") {
            $("#btn-lihat-f2").click();
        }
    });

    // Auto load saat tahun berubah - Format 3
    $("#tahun_pilih_f3").change(function() {
        if($(this).val() !== "") {
            $("#btn-lihat-f3").click();
        }
        
        // Update year display in notes
        $("#current-year-display").text($(this).val());
    });

    // ===== FORMAT 3: AJAX Load Data =====
    $("#btn-lihat-f3").click(function(e){
        e.preventDefault();
        let tahun = $("#tahun_pilih_f3").val();
        
        if(tahun === ""){
            alert("Pilih tahun terlebih dahulu!");
            return;
        }
        
        console.log("Memuat data Format 3 untuk tahun:", tahun);
        
        $.ajax({
            url: "load_form3.php",
            type: "POST",
            data: {tahun_pilih: tahun},
            dataType: 'json',
            success: function(response) {
                console.log("Response Format 3:", response);
                
                if(response.status === 'success') {
                    // Ada data, tampilkan tabel
                    $("#format3-container").show();
                    
                    // Load data AK Konversi dari response
                    if(response.total_angka_kredit > 0) {
                        $(".editable-cell[data-type='ak_konversi_baru']").text(parseFloat(response.total_angka_kredit).toFixed(2));
                    } else {
                        // Fallback ke Format 1 jika tidak ada data dari DB
                        loadAKKonversiFromFormat1();
                    }
                    
                    // Calculate totals
                    calculateFormat3Totals();
                    
                    // Hide warning when data is available
                    $(".format3-warning").hide();
                    
                } else if(response.status === 'no_data') {
                    // Tidak ada data
                    $("#format3-container").show(); // Still show container
                    
                    // Show warning
                    $(".format3-warning").show();
                    
                } else {
                    // Error
                    $("#format3-container").hide();
                    alert("Terjadi kesalahan: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error Format 3:", status, error);
                console.log("Response Text:", xhr.responseText);
                $("#format3-container").hide();
                alert("Terjadi kesalahan saat memuat data Format 3");
            }
        });
    });

    // ===== FORMAT 3: Editable Cells =====
    $(document).on('click', '.editable-cell:not(.keterangan-cell)', function() {
        if ($(this).find('input').length > 0) return; // Already editing
        
        var currentValue = $(this).text();
        var input = $('<input type="text" class="cell-input" value="' + currentValue + '">');
        
        $(this).html(input);
        input.focus().select();
        
        // Save on Enter or blur
        input.on('blur keypress', function(e) {
            if (e.type === 'blur' || e.which === 13) {
                var newValue = $(this).val();
                $(this).parent().text(newValue);
                calculateFormat3Totals();
            }
        });
    });

    // Function to load AK Konversi from Format 1
    function loadAKKonversiFromFormat1() {
        // Get angka kredit from Format 1 if available
        var angkaKredit = $("#angka_kredit").val();
        if (angkaKredit && angkaKredit !== '') {
            $(".editable-cell[data-type='ak_konversi_baru']").text(parseFloat(angkaKredit).toFixed(2));
        }
    }

    // Function to calculate Format 3 totals AND automatic calculations
    function calculateFormat3Totals() {
        // Khusus untuk perhitungan JUMLAH ANGKA KREDIT KUMULATIF
        // Hanya menghitung AK Dasar + AK Konversi
        var akDasarLama = parseFloat($(".editable-cell[data-type='ak_dasar_lama']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;
        var akDasarBaru = parseFloat($(".editable-cell[data-type='ak_dasar_baru']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;
        var akKonversiLama = parseFloat($(".editable-cell[data-type='ak_konversi_lama']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;
        var akKonversiBaru = parseFloat($(".editable-cell[data-type='ak_konversi_baru']").text().replace(',', '.').replace(/[^\d.-]/g, '')) || 0;

        // Hitung jumlah masing-masing kolom untuk AK Dasar dan AK Konversi
        var akDasarJumlah = akDasarLama + akDasarBaru;
        var akKonversiJumlah = akKonversiLama + akKonversiBaru;

        // Update kolom jumlah untuk baris AK Dasar dan AK Konversi
        $(".calculated-cell[data-type='ak_dasar_jumlah']").text(akDasarJumlah.toFixed(2));
        $(".calculated-cell[data-type='ak_konversi_jumlah']").text(akKonversiJumlah.toFixed(2));

        // Hitung total kumulatif (hanya AK Dasar + AK Konversi)
        var totalKumulatifLama = akDasarLama + akKonversiLama;
        var totalKumulatifBaru = akDasarBaru + akKonversiBaru;
        var totalKumulatifJumlah = akDasarJumlah + akKonversiJumlah;

        // Update JUMLAH ANGKA KREDIT KUMULATIF
        $("#total_lama_kumulatif").text(totalKumulatifLama.toFixed(2));
        $("#total_baru_kumulatif").text(totalKumulatifBaru.toFixed(2));
        $("#total_jumlah_kumulatif").text(totalKumulatifJumlah.toFixed(2));

        // Hitung jumlah untuk baris lainnya (AK JF Lama, AK Penyesuaian, AK Pendidikan, dll)
        $('#performance-table-body tr:not(.total-row)').each(function() {
            const lamaCell = $(this).find('.editable-cell[data-type*="_lama"]:not(.keterangan-cell)');
            const baruCell = $(this).find('.editable-cell[data-type*="_baru"]:not(.keterangan-cell)');
            const jumlahCell = $(this).find('.calculated-cell[data-type*="_jumlah"]');
            
            // Skip yang sudah dihitung di atas (ak_dasar dan ak_konversi)
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

        // ===== PERHITUNGAN OTOMATIS TABEL KEDUA =====
        // Gunakan total kumulatif untuk perhitungan kelebihan/kekurangan
        calculateKelebihanAngkaKredit(totalKumulatifJumlah);
    }

    // Function untuk menghitung kelebihan angka kredit secara otomatis
    function calculateKelebihanAngkaKredit(totalJumlahKumulatif) {
        // Gunakan parameter yang diberikan, atau ambil dari elemen jika tidak ada
        var totalJumlah = totalJumlahKumulatif || parseFloat($("#total_jumlah_kumulatif").text().replace(',', '.')) || 0;
        
        // Ambil nilai angka kredit minimal untuk pangkat
        var akMinimalPangkatText = $(".editable-cell[data-type='ak_minimal_pangkat']").text().replace(',', '.');
        var akMinimalPangkat = parseFloat(akMinimalPangkatText) || 0;
        
        // Ambil nilai angka kredit minimal untuk jenjang jabatan
        var akMinimalJenjangText = $(".editable-cell[data-type='ak_minimal_jenjang']").text().replace(',', '.');
        var akMinimalJenjang = parseFloat(akMinimalJenjangText) || 0;
        
        // Hitung kelebihan untuk pangkat
        var kelebihanPangkat = totalJumlah - akMinimalPangkat;
        
        // Hitung kelebihan untuk jenjang jabatan
        var kelebihanJenjang = totalJumlah - akMinimalJenjang;
        
        // Update nilai di tabel kedua
        $(".calculated-cell[data-type='kelebihan_pangkat']").text(kelebihanPangkat.toFixed(3));
        $(".calculated-cell[data-type='kelebihan_jenjang']").text(kelebihanJenjang.toFixed(3));
        
        // ===== IMPLEMENTASI STRIKETHROUGH OTOMATIS =====
        updateStrikethroughText(kelebihanPangkat, kelebihanJenjang);
        
        // Debug log
        console.log("Perhitungan Kelebihan Angka Kredit:");
        console.log("Total Jumlah:", totalJumlah);
        console.log("AK Minimal Pangkat:", akMinimalPangkat);
        console.log("AK Minimal Jenjang:", akMinimalJenjang);
        console.log("Kelebihan Pangkat:", kelebihanPangkat);
        console.log("Kelebihan Jenjang:", kelebihanJenjang);
    }

    // Function untuk update strikethrough text berdasarkan kondisi
function updateStrikethroughText(kelebihanPangkat, kelebihanJenjang) {
    var pangkatCell = $("#keterangan-pangkat");
    if (pangkatCell.length > 0) {
        var newTextPangkat = "";
        
        if (kelebihanPangkat < 0) {
            // Jika kurang dari 0, strikethrough "kelebihan"
            newTextPangkat = '<span style="text-decoration: line-through;">Kelebihan</span>/ kekurangan <sup>**)</sup> Angka Kredit yang harus dicapai untuk kenaikan pangkat';
        } else {
            // Jika >= 0, strikethrough "kekurangan"
            newTextPangkat = 'Kelebihan/ <span style="text-decoration: line-through;">kekurangan</span> <sup>**)</sup> Angka Kredit yang harus dicapai untuk kenaikan pangkat';
        }
        
        pangkatCell.html(newTextPangkat);
    }
    
    // Update untuk baris jenjang jabatan (baris ke-3)
    var jenjangCell = $("#keterangan-jenjang");
    if (jenjangCell.length > 0) {
        var newTextJenjang = "";
        
        if (kelebihanJenjang < 0) {
            // Jika kurang dari 0, strikethrough "kelebihan"
            newTextJenjang = '<span style="text-decoration: line-through;">Kelebihan</span>/kekurangan <sup>**)</sup> Angka Kredit yang harus dicapai untuk peningkatan jenjang';
        } else {
            // Jika >= 0, strikethrough "kekurangan"
            newTextJenjang = 'Kelebihan/<span style="text-decoration: line-through;">kekurangan</span> <sup>**)</sup> Angka Kredit yang harus dicapai untuk peningkatan jenjang';
        }
        
        jenjangCell.html(newTextJenjang);
    }
    
    // Log untuk debugging
    console.log("Strikethrough Update:");
    console.log("Kelebihan Pangkat:", kelebihanPangkat, (kelebihanPangkat < 0 ? "-> Strike 'kelebihan'" : "-> Strike 'kekurangan'"));
    console.log("Kelebihan Jenjang:", kelebihanJenjang, (kelebihanJenjang < 0 ? "-> Strike 'kelebihan'" : "-> Strike 'kekurangan'"));
}

    // Tambahkan juga event listener untuk perubahan pada angka kredit minimal
    $(document).on('blur', '.editable-cell[data-type="ak_minimal_pangkat"], .editable-cell[data-type="ak_minimal_jenjang"]', function() {
        // Trigger kalkulasi ulang ketika angka minimal berubah
        setTimeout(function() {
            calculateFormat3Totals();
        }, 100);
    });

    // ===== PERHITUNGAN OTOMATIS PERSENTASE BERDASARKAN BULAN =====
    document.addEventListener("DOMContentLoaded", function() {
        
        const persentaseInputDom = document.getElementById("persentase");
        const koefisienInputDom = document.getElementById("koefisien");
        const angkaKreditInputDom = document.getElementById("angka_kredit");
        
        // Load keterangan data when page loads
        loadKeteranganData();
        
        // Trigger perhitungan awal jika koefisien sudah ada nilai
        if (koefisienInputDom.value && persentaseInputDom.value) {
            hitungAngkaKredit();
        }
        
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
                
                // Trigger perhitungan angka kredit
                const nilaiPersentase = parseFloat(persentaseInputDom.value);
                const nilaiKoefisien = parseFloat(koefisienInputDom.value);
                if (!isNaN(nilaiPersentase) && !isNaN(nilaiKoefisien)) {
                    const persen = (nilaiPersentase / 12) * 100;
                    let angkaKredit = persen * nilaiKoefisien / 100;
                    angkaKreditInputDom.value = angkaKredit.toFixed(3);
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
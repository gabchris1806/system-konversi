<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug: Script started<br>";

session_start();
echo "Debug: Session started<br>";

// Check if db.php exists
if (!file_exists("db.php")) {
    die("Error: db.php file not found!");
}

include "db.php";
echo "Debug: Database included<br>";

// Cek login
if (!isset($_SESSION['nip'])) {
    echo "Debug: No NIP in session<br>";
    echo "Session contents: ";
    print_r($_SESSION);
    header("Location: login.php");
    exit();
}

echo "Debug: NIP found: " . $_SESSION['nip'] . "<br>";

$nip = $_SESSION['nip'];
$tahun = $_GET['tahun'] ?? '';

echo "Debug: Tahun parameter: " . $tahun . "<br>";

if (empty($tahun)) {
    die("Error: Tahun harus diisi");
}

// Test database connection
if (!isset($conn)) {
    die("Error: Database connection not established");
}

echo "Debug: Database connection OK<br>";

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM pegawai WHERE nip = ?");
if (!$stmt) {
    die("Error preparing user query: " . $conn->error);
}

$stmt->bind_param("s", $nip);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Error: User not found for NIP: " . $nip);
}

echo "Debug: User found: " . $user['nama'] . "<br>";

// Ambil data konversi
$stmt_konversi = $conn->prepare("SELECT * FROM nilai WHERE nip = ? AND tahun = ?");
if (!$stmt_konversi) {
    die("Error preparing konversi query: " . $conn->error);
}

$stmt_konversi->bind_param("ss", $nip, $tahun);
$stmt_konversi->execute();
$result_konversi = $stmt_konversi->get_result();

echo "Debug: Konversi query executed<br>";

$total_angka_kredit = 0;
$data_count = 0;
while ($row = $result_konversi->fetch_assoc()) {
    $total_angka_kredit += $row['angka_kredit'];
    $data_count++;
    echo "Debug: Found data - AK: " . $row['angka_kredit'] . "<br>";
}

echo "Debug: Total data found: " . $data_count . "<br>";
echo "Debug: Total angka kredit: " . $total_angka_kredit . "<br>";

// Hitung data untuk tabel
$ak_dasar_lama = 0;
$ak_dasar_baru = 50.00;
$ak_dasar_jumlah = $ak_dasar_lama + $ak_dasar_baru;

$ak_konversi_lama = 0;
$ak_konversi_baru = $total_angka_kredit;
$ak_konversi_jumlah = $ak_konversi_lama + $ak_konversi_baru;

$total_kumulatif_lama = $ak_dasar_lama + $ak_konversi_lama;
$total_kumulatif_baru = $ak_dasar_baru + $ak_konversi_baru;
$total_kumulatif_jumlah = $ak_dasar_jumlah + $ak_konversi_jumlah;

$ak_minimal_pangkat = 50;
$ak_minimal_jenjang = 50;
$kelebihan_pangkat = $total_kumulatif_jumlah - $ak_minimal_pangkat;
$kelebihan_jenjang = $total_kumulatif_jumlah - $ak_minimal_jenjang;

echo "Debug: All calculations completed successfully<br>";
echo "Debug: Ready to show PDF content<br><hr>";

// Set headers untuk download PDF
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Angka Kredit - <?php echo $tahun; ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* PDF/Print specific overrides */
        body {
            font-size: 12px;
            margin: 20px;
            color: #000 !important;
            background: white !important;
        }
        
        /* Hide navbar and other non-essential elements */
        .navbar, .tab-container, .tab-bar, .format3-actions, .no-print {
            display: none !important;
        }
        
        /* Debug info styling */
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            font-family: monospace;
            font-size: 10px;
        }
        
        /* PDF specific styling */
        .pdf-container {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
        
        .pdf-title {
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
            font-size: 16px;
        }
        
        .pdf-user-info {
            margin-bottom: 20px;
            font-size: 11px;
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
        }
        
        /* Override existing table styles for PDF */
        .pdf-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 20px 0 !important;
            font-size: 11px !important;
        }
        
        .pdf-table th, .pdf-table td {
            border: 1px solid #000 !important;
            padding: 8px 6px !important;
            text-align: center !important;
            vertical-align: middle !important;
            font-size: 11px !important;
            background: white !important;
        }
        
        .pdf-table th {
            background-color: #f0f0f0 !important;
            font-weight: bold !important;
        }
        
        .pdf-table .total-row td {
            background-color: #28a745 !important;
            color: white !important;
            font-weight: bold !important;
        }
        
        .pdf-text-left {
            text-align: left !important;
            padding-left: 10px !important;
        }
        
        .pdf-notes {
            margin-top: 30px;
            font-size: 10px;
            line-height: 1.4;
            background: white;
        }
        
        .strikethrough {
            text-decoration: line-through;
        }
        
        @media print {
            .debug-info { display: none !important; }
            body { 
                margin: 10px !important; 
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            .no-print, .navbar, .tab-container { 
                display: none !important; 
            }
            .pdf-table .total-row td {
                background-color: #28a745 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
            }
        }
        
        @page {
            margin: 1cm;
            size: A4;
        }
    </style>
    <script>
        // Auto print when page loads (disabled for debugging)
        // window.onload = function() {
        //     window.print();
        // }
        
        function enablePrint() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="debug-info">
        <strong>Debug Information:</strong><br>
        User: <?php echo htmlspecialchars($user['nama']); ?> (NIP: <?php echo htmlspecialchars($user['nip']); ?>)<br>
        Tahun: <?php echo $tahun; ?><br>
        Data konversi ditemukan: <?php echo $data_count; ?> record(s)<br>
        Total Angka Kredit: <?php echo $total_angka_kredit; ?><br>
        <button onclick="enablePrint()">Print PDF</button>
        <button onclick="document.querySelector('.debug-info').style.display='none';">Hide Debug</button>
    </div>

    <div class="pdf-container">
        <div class="pdf-user-info">
            <strong>Nama:</strong> <?php echo htmlspecialchars($user['nama']); ?><br>
            <strong>NIP:</strong> <?php echo htmlspecialchars($user['nip']); ?><br>
            <strong>Tahun:</strong> <?php echo $tahun; ?>
        </div>

        <h3 class="pdf-title">HASIL PENILAIAN ANGKA KREDIT</h3>
        
        <!-- Tabel Pertama -->
        <table class="pdf-table">
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
            <tbody>
                <tr>
                    <td rowspan="7">1</td>
                    <td class="pdf-text-left">1. AK Dasar yang diberikan</td>
                    <td><?php echo number_format($ak_dasar_lama, 2); ?></td>
                    <td><?php echo number_format($ak_dasar_baru, 2); ?></td>
                    <td><?php echo number_format($ak_dasar_jumlah, 2); ?></td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="pdf-text-left">2. AK JF Lama</td>
                    <td>-</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="pdf-text-left">3. AK Penyesuaian/ Penyetaraan</td>
                    <td>-</td>
                    <td>0.00</td>
                    <td>0.00</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="pdf-text-left">4. AK Konversi</td>
                    <td><?php echo number_format($ak_konversi_lama, 2); ?></td>
                    <td><?php echo number_format($ak_konversi_baru, 2); ?></td>
                    <td><?php echo number_format($ak_konversi_jumlah, 2); ?></td>
                    <td>Dari konversi</td>
                </tr>
                <tr>
                    <td class="pdf-text-left">5. AK yang diperoleh dari peningkatan pendidikan</td>
                    <td>-</td>
                    <td>-</td>
                    <td>0.00</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="pdf-text-left">6. ................ **)</td>
                    <td>-</td>
                    <td>-</td>
                    <td>0.00</td>
                    <td>-</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" class="pdf-text-left">JUMLAH ANGKA KREDIT KUMULATIF</td>
                    <td><?php echo number_format($total_kumulatif_lama, 2); ?></td>
                    <td><?php echo number_format($total_kumulatif_baru, 2); ?></td>
                    <td><?php echo number_format($total_kumulatif_jumlah, 2); ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Tabel Kedua -->
        <table class="pdf-table" style="margin-top: 30px;">
            <thead>
                <tr>
                    <th style="width: 50%;">Keterangan</th>
                    <th style="width: 25%;">Pangkat</th>
                    <th style="width: 25%;">Jenjang Jabatan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="pdf-text-left">Angka Kredit minimal yang harus dipenuhi untuk kenaikan pangkat/ jenjang</td>
                    <td><?php echo $ak_minimal_pangkat; ?></td>
                    <td><?php echo $ak_minimal_jenjang; ?></td>
                </tr>
                <tr>
                    <td class="pdf-text-left">
                        <?php if ($kelebihan_pangkat < 0): ?>
                            <span class="strikethrough">Kelebihan</span>/ kekurangan **) Angka Kredit yang harus dicapai untuk kenaikan pangkat
                        <?php else: ?>
                            Kelebihan/ <span class="strikethrough">kekurangan</span> **) Angka Kredit yang harus dicapai untuk kenaikan pangkat
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($kelebihan_pangkat, 3); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="pdf-text-left">
                        <?php if ($kelebihan_jenjang < 0): ?>
                            <span class="strikethrough">Kelebihan</span>/kekurangan **) Angka Kredit yang harus dicapai untuk peningkatan jenjang
                        <?php else: ?>
                            Kelebihan/<span class="strikethrough">kekurangan</span> **) Angka Kredit yang harus dicapai untuk peningkatan jenjang
                        <?php endif; ?>
                    </td>
                    <td></td>
                    <td><?php echo number_format($kelebihan_jenjang, 3); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="pdf-notes">
            <h4>Keterangan:</h4>
            <p>**) Diisi dengan angka kredit yang diperoleh dari hasil konversi</p>
            <p>***) Diisi dengan jenis kegiatan lainnya yang dapat dinilai angka kreditnya</p>
            
            <div style="margin-top: 20px;">
                Laporan ini dibuat berdasarkan data konversi yang telah diinput untuk tahun <?php echo $tahun; ?>
            </div>
            
            <div style="margin-top: 30px; text-align: right;">
                <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
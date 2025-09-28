<?php
session_start();
include __DIR__ . '/db.php';

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
        /* ===== DASHBOARD BODY ===== */
        body.dashboard-page {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f37eb 0%, #03e2ff 100%);
            margin: 0;
            display: block; 
            min-height: 100vh;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            color: #333;
            position: relative;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .navbar-left {
            display: flex;
            align-items: center;
        }

        .logo {
            width: 60px;
            height: 60px;
            margin-right: 15px;
            border-radius: 8px;
        }

        .app-title {
            font-size: 20px;
            font-weight: 700;
            color: #2d3748;
            margin-right: 30px;
        }

        /* Navigation Links */
        .nav-links {
            display: flex;
            gap: 5px;
        }

        .nav-link {
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            transition: all 0.3s ease;
        }

        .nav-link:hover::before {
            left: 0;
        }

        .nav-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .navbar-right {
            display: flex;
            align-items: center;
        }

        .user-info {
            text-align: right;
            margin-right: 15px;
        }

        .user-name {
            display: block;
            font-weight: 600;
            color: #2d3748;
        }

        .user-nip {
            font-size: 12px;
            color: #718096;
        }

        .profile-menu {
            position: relative;
            display: inline-block;
        }

        .profile {
            width: 45px;
            height: 45px;
            cursor: pointer;
            border-radius: 50%;
            padding: 2px;
            border: 2px solid #667eea;
            transition: all 0.3s ease;
        }

        .profile:hover {
            transform: scale(1.1);
            border-color: #764ba2;
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            color: #333;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-radius: 12px;
            overflow: hidden;
            z-index: 1001;
            min-width: 150px;
            margin-top: 8px;
        }

        .dropdown a {
            display: block;
            padding: 12px 16px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .dropdown a:hover {
            background: linear-gradient(135deg, #002fff, #00d9ff);
            color: white;
        }

        .profile-menu:hover .dropdown {
            display: block;
            animation: fadeInDown 0.3s ease;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== MAIN DASHBOARD CONTENT ===== */
        .dashboard-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Welcome Section */
        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .welcome-subtitle {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            font-weight: 300;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            transition: all 0.5s ease;
        }

        .dashboard-card:hover::before {
            left: 0;
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .dashboard-card .card-icon {
            color: #667eea;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .dashboard-card .card-content {
            position: relative;
            z-index: 1;
        }

        .dashboard-card h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .dashboard-card p {
            color: #718096;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 0;
        }

        .dashboard-card .card-arrow {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 1.5rem;
            color: #667eea;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover .card-arrow {
            transform: translateX(5px);
            color: #764ba2;
        }

        /* Stats Section */
        .stats-section {
            margin-top: 60px;
        }

        .stats-section h2 {
            text-align: center;
            font-size: 2rem;
            font-weight: 600;
            color: white;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #718096;
            font-weight: 500;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== ALERT STYLING ===== */
        .alert {
            padding: 15px 20px;
            margin: 20px;
            border-radius: 12px;
            font-weight: 500;
            position: relative;
            animation: slideDown 0.3s ease-out;
            transition: all 0.5s ease-out;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(72, 187, 120, 0.15);
            border: 2px solid rgba(72, 187, 120, 0.3);
            color: #2f855a;
        }

        .alert-error {
            background: rgba(245, 101, 101, 0.15);
            border: 2px solid rgba(245, 101, 101, 0.3);
            color: #c53030;
        }

        .alert-close {
            position: absolute;
            top: 12px;
            right: 15px;
            cursor: pointer;
            font-size: 20px;
            color: inherit;
            opacity: 0.7;
            font-weight: bold;
        }

        .alert-close:hover {
            opacity: 1;
            transform: scale(1.1);
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

        /* ===== RESPONSIVE DESIGN FOR DASHBOARD ===== */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }
            
            .navbar-left {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .nav-links {
                flex-direction: column;
                width: 100%;
                gap: 8px;
            }
            
            .nav-link {
                text-align: center;
                width: 100%;
            }
            
            .dashboard-content {
                padding: 20px 15px;
            }
            
            .welcome-section h1 {
                font-size: 2rem;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .welcome-section h1 {
                font-size: 1.5rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
            }
            
            .dashboard-card {
                padding: 20px;
            }
            
            .navbar {
                padding: 10px;
            }
            
            .app-title {
                font-size: 16px;
                text-align: center;
            }
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-left">
            <img src="Logo_PTKI_Medan.png" class="logo" alt="Logo">
            <span class="app-title">SISTEM KONVERSI ANGKA KREDIT</span>
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

    <!-- ALERT MESSAGES -->
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

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="dashboard-content">
        <div class="welcome-section">
            <h1>Selamat Datang, <?php echo htmlspecialchars($user['nama'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="welcome-subtitle">Sistem Konversi Angka Kredit - Dashboard Utama</p>
        </div>

        <!-- DASHBOARD CARDS -->
        <div class="dashboard-cards">
            <div class="dashboard-card" onclick="location.href='dashboard.php?tab=format1'">
                <div class="card-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline points="10,9 9,9 8,9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3>Input Data</h3>
                    <p>Masukkan data konversi angka kredit baru</p>
                </div>
                <div class="card-arrow">→</div>
            </div>

            <div class="dashboard-card" onclick="location.href='dashboard.php?tab=format2'">
                <div class="card-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="9" y="11" width="6" height="11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="m9 7 3-3 3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="12" y1="4" x2="12" y2="11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3>Daftar Konversi</h3>
                    <p>Lihat dan kelola data konversi yang telah diinput</p>
                </div>
                <div class="card-arrow">→</div>
            </div>

            <div class="dashboard-card" onclick="location.href='dashboard.php?tab=format3'">
                <div class="card-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="card-content">
                    <h3>Rekap Konversi</h3>
                    <p>Lihat rekapitulasi dan cetak laporan</p>
                </div>
                <div class="card-arrow">→</div>
            </div>
        </div>
    </div>

    <script>
        // ===== ALERT FUNCTIONS =====
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

        // ===== MESSAGE FUNCTIONS =====
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

        // ===== LOAD DASHBOARD STATS =====
        function loadDashboardStats() {
            $.ajax({
                url: '../models/get_dashboard_stats.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#total-data').text(response.data.total_data || '0');
                        $('#data-tahun-ini').text(response.data.data_tahun_ini || '0');
                        $('#total-angka-kredit').text(response.data.total_angka_kredit || '0.00');
                    } else {
                        console.error('Failed to load dashboard stats:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading dashboard stats:', error);
                }
            });
        }

        // ===== DOCUMENT READY =====
        document.addEventListener("DOMContentLoaded", function() {
            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert, index) {
                if (!alert.id) {
                    alert.id = 'auto-alert-' + index;
                }
                
                setTimeout(function() {
                    hideAlert(alert);
                }, 5000);
            });

            // Load dashboard statistics
            loadDashboardStats();

            // Add hover effects to dashboard cards
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
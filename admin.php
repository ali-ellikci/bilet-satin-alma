<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die("<h1>Erişim Yetkiniz Yok!</h1><p>Bu alana yalnızca yöneticiler erişebilir.</p>");
}

$db = new PDO('sqlite:database.db'); 
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$total_users = $db->query("SELECT COUNT(*) FROM User WHERE role = 'user'")->fetchColumn();
$total_companies = $db->query("SELECT COUNT(*) FROM Bus_Company")->fetchColumn();
$total_trips = $db->query("SELECT COUNT(*) FROM Trips")->fetchColumn();
$total_admins = $db->query("SELECT COUNT(*) FROM User WHERE role = 'admin'")->fetchColumn();
$total_company_admins = $db->query("SELECT COUNT(*) FROM User WHERE role = 'company_admin'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
            background-color: #2c3e50;
        }

        .sidebar-header h2 {
            color: #ecf0f1;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background-color: #34495e;
            border-left-color: #3498db;
            padding-left: 25px;
        }

        .sidebar-menu a.active {
            background-color: #3498db;
            border-left-color: #2980b9;
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            padding: 0;
            flex: 1;
        }

        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text {
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .content-area {
            padding: 30px;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
        }

        .dashboard-card.users {
            border-left-color: #3498db;
        }

        .dashboard-card.companies {
            border-left-color: #e67e22;
        }

        .dashboard-card.trips {
            border-left-color: #27ae60;
        }

        .dashboard-card.admins {
            border-left-color: #9b59b6;
        }

        .dashboard-card.company-admins {
            border-left-color: #f39c12;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .card-icon {
            font-size: 2rem;
            opacity: 0.7;
        }

        .card-icon.users { color: #3498db; }
        .card-icon.companies { color: #e67e22; }
        .card-icon.trips { color: #27ae60; }
        .card-icon.admins { color: #9b59b6; }
        .card-icon.company-admins { color: #f39c12; }

        .card-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .card-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .quick-actions h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .action-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .action-btn i {
            margin-right: 10px;
        }

        .action-btn.companies {
            background: #e67e22;
        }

        .action-btn.companies:hover {
            background: #d35400;
        }

        .action-btn.coupons {
            background: #27ae60;
        }

        .action-btn.coupons:hover {
            background: #229954;
        }

        .action-btn.company-admins {
            background: #9b59b6;
        }

        .action-btn.company-admins:hover {
            background: #8e44ad;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tachometer-alt"></i> Admin Panel</h2>
                <p><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="admin.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_companies.php"><i class="fas fa-building"></i> Firmalar</a></li>
                <li><a href="manage_company_admins.php"><i class="fas fa-user-tie"></i> Firma Adminleri</a></li>
                <li><a href="manage_coupons.php"><i class="fas fa-ticket-alt"></i> Kuponlar</a></li>
                <li><a href="admin_panel.php"><i class="fas fa-users"></i> Kullanıcılar</a></li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> Raporlar</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Ayarlar</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="welcome-text">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                </a>
            </div>

            <div class="content-area">
                <!-- Dashboard Stats -->
                <div class="dashboard-grid">
                    <div class="dashboard-card users">
                        <div class="card-header">
                            <span class="card-title">Toplam Kullanıcı</span>
                            <i class="fas fa-users card-icon users"></i>
                        </div>
                        <div class="card-number"><?= $total_users ?></div>
                        <div class="card-description">Kayıtlı normal kullanıcı sayısı</div>
                    </div>

                    <div class="dashboard-card companies">
                        <div class="card-header">
                            <span class="card-title">Toplam Firma</span>
                            <i class="fas fa-building card-icon companies"></i>
                        </div>
                        <div class="card-number"><?= $total_companies ?></div>
                        <div class="card-description">Sisteme kayıtlı otobüs firması</div>
                    </div>

                    <div class="dashboard-card trips">
                        <div class="card-header">
                            <span class="card-title">Toplam Sefer</span>
                            <i class="fas fa-bus card-icon trips"></i>
                        </div>
                        <div class="card-number"><?= $total_trips ?></div>
                        <div class="card-description">Aktif sefer sayısı</div>
                    </div>

                    <div class="dashboard-card admins">
                        <div class="card-header">
                            <span class="card-title">Sistem Admin</span>
                            <i class="fas fa-user-shield card-icon admins"></i>
                        </div>
                        <div class="card-number"><?= $total_admins ?></div>
                        <div class="card-description">Sistem yöneticisi sayısı</div>
                    </div>

                    <div class="dashboard-card company-admins">
                        <div class="card-header">
                            <span class="card-title">Firma Admin</span>
                            <i class="fas fa-user-tie card-icon company-admins"></i>
                        </div>
                        <div class="card-number"><?= $total_company_admins ?></div>
                        <div class="card-description">Firma yöneticisi sayısı</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3><i class="fas fa-bolt"></i> Hızlı İşlemler</h3>
                    <div class="actions-grid">
                        <a href="manage_companies.php" class="action-btn companies">
                            <i class="fas fa-building"></i> Firma Yönetimi
                        </a>
                        <a href="manage_company_admins.php" class="action-btn company-admins">
                            <i class="fas fa-user-tie"></i> Firma Adminleri
                        </a>
                        <a href="manage_coupons.php" class="action-btn coupons">
                            <i class="fas fa-ticket-alt"></i> Kupon Yönetimi
                        </a>
                        <a href="admin_panel.php" class="action-btn">
                            <i class="fas fa-users"></i> Kullanıcı Listesi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
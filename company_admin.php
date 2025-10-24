<?php
session_start();

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header('Location: login.php');
    exit;
}

$company_id = $_SESSION['company_id'] ?? null;
if (empty($company_id) && isset($_SESSION['user_id'])) {
    try {
        $stmtTmp = $db->prepare("SELECT company_id FROM User WHERE id = ? LIMIT 1");
        $stmtTmp->execute([$_SESSION['user_id']]);
        $u = $stmtTmp->fetch(PDO::FETCH_ASSOC);
        if ($u && !empty($u['company_id'])) {
            $company_id = $u['company_id'];
            $_SESSION['company_id'] = $company_id;
        }
    } catch (PDOException $e) {
    }
}

$msg = '';
if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

if (isset($_GET['delete'])) {
    $trip_id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
    $msg = "🗑️ Sefer silindi.";
}

$stmt = $db->prepare("SELECT * FROM Trips WHERE company_id = ?");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Admin Paneli</title>
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

        .page-title {
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #7f8c8d;
        }

        .content-area {
            padding: 30px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .welcome-title {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #d5f7e8;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        /* Action Bar */
        .action-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-info h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .action-info p {
            color: #7f8c8d;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            font-size: 0.8rem;
            padding: 8px 12px;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.3);
        }

        /* Trips Table */
        .trips-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trips-count {
            background: #3498db;
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
        }

        .trips-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .trips-table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.9rem;
        }

        .trips-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .trips-table tr:hover {
            background: #f8f9fa;
        }

        .trip-route {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .route-arrow {
            color: #3498db;
        }

        .trip-time {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .trip-price {
            font-weight: bold;
            color: #27ae60;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .no-trips {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .no-trips-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #bdc3c7;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .action-bar {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .table-container {
                font-size: 0.8rem;
            }

            .trips-table th,
            .trips-table td {
                padding: 10px 8px;
            }

            .table-actions {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-building"></i> Firma Panel</h2>
                <p><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="company_admin.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="add_trip.php"><i class="fas fa-plus"></i> Sefer Ekle</a></li>
                <li><a href="index.php"><i class="fas fa-home"></i> Ana Sayfa</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <i class="fas fa-building"></i> Firma Admin Paneli
                </div>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>

            <div class="content-area">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <h1 class="welcome-title">
                        <i class="fas fa-hand-wave"></i>
                        Hoş geldiniz, <?= htmlspecialchars($_SESSION['username']) ?>!
                    </h1>
                    <p class="welcome-subtitle">
                        Firma sefer yönetim panelinize hoş geldiniz. Buradan seferlerinizi yönetebilirsiniz.
                    </p>
                </div>

                <!-- Messages -->
                <?php if (!empty($msg)): ?>
                    <div class="message">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <!-- Action Bar -->
                <div class="action-bar">
                    <div class="action-info">
                        <h3>Sefer Yönetimi</h3>
                        <p>Firmanızın seferlerini yönetin ve yeni sefer ekleyin</p>
                    </div>
                    <a href="add_trip.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Yeni Sefer Ekle
                    </a>
                </div>

                <!-- Trips Table -->
                <div class="trips-card">
                    <div class="table-header">
                        <h2 class="table-title">
                            <i class="fas fa-bus"></i> Kayıtlı Seferler
                        </h2>
                        <span class="trips-count"><?= count($trips) ?> sefer</span>
                    </div>
                    
                    <?php if (empty($trips)): ?>
                        <div class="no-trips">
                            <div class="no-trips-icon">
                                <i class="fas fa-bus"></i>
                            </div>
                            <h3>Henüz sefer bulunmuyor</h3>
                            <p>İlk seferinizi eklemek için yukarıdaki butonu kullanın</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="trips-table">
                                <thead>
                                    <tr>
                                        <th>Sefer ID</th>
                                        <th>Güzergah</th>
                                        <th>Kalkış</th>
                                        <th>Varış</th>
                                        <th>Fiyat</th>
                                        <th>Kapasite</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trips as $trip): ?>
                                        <tr>
                                            <td>
                                                <code><?= htmlspecialchars($trip['id']) ?></code>
                                            </td>
                                            <td>
                                                <div class="trip-route">
                                                    <?= htmlspecialchars($trip['departure_city']) ?>
                                                    <i class="fas fa-arrow-right route-arrow"></i>
                                                    <?= htmlspecialchars($trip['destination_city']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="trip-time">
                                                    <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="trip-time">
                                                    <?= date('d.m.Y H:i', strtotime($trip['arrival_time'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="trip-price"><?= htmlspecialchars($trip['price']) ?> ₺</span>
                                            </td>
                                            <td>
                                                <span><?= htmlspecialchars($trip['capacity']) ?> kişi</span>
                                            </td>
                                            <td>
                                                <div class="table-actions">
                                                    <a href="add_trip.php?id=<?= urlencode($trip['id']) ?>" 
                                                       class="btn btn-primary" style="font-size: 0.8rem; padding: 8px 12px;">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                    <a href="?delete=<?= $trip['id'] ?>" 
                                                       class="btn btn-danger"
                                                       onclick="return confirm('Bu seferi silmek istediğinize emin misiniz?')">
                                                        <i class="fas fa-trash"></i> Sil
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header('Location: login.php');
    exit;
}

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}

$company_id = $_SESSION['company_id'] ?? null;
if (empty($company_id)) {
    $stmtTmp = $db->prepare("SELECT company_id FROM User WHERE id = ? LIMIT 1");
    $stmtTmp->execute([$_SESSION['user_id']]);
    $u = $stmtTmp->fetch(PDO::FETCH_ASSOC);
    if ($u && !empty($u['company_id'])) {
        $company_id = $u['company_id'];
        $_SESSION['company_id'] = $company_id;
    }
}

if (empty($company_id)) {
    $_SESSION['flash_msg'] = "⚠️ Firma bilgisi bulunamadı. Lütfen tekrar giriş yapınız.";
    header('Location: company_admin.php');
    exit;
}

$editing = false;
$trip = [
    'id' => '',
    'departure_city' => '',
    'destination_city' => '',
    'departure_time' => '',
    'arrival_time' => '',
    'price' => '',
    'capacity' => ''
];

if (!empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ? LIMIT 1");
    $stmt->execute([$_GET['id'], $company_id]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($t) {
        $editing = true;
        $trip = $t;
        $dt = new DateTime($trip['departure_time']);
        $trip['departure_time'] = $dt->format('Y-m-d\TH:i');
        $dt2 = new DateTime($trip['arrival_time']);
        $trip['arrival_time'] = $dt2->format('Y-m-d\TH:i');
    } else {
        $_SESSION['flash_msg'] = "⚠️ Sefer bulunamadı veya yetkiniz yok.";
        header('Location: company_admin.php');
        exit;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = trim($_POST['departure'] ?? '');
    $arrival = trim($_POST['arrival'] ?? '');
    $departure_time_raw = $_POST['departure_time'] ?? '';
    $arrival_time_raw = $_POST['arrival_time'] ?? '';
    $price_raw = $_POST['price'] ?? '';
    $capacity_raw = $_POST['capacity'] ?? '';

    if ($departure === '' || $arrival === '' || $departure_time_raw === '' || $arrival_time_raw === '' || $price_raw === '' || $capacity_raw === '') {
        $errors[] = 'Lütfen tüm alanları doldurun.';
    }

    try {
        $dt1 = new DateTime($departure_time_raw);
        $departure_time = $dt1->format('Y-m-d H:i:s');
        $dt2 = new DateTime($arrival_time_raw);
        $arrival_time = $dt2->format('Y-m-d H:i:s');

        if ($arrival_time <= $departure_time) {
            $errors[] = 'Varış zamanı, kalkış zamanından sonra olmalıdır.';
        }
    } catch (Exception $e) {
        $errors[] = 'Geçersiz tarih/saat formatı.';
    }

    $price = floatval($price_raw);
    $capacity = intval($capacity_raw);
    if ($price <= 0) $errors[] = 'Fiyat 0 veya negatif olamaz.';
    if ($capacity <= 0) $errors[] = 'Kapasite 0 veya negatif olamaz.';

    if (empty($errors)) {
        if (!empty($_POST['id'])) {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE Trips SET departure_city = ?, destination_city = ?, departure_time = ?, arrival_time = ?, price = ?, capacity = ? WHERE id = ? AND company_id = ?");
            try {
                $stmt->execute([$departure, $arrival, $departure_time, $arrival_time, $price, $capacity, $id, $company_id]);
                $_SESSION['flash_msg'] = '✅ Sefer başarıyla güncellendi!';
                header('Location: company_admin.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        } else {
            $id = uniqid('TRP');
            $stmt = $db->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$id, $company_id, $departure, $arrival, $departure_time, $arrival_time, $price, $capacity]);
                $_SESSION['flash_msg'] = '✅ Sefer başarıyla eklendi!';
                header('Location: company_admin.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editing ? 'Seferi Düzenle' : 'Yeni Sefer Ekle' ?></title>
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

        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 800px;
        }

        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: #fdf2f2;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .alert ul {
            margin: 0;
            padding-left: 20px;
        }

        .alert li {
            margin-bottom: 5px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e6ed;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            min-width: 120px;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(149, 165, 166, 0.3);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .content-area {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-building"></i> Firma Panel</h2>
                <p><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="company_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="add_trip.php" class="active"><i class="fas fa-plus"></i> Sefer Ekle</a></li>
                <li><a href="index.php"><i class="fas fa-home"></i> Ana Sayfa</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <i class="fas fa-<?= $editing ? 'edit' : 'plus' ?>"></i> 
                    <?= $editing ? 'Seferi Düzenle' : 'Yeni Sefer Ekle' ?>
                </div>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                </div>
            </div>

            <div class="content-area">
                <div class="form-card">
                    <div class="form-header">
                        <h2>
                            <i class="fas fa-<?= $editing ? 'edit' : 'route' ?>"></i>
                            <?= $editing ? 'Sefer Bilgilerini Düzenle' : 'Yeni Sefer Bilgileri' ?>
                        </h2>
                        <p><?= $editing ? 'Sefer bilgilerini güncelleyebilirsiniz' : 'Lütfen sefer detaylarını eksiksiz doldurunuz' ?></p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>
                                <strong>Hata!</strong> Aşağıdaki sorunları düzeltiniz:
                                <ul>
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= htmlspecialchars($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($trip['id']) ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt"></i> Kalkış Şehri</label>
                                <input type="text" name="departure" value="<?= htmlspecialchars($trip['departure_city']) ?>" required placeholder="Örn: İstanbul">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-flag-checkered"></i> Varış Şehri</label>
                                <input type="text" name="arrival" value="<?= htmlspecialchars($trip['destination_city']) ?>" required placeholder="Örn: Ankara">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Kalkış Zamanı</label>
                                <input type="datetime-local" name="departure_time" value="<?= htmlspecialchars($trip['departure_time']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Varış Zamanı</label>
                                <input type="datetime-local" name="arrival_time" value="<?= htmlspecialchars($trip['arrival_time']) ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-lira-sign"></i> Fiyat (₺)</label>
                                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($trip['price']) ?>" required placeholder="0.00" min="0">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Kapasite</label>
                                <input type="number" name="capacity" value="<?= htmlspecialchars($trip['capacity']) ?>" required placeholder="40" min="1">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-<?= $editing ? 'save' : 'plus' ?>"></i>
                                <?= $editing ? 'Güncelle' : 'Sefer Ekle' ?>
                            </button>
                            <a href="company_admin.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

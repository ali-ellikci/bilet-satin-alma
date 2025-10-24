<?php
session_start();

// Kullanıcı giriş yapmamışsa, doğrudan login.php'ye yönlendir.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user_role'] == 'admin') {
    header('Location: index.php');
    exit;
}

// Veritabanı bağlantısı
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Giriş yapan kullanıcının bilgilerini veritabanından çek
try {
    $stmt = $db->prepare("SELECT full_name, email, balance FROM User WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabım - Bilet Satın Alma</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            text-decoration: none;
        }

        .logo i {
            margin-right: 10px;
            color: #667eea;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .user-name {
            color: #2c3e50;
            font-weight: 500;
        }

        .header-btn {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #667eea;
            color: white;
        }

        .header-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        /* Main Container */
        .main-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .account-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .account-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .account-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .account-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .account-content {
            padding: 40px;
        }

        /* User Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-3px);
        }

        .info-card.balance {
            border-left-color: #27ae60;
        }

        .info-card.email {
            border-left-color: #e67e22;
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }

        .card-icon.balance {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .card-icon.email {
            background: linear-gradient(135deg, #e67e22, #f39c12);
        }

        .card-title {
            color: #2c3e50;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-value {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: bold;
            margin-top: 10px;
        }

        .balance-amount {
            color: #27ae60;
            font-size: 1.5rem;
        }

        /* Action Buttons */
        .actions-section {
            margin-top: 40px;
        }

        .section-title {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .action-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .action-btn.tickets {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }

        .action-btn.tickets:hover {
            box-shadow: 0 10px 25px rgba(39, 174, 96, 0.3);
        }

        .action-btn.logout {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .action-btn.logout:hover {
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.3);
        }

        /* Stats Section */
        .stats-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }

        .stats-title {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px 10px;
            }

            .account-content {
                padding: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .account-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-bus"></i>
                Bilet Satın Alma
            </a>
            
            <div class="header-actions">
                <span class="user-name">
                    <i class="fas fa-user"></i> 
                    <?= htmlspecialchars($user['full_name']) ?>
                </span>
                <a href="index.php" class="header-btn">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
                <a href="logout.php" class="header-btn">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="account-container">
            <div class="account-header">
                <h1><i class="fas fa-user-circle"></i> Hesap Bilgilerim</h1>
                <p>Kişisel bilgilerinizi ve hesap durumunuzu görüntüleyin</p>
            </div>

            <div class="account-content">
                <!-- User Info Cards -->
                <div class="info-grid">
                    <div class="info-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="card-title">Ad Soyad</div>
                            </div>
                        </div>
                        <div class="card-value"><?= htmlspecialchars($user['full_name']) ?></div>
                    </div>

                    <div class="info-card email">
                        <div class="card-header">
                            <div class="card-icon email">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <div class="card-title">E-posta Adresi</div>
                            </div>
                        </div>
                        <div class="card-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>

                    <div class="info-card balance">
                        <div class="card-header">
                            <div class="card-icon balance">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div>
                                <div class="card-title">Hesap Bakiyesi</div>
                            </div>
                        </div>
                        <div class="card-value balance-amount"><?= number_format($user['balance'], 2) ?> ₺</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="actions-section">
                    <h2 class="section-title">
                        <i class="fas fa-bolt"></i> Hızlı İşlemler
                    </h2>
                    <div class="actions-grid">
                        <a href="my_tickets.php" class="action-btn tickets">
                            <i class="fas fa-ticket-alt"></i>
                            Biletlerim
                        </a>
                        <a href="index.php" class="action-btn">
                            <i class="fas fa-search"></i>
                            Yeni Bilet Ara
                        </a>
                        <a href="logout.php" class="action-btn logout">
                            <i class="fas fa-sign-out-alt"></i>
                            Çıkış Yap
                        </a>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="stats-section">
                    <h3 class="stats-title">
                        <i class="fas fa-chart-bar"></i> Hesap İstatistikleri
                    </h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Toplam Bilet</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0</div>
                            <div class="stat-label">Bu Ay</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">0₺</div>
                            <div class="stat-label">Toplam Harcama</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
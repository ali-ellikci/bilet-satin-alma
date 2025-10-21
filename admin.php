<?php
session_start();

// GÜVENLİK: Kullanıcı giriş yapmış ve rolü 'admin' olmalı.
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); // Forbidden
    die("<h1>Erişim Yetkiniz Yok!</h1><p>Bu alana yalnızca yöneticiler erişebilir.</p>");
}

// Veritabanı bağlantısı (Dosya ana dizinde olduğu için yol değişti)
$db = new PDO('sqlite:database.db'); 
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Dashboard için temel istatistikleri çekelim
$total_users = $db->query("SELECT COUNT(*) FROM User WHERE role = 'user'")->fetchColumn();
$total_companies = $db->query("SELECT COUNT(*) FROM Bus_Company")->fetchColumn();
$total_trips = $db->query("SELECT COUNT(*) FROM Trips")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="assets/style.css"> 
    <style>
        /* Admin paneline özel stiller */
        .admin-header {
            background-color: #333; color: white; padding: 10px 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .admin-header a { color: white; text-decoration: none; }
        .admin-header a:hover { text-decoration: underline; }
        
        .stat-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .stat-card {
            background-color: #fff; padding: 20px; border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center;
        }
        .stat-card h3 { margin-top: 0; }
        .stat-card .number { font-size: 36px; font-weight: bold; color: #dc2626; }
        body.dark .stat-card { background-color: #2b0000; }

        .management-links .btn {
             margin: 5px; /* Butonlar arasına boşluk koy */
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1>Hoş Geldin, <?= htmlspecialchars($_SESSION['user_fullname']) ?>!</h1>

    <div class="stat-grid">
        <div class="stat-card">
            <h3>Toplam Yolcu</h3>
            <p class="number"><?= $total_users ?></p>
        </div>
        <div class="stat-card">
            <h3>Toplam Firma</h3>
            <p class="number"><?= $total_companies ?></p>
        </div>
        <div class="stat-card">
            <h3>Toplam Sefer</h3>
            <p class="number"><?= $total_trips ?></p>
        </div>
    </div>

    <hr>
    
    <div class="management-links">
        <h2>Yönetim Linkleri</h2>
        <p>Buradan firmaları, kullanıcıları ve diğer ayarları yönetebilirsiniz.</p>
        <a href="manage_companies.php" class="btn">Firmaları Yönet</a>
        <a href="manage_users.php" class="btn">Kullanıcıları Yönet</a>
    </div>

</div>

</body>
</html>
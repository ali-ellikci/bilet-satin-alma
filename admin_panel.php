<?php
session_start();

// Only super admins (role 'admin') allowed
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die("<h1>Erişim Yetkiniz Yok!</h1><p>Bu alana yalnızca yöneticiler erişebilir.</p>");
}

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Veritabanına bağlanılamadı: ' . $e->getMessage());
}

// Flash messages
$msg = '';
if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// Get statistics for dashboard
$total_users = $db->query("SELECT COUNT(*) FROM User WHERE role = 'user'")->fetchColumn();
$total_companies = $db->query("SELECT COUNT(*) FROM Bus_Company")->fetchColumn();
$total_trips = $db->query("SELECT COUNT(*) FROM Trips")->fetchColumn();
$total_company_admins = $db->query("SELECT COUNT(*) FROM User WHERE role = 'company_admin'")->fetchColumn();

$total_coupons = 0;
try {
    $total_coupons = $db->query("SELECT COUNT(*) FROM Coupons")->fetchColumn();
} catch (Exception $e) {
    // ignore if table doesn't exist
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetici Paneli</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,.06); text-align:center; }
        .stat-card h3 { margin-top:0; color:#333; }
        .stat-card .number { font-size:36px; font-weight:bold; color:#dc2626; margin:10px 0; }
        .management-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .management-card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
        .management-card h3 { margin-top:0; color:#333; }
        .management-card p { color:#666; margin-bottom:15px; }
        .btn { display:inline-block; padding:12px 20px; background:#dc2626; color:#fff; border-radius:6px; text-decoration:none; }
        .btn:hover { background:#b91c1c; }
        .msg { margin-bottom:20px; padding:12px; background:#f3f4f6; border-radius:6px; }
        body.dark .stat-card, body.dark .management-card { background:#2b0000; }
        body.dark .stat-card h3, body.dark .management-card h3 { color:#fff; }
        body.dark .management-card p { color:#ccc; }
    </style>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1>Yönetici Paneli</h1>
    <p>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>! 👋</p>

    <?php if (!empty($msg)): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Toplam Yolcu</h3>
            <div class="number"><?= $total_users ?></div>
        </div>
        <div class="stat-card">
            <h3>Toplam Firma</h3>
            <div class="number"><?= $total_companies ?></div>
        </div>
        <div class="stat-card">
            <h3>Toplam Sefer</h3>
            <div class="number"><?= $total_trips ?></div>
        </div>
        <div class="stat-card">
            <h3>Firma Yöneticileri</h3>
            <div class="number"><?= $total_company_admins ?></div>
        </div>
        <div class="stat-card">
            <h3>Toplam Kupon</h3>
            <div class="number"><?= $total_coupons ?></div>
        </div>
    </div>

    <h2>Yönetim İşlemleri</h2>
    <div class="management-links">
        <div class="management-card">
            <h3>🏢 Firma Yönetimi</h3>
            <p>Firmaları ekle, düzenle ve sil. Firma bilgilerini güncelleyebilirsin.</p>
            <a href="manage_companies.php" class="btn">Firmaları Yönet</a>
        </div>

        <div class="management-card">
            <h3>👥 Firma Yöneticileri</h3>
            <p>Firma yöneticilerini ekle, düzenle ve firmalarla eşleştir.</p>
            <a href="manage_company_admins.php" class="btn">Yöneticileri Yönet</a>
        </div>

        <div class="management-card">
            <h3>🎫 Kupon Yönetimi</h3>
            <p>İndirim kuponlarını oluştur, düzenle ve yönet.</p>
            <a href="manage_coupons.php" class="btn">Kuponları Yönet</a>
        </div>
    </div>

</div>

</body>
</html>

<?php
session_start();

// Veritabanı
try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanına bağlanılamadı: " . $e->getMessage());
}

// Yetki kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company_admin') {
    header('Location: login.php');
    exit;
}

$company_id = $_SESSION['company_id'] ?? null;
// If company_id is not present in session, try to fetch it from the User table
if (empty($company_id) && isset($_SESSION['user_id'])) {
    try {
        $stmtTmp = $db->prepare("SELECT company_id FROM User WHERE id = ? LIMIT 1");
        $stmtTmp->execute([$_SESSION['user_id']]);
        $u = $stmtTmp->fetch(PDO::FETCH_ASSOC);
        if ($u && !empty($u['company_id'])) {
            $company_id = $u['company_id'];
            // persist back to session so future requests don't need DB hit
            $_SESSION['company_id'] = $company_id;
        }
    } catch (PDOException $e) {
        // ignore here; later checks will show missing company info
    }
}
// flash message support (set by add_trip.php)
$msg = '';
if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// --- Sefer Silme ---
if (isset($_GET['delete'])) {
    $trip_id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt->execute([$trip_id, $company_id]);
    $msg = "🗑️ Sefer silindi.";
}

// --- Seferleri Getir ---
$stmt = $db->prepare("SELECT * FROM Trips WHERE company_id = ?");
$stmt->execute([$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Admin Paneli</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/company_admin.css">
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<?php if (!empty($msg)): ?>
    <div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<div class="container admin-page">

    <h1>Firma Admin Paneli</h1>
    <p>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> 👋</p>

    <div class="admin-actions">
        <a class="btn" href="add_trip.php">➕ Yeni Sefer Ekle</a>
    </div>

    <h2>Mevcut Seferler</h2>
    <table class="table-admin">
    <tr>
        <th>ID</th>
        <th>Kalkış</th>
        <th>Varış</th>
        <th>Kalkış Zamanı</th>
        <th>Varış Zamanı</th>
        <th>Fiyat</th>
        <th>Kapsite</th>
        <th>İşlem</th>
    </tr>
    <?php foreach ($trips as $trip): ?>
        <tr>
            <td><?= $trip['id'] ?></td>
            <td><?= htmlspecialchars($trip['departure_city']) ?></td>
            <td><?= htmlspecialchars($trip['destination_city']) ?></td>
            <td><?= htmlspecialchars($trip['departure_time']) ?></td>
            <td><?= htmlspecialchars($trip['arrival_time']) ?></td>
            <td><?= htmlspecialchars($trip['price']) ?> ₺</td>
            <td><?= htmlspecialchars($trip['capacity']) ?></td>
            <td>
                <a class="btn" href="add_trip.php?id=<?= urlencode($trip['id']) ?>">Düzenle</a>
                <a class="delete-link" href="?delete=<?= $trip['id'] ?>" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

    <p class="admin-footer"><a href="logout.php">🚪 Çıkış Yap</a></p>

</div>
</body>
</html>

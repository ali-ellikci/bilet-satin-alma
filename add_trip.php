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
    <title><?= $editing ? 'Seferi Düzenle' : 'Yeni Sefer Ekle' ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/company_admin.css">
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container admin-page">
    <h1><?= $editing ? 'Seferi Düzenle' : 'Yeni Sefer Ekle' ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="msg msg-error">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="company-form">
        <input type="hidden" name="id" value="<?= htmlspecialchars($trip['id']) ?>">

        <label>Kalkış Şehri:</label>
        <input type="text" name="departure" value="<?= htmlspecialchars($trip['departure_city']) ?>" required>

        <label>Varış Şehri:</label>
        <input type="text" name="arrival" value="<?= htmlspecialchars($trip['destination_city']) ?>" required>

        <label>Kalkış Zamanı:</label>
        <input type="datetime-local" name="departure_time" value="<?= htmlspecialchars($trip['departure_time']) ?>" required>

        <label>Varış Zamanı:</label>
        <input type="datetime-local" name="arrival_time" value="<?= htmlspecialchars($trip['arrival_time']) ?>" required>

        <label>Fiyat (₺):</label>
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($trip['price']) ?>" required>

        <label>Kapasite:</label>
        <input type="number" name="capacity" value="<?= htmlspecialchars($trip['capacity']) ?>" required>

        <div class="form-actions">
            <button type="submit" class="btn"><?= $editing ? 'Güncelle' : 'Ekle' ?></button>
            <a class="btn" href="company_admin.php">İptal</a>
        </div>
    </form>

</div>

</body>
</html>

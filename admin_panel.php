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

// Handle create/delete actions via POST/GET
// Create Company
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_company'])) {
    $name = trim($_POST['company_name'] ?? '');
    if ($name === '') {
        $_SESSION['flash_msg'] = 'Firma adı boş olamaz.';
    } else {
        $stmt = $db->prepare('INSERT INTO Bus_Company (id, name) VALUES (?, ?)');
        try {
            $stmt->execute([uniqid('CMP'), $name]);
            $_SESSION['flash_msg'] = '✅ Firma eklendi.';
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
    header('Location: admin_panel.php#companies');
    exit;
}

// Delete Company
if (isset($_GET['delete_company'])) {
    $id = $_GET['delete_company'];
    $stmt = $db->prepare('DELETE FROM Bus_Company WHERE id = ?');
    $stmt->execute([$id]);
    $_SESSION['flash_msg'] = '🗑️ Firma silindi.';
    header('Location: admin_panel.php#companies');
    exit;
}

// Create company admin user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_company_admin'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $company_id = $_POST['company_id'] ?? '';

    if ($full_name === '' || $email === '' || $password === '' || $company_id === '') {
        $_SESSION['flash_msg'] = 'Tüm alanları doldurun.';
        header('Location: admin_panel.php#company_admins');
        exit;
    }

    // basic email uniqueness check
    $s = $db->prepare('SELECT id FROM User WHERE email = ? LIMIT 1');
    $s->execute([$email]);
    if ($s->fetch()) {
        $_SESSION['flash_msg'] = 'E-posta zaten kayıtlı.';
        header('Location: admin_panel.php#company_admins');
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO User (id, full_name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?, ?)');
    try {
        $stmt->execute([uniqid('USR'), $full_name, $email, $hashed, 'company_admin', $company_id]);
        $_SESSION['flash_msg'] = '✅ Firma yöneticisi eklendi.';
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = 'Veritabanı hatası: ' . $e->getMessage();
    }
    header('Location: admin_panel.php#company_admins');
    exit;
}

// Delete company admin user
if (isset($_GET['delete_admin_user'])) {
    $id = $_GET['delete_admin_user'];
    $stmt = $db->prepare('DELETE FROM User WHERE id = ? AND role = ?');
    $stmt->execute([$id, 'company_admin']);
    $_SESSION['flash_msg'] = '🗑️ Firma yöneticisi silindi.';
    header('Location: admin_panel.php#company_admins');
    exit;
}

// Coupons: simple table 'Coupons' assumed with (id, code, discount_percent, expires_at)
// Create coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
    $code = trim($_POST['code'] ?? '');
    $discount = floatval($_POST['discount'] ?? 0);
    $expires_raw = $_POST['expires_at'] ?? '';

    if ($code === '' || $discount <= 0 || $expires_raw === '') {
        $_SESSION['flash_msg'] = 'Lütfen tüm kupon alanlarını doğru doldurun.';
        header('Location: admin_panel.php#coupons');
        exit;
    }

    try {
        $dt = new DateTime($expires_raw);
        $expires = $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        $_SESSION['flash_msg'] = 'Geçersiz tarih formatı.';
        header('Location: admin_panel.php#coupons');
        exit;
    }

    $stmt = $db->prepare('INSERT INTO Coupons (id, code, discount_percent, expires_at) VALUES (?, ?, ?, ?)');
    try {
        $stmt->execute([uniqid('CUP'), $code, $discount, $expires]);
        $_SESSION['flash_msg'] = '✅ Kupon eklendi.';
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = 'Veritabanı hatası: ' . $e->getMessage();
    }
    header('Location: admin_panel.php#coupons');
    exit;
}

// Delete coupon
if (isset($_GET['delete_coupon'])) {
    $id = $_GET['delete_coupon'];
    $stmt = $db->prepare('DELETE FROM Coupons WHERE id = ?');
    $stmt->execute([$id]);
    $_SESSION['flash_msg'] = '🗑️ Kupon silindi.';
    header('Location: admin_panel.php#coupons');
    exit;
}

// Fetch lists
$companies = $db->query('SELECT * FROM Bus_Company ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$company_admins = $db->prepare("SELECT * FROM User WHERE role = 'company_admin' ORDER BY full_name");
$company_admins->execute();
$company_admins = $company_admins->fetchAll(PDO::FETCH_ASSOC);

$coupons = [];
try {
    $coupons = $db->query('SELECT * FROM Coupons ORDER BY expires_at DESC')->fetchAll(PDO::FETCH_ASSOC);
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
        .admin-sections { display: grid; grid-template-columns: 1fr; gap: 24px; }
        .card { background:#fff; padding:16px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
        .card h3 { margin-top:0; }
        .list-table { width:100%; border-collapse: collapse; }
        .list-table th, .list-table td { padding:8px 10px; border-bottom:1px solid #eee; }
        .btn { display:inline-block; padding:8px 12px; background:#dc2626; color:#fff; border-radius:6px; text-decoration:none; }
        .delete-link { color:#777; margin-left:8px; }
        .msg { margin-bottom:12px; padding:10px; background:#f3f4f6; border-radius:6px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1>Yönetici Paneli</h1>

    <?php if (!empty($msg)): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="admin-sections">
        <div class="card" id="companies">
            <h3>Firmalar</h3>
            <form method="POST" style="margin-bottom:12px">
                <input type="text" name="company_name" placeholder="Firma adı">
                <button class="btn" name="create_company">Ekle</button>
            </form>
            <table class="list-table">
                <tr><th>ID</th><th>Ad</th><th>İşlem</th></tr>
                <?php foreach ($companies as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['id']) ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td>
                            <a class="btn" href="?delete_company=<?= urlencode($c['id']) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card" id="company_admins">
            <h3>Firma Yöneticileri</h3>
            <form method="POST" style="margin-bottom:12px">
                <input type="text" name="full_name" placeholder="Ad Soyad">
                <input type="email" name="email" placeholder="E-posta">
                <input type="password" name="password" placeholder="Şifre">
                <select name="company_id">
                    <option value="">Firma seç</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" name="create_company_admin">Ekle</button>
            </form>

            <table class="list-table">
                <tr><th>ID</th><th>İsim</th><th>E-posta</th><th>Firma</th><th>İşlem</th></tr>
                <?php foreach ($company_admins as $a): ?>
                    <?php $companyName = '';
                        foreach ($companies as $c) { if ($c['id'] === $a['company_id']) { $companyName = $c['name']; break; } }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($a['id']) ?></td>
                        <td><?= htmlspecialchars($a['full_name']) ?></td>
                        <td><?= htmlspecialchars($a['email']) ?></td>
                        <td><?= htmlspecialchars($companyName) ?></td>
                        <td>
                            <a class="btn" href="?delete_admin_user=<?= urlencode($a['id']) ?>" onclick="return confirm('Silinsin mi?')">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card" id="coupons">
            <h3>Kuponlar</h3>
            <form method="POST" style="margin-bottom:12px">
                <input type="text" name="code" placeholder="Kodu">
                <input type="number" step="0.01" name="discount" placeholder="İndirim (%)">
                <input type="datetime-local" name="expires_at">
                <button class="btn" name="create_coupon">Ekle</button>
            </form>

            <table class="list-table">
                <tr><th>Kod</th><th>İndirim</th><th>Sona Erme</th><th>İşlem</th></tr>
                <?php foreach ($coupons as $cup): ?>
                    <tr>
                        <td><?= htmlspecialchars($cup['code']) ?></td>
                        <td><?= htmlspecialchars($cup['discount_percent']) ?> %</td>
                        <td><?= htmlspecialchars($cup['expires_at']) ?></td>
                        <td><a class="btn" href="?delete_coupon=<?= urlencode($cup['id']) ?>" onclick="return confirm('Silinsin mi?')">Sil</a></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</div>

</body>
</html>

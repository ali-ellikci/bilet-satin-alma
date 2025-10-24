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

$editing = false;
$admin_user = [
    'id' => '',
    'full_name' => '',
    'email' => '',
    'company_id' => ''
];

// Check if editing
if (!empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM User WHERE id = ? AND role = 'company_admin' LIMIT 1");
    $stmt->execute([$_GET['id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $editing = true;
        $admin_user = $u;
    } else {
        $_SESSION['flash_msg'] = '⚠️ Firma yöneticisi bulunamadı.';
        header('Location: manage_company_admins.php');
        exit;
    }
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $company_id = $_POST['company_id'] ?? '';
    
    if ($full_name === '' || $email === '' || $company_id === '') {
        $errors[] = 'Ad soyad, e-posta ve firma alanları zorunludur.';
    }
    
    if (!$editing && $password === '') {
        $errors[] = 'Yeni kullanıcı için şifre zorunludur.';
    }

    // Check email uniqueness
    if (!empty($email)) {
        $stmt = $db->prepare('SELECT id FROM User WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $admin_user['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Bu e-posta adresi zaten kullanılıyor.';
        }
    }

    if (empty($errors)) {
        if (!empty($_POST['id'])) {
            // Update existing admin
            $id = $_POST['id'];
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE User SET full_name = ?, email = ?, password = ?, company_id = ? WHERE id = ?');
                $params = [$full_name, $email, $hashed, $company_id, $id];
            } else {
                $stmt = $db->prepare('UPDATE User SET full_name = ?, email = ?, company_id = ? WHERE id = ?');
                $params = [$full_name, $email, $company_id, $id];
            }
            
            try {
                $stmt->execute($params);
                $_SESSION['flash_msg'] = '✅ Firma yöneticisi güncellendi.';
                header('Location: manage_company_admins.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        } else {
            // Create new admin
            $id = uniqid('USR');
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO User (id, full_name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?, ?)');
            try {
                $stmt->execute([$id, $full_name, $email, $hashed, 'company_admin', $company_id]);
                $_SESSION['flash_msg'] = '✅ Firma yöneticisi eklendi.';
                header('Location: manage_company_admins.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare("DELETE FROM User WHERE id = ? AND role = 'company_admin'");
    $stmt->execute([$id]);
    $_SESSION['flash_msg'] = '🗑️ Firma yöneticisi silindi.';
    header('Location: manage_company_admins.php');
    exit;
}

// Fetch data
$companies = $db->query('SELECT * FROM Bus_Company ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$company_admins = $db->prepare("SELECT * FROM User WHERE role = 'company_admin' ORDER BY full_name");
$company_admins->execute();
$company_admins = $company_admins->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= $editing ? 'Firma Yöneticisini Düzenle' : 'Firma Yöneticisi Yönetimi' ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,.06); margin-bottom:20px; }
        .card h3 { margin-top:0; }
        .list-table { width:100%; border-collapse: collapse; }
        .list-table th, .list-table td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
        .btn { display:inline-block; padding:10px 16px; background:#dc2626; color:#fff; border-radius:6px; text-decoration:none; margin:2px; }
        .btn:hover { background:#b91c1c; }
        .msg { margin-bottom:15px; padding:12px; background:#f3f4f6; border-radius:6px; }
        .msg-error { background:#fef2f2; color:#dc2626; }
        .form-group { margin-bottom:15px; }
        .form-group label { display:block; margin-bottom:5px; font-weight:bold; }
        .form-group input, .form-group select { width:100%; padding:8px; border:1px solid #ddd; border-radius:4px; }
        .form-actions { margin-top:20px; }
        body.dark .card { background:#2b0000; }
        body.dark .list-table th, body.dark .list-table td { border-bottom:1px solid #444; }
    </style>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <h1>Firma Yöneticisi Yönetimi</h1>
    
    <div style="margin-bottom:20px;">
        <a class="btn" href="admin_panel.php">← Yönetici Paneli</a>
        <?php if (!$editing): ?>
            <a class="btn" href="manage_company_admins.php?new=1">➕ Yeni Firma Yöneticisi Ekle</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="msg msg-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($editing || isset($_GET['new'])): ?>
        <div class="card">
            <h3><?= $editing ? 'Firma Yöneticisini Düzenle' : 'Yeni Firma Yöneticisi Ekle' ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($admin_user['id']) ?>">
                
                <div class="form-group">
                    <label>Ad Soyad:</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($admin_user['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>E-posta:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin_user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Şifre <?= $editing ? '(Boş bırakırsan değişmez)' : '' ?>:</label>
                    <input type="password" name="password" <?= $editing ? '' : 'required' ?>>
                </div>

                <div class="form-group">
                    <label>Firma:</label>
                    <select name="company_id" required>
                        <option value="">Firma seç</option>
                        <?php foreach ($companies as $c): ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>" 
                                    <?= $c['id'] === $admin_user['company_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn"><?= $editing ? 'Güncelle' : 'Ekle' ?></button>
                    <a class="btn" href="manage_company_admins.php">İptal</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Mevcut Firma Yöneticileri</h3>
        <table class="list-table">
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Firma</th>
                <th>İşlemler</th>
            </tr>
            <?php foreach ($company_admins as $a): ?>
                <?php 
                $companyName = '';
                foreach ($companies as $c) { 
                    if ($c['id'] === $a['company_id']) { 
                        $companyName = $c['name']; 
                        break; 
                    } 
                }
                ?>
                <tr>
                    <td><?= htmlspecialchars($a['id']) ?></td>
                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><?= htmlspecialchars($companyName) ?></td>
                    <td>
                        <a class="btn" href="manage_company_admins.php?id=<?= urlencode($a['id']) ?>">Düzenle</a>
                        <a class="btn" href="manage_company_admins.php?delete=<?= urlencode($a['id']) ?>" 
                           onclick="return confirm('Bu firma yöneticisini silmek istediğinize emin misiniz?')">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

</div>

</body>
</html>
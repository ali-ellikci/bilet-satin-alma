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
$coupon = ['id' => '', 'code' => '', 'discount' => '', 'usage_limit' => '', 'expire_date' => ''];

// Check if editing
if (!empty($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM Coupons WHERE id = ? LIMIT 1');
    $stmt->execute([$_GET['id']]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($c) {
        $editing = true;
        $coupon = $c;
        // Format date for input
        $coupon['expire_date'] = date('Y-m-d\TH:i', strtotime($coupon['expire_date']));
    } else {
        $_SESSION['flash_msg'] = '⚠️ Kupon bulunamadı.';
        header('Location: manage_coupons.php');
        exit;
    }
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount = trim($_POST['discount'] ?? '');
    $usage_limit = trim($_POST['usage_limit'] ?? '');
    $expire_date = trim($_POST['expire_date'] ?? '');
    
    // Validation
    if ($code === '') {
        $errors[] = 'Kupon kodu boş olamaz.';
    }
    if (!is_numeric($discount) || $discount <= 0 || $discount > 100) {
        $errors[] = 'İndirim oranı 0-100 arasında olmalıdır.';
    }
    if (!is_numeric($usage_limit) || $usage_limit < 1) {
        $errors[] = 'Kullanım limiti en az 1 olmalıdır.';
    }
    if (empty($expire_date)) {
        $errors[] = 'Geçerlilik tarihi seçilmelidir.';
    } elseif (strtotime($expire_date) <= time()) {
        $errors[] = 'Geçerlilik tarihi gelecekte olmalıdır.';
    }
    
    // Check if code already exists (for new coupon or editing different coupon)
    if (empty($errors)) {
        if ($editing) {
            $stmt = $db->prepare('SELECT id FROM Coupons WHERE code = ? AND id != ? LIMIT 1');
            $stmt->execute([$code, $coupon['id']]);
        } else {
            $stmt = $db->prepare('SELECT id FROM Coupons WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
        }
        if ($stmt->fetch()) {
            $errors[] = 'Bu kupon kodu zaten kullanılıyor.';
        }
    }
    
    if (empty($errors)) {
        try {
            if ($editing) {
                $stmt = $db->prepare('UPDATE Coupons SET code = ?, discount = ?, usage_limit = ?, expire_date = ? WHERE id = ?');
                $stmt->execute([$code, $discount, $usage_limit, $expire_date, $coupon['id']]);
                $_SESSION['flash_msg'] = '✅ Kupon güncellendi.';
            } else {
                $id = 'COUPON_' . uniqid();
                $stmt = $db->prepare('INSERT INTO Coupons (id, code, discount, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$id, $code, $discount, $usage_limit, $expire_date]);
                $_SESSION['flash_msg'] = '✅ Kupon oluşturuldu.';
            }
            header('Location: manage_coupons.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

// Handle deletion
if (!empty($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $db->prepare('DELETE FROM Coupons WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash_msg'] = '🗑️ Kupon silindi.';
        header('Location: manage_coupons.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = 'Silme hatası: ' . $e->getMessage();
        header('Location: manage_coupons.php');
        exit;
    }
}

// Get all coupons with usage statistics
$stmt = $db->prepare("
    SELECT c.*, 
           COUNT(uc.id) as used_count,
           (c.usage_limit - COUNT(uc.id)) as remaining_usage
    FROM Coupons c 
    LEFT JOIN User_Coupons uc ON c.id = uc.coupon_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");
$stmt->execute();
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kupon Yönetimi</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .form-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.06); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px; }
        .form-group input:focus { border-color: #dc2626; outline: none; }
        .btn { background: #dc2626; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #b91c1c; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; }
        .btn-danger:hover { background: #b91c1c; }
        .error { color: #dc2626; margin-bottom: 20px; padding: 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; }
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 6px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .coupons-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .coupons-table th, .coupons-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .coupons-table th { background: #f9fafb; font-weight: bold; }
        .coupons-table tr:hover { background: #f9fafb; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .status-active { background: #dcfce7; color: #166534; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        .status-used-up { background: #fef3c7; color: #92400e; }
        .back-link { margin-bottom: 20px; }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container" style="max-width: 1200px; margin: 20px auto; padding: 0 20px;">
    <div class="back-link">
        <a href="admin_panel.php" class="btn btn-secondary">← Admin Paneline Dön</a>
    </div>
    
    <h1>Kupon Yönetimi</h1>
    
    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <h2><?= $editing ? 'Kupon Düzenle' : 'Yeni Kupon Oluştur' ?></h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="code">Kupon Kodu:</label>
                <input type="text" id="code" name="code" value="<?= htmlspecialchars($coupon['code']) ?>" required 
                       placeholder="Örn: INDIRIM20" style="text-transform: uppercase;">
            </div>
            
            <div class="form-group">
                <label for="discount">İndirim Oranı (%):</label>
                <input type="number" id="discount" name="discount" value="<?= htmlspecialchars($coupon['discount']) ?>" 
                       required min="1" max="100" step="0.01" placeholder="Örn: 15">
            </div>
            
            <div class="form-group">
                <label for="usage_limit">Kullanım Limiti:</label>
                <input type="number" id="usage_limit" name="usage_limit" value="<?= htmlspecialchars($coupon['usage_limit']) ?>" 
                       required min="1" placeholder="Örn: 100">
            </div>
            
            <div class="form-group">
                <label for="expire_date">Son Kullanma Tarihi:</label>
                <input type="datetime-local" id="expire_date" name="expire_date" 
                       value="<?= htmlspecialchars($coupon['expire_date']) ?>" required>
            </div>
            
            <div class="actions">
                <button type="submit" class="btn"><?= $editing ? 'Güncelle' : 'Oluştur' ?></button>
                <?php if ($editing): ?>
                    <a href="manage_coupons.php" class="btn btn-secondary">İptal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <h2 style="margin-top: 40px;">Mevcut Kuponlar</h2>
    
    <?php if (empty($coupons)): ?>
        <p>Henüz kupon oluşturulmamış.</p>
    <?php else: ?>
        <table class="coupons-table">
            <thead>
                <tr>
                    <th>Kupon Kodu</th>
                    <th>İndirim</th>
                    <th>Kullanım</th>
                    <th>Son Tarih</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $c): 
                    $is_expired = strtotime($c['expire_date']) <= time();
                    $is_used_up = $c['remaining_usage'] <= 0;
                    $status_class = $is_expired ? 'status-expired' : ($is_used_up ? 'status-used-up' : 'status-active');
                    $status_text = $is_expired ? 'Süresi Dolmuş' : ($is_used_up ? 'Kullanım Tükendi' : 'Aktif');
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['code']) ?></strong></td>
                    <td><?= htmlspecialchars($c['discount']) ?>%</td>
                    <td><?= $c['used_count'] ?> / <?= $c['usage_limit'] ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($c['expire_date'])) ?></td>
                    <td><span class="status-badge <?= $status_class ?>"><?= $status_text ?></span></td>
                    <td class="actions">
                        <a href="?id=<?= $c['id'] ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 14px;">Düzenle</a>
                        <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 14px;" 
                           onclick="return confirm('Bu kuponu silmek istediğinizden emin misiniz?')">Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
<?php
session_start();
header('Content-Type: application/json');

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Lütfen giriş yapın.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek.']);
    exit;
}

$coupon_code = trim($_POST['coupon_code'] ?? '');
$total_amount = floatval($_POST['total_amount'] ?? 0);
$user_id = $_SESSION['user_id'];

if (empty($coupon_code)) {
    echo json_encode(['success' => false, 'error' => 'Kupon kodu gerekli.']);
    exit;
}

if ($total_amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz tutar.']);
    exit;
}

try {
    $pdo = new PDO('sqlite:bus_ticket_system.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Kupon bilgilerini getir
    $stmt = $pdo->prepare("
        SELECT id, code, discount_type, discount_value, min_amount, 
               usage_limit, used_count, valid_from, valid_until, is_active
        FROM Coupons 
        WHERE code = ? AND is_active = 1
    ");
    $stmt->execute([$coupon_code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        echo json_encode(['success' => false, 'error' => 'Geçersiz kupon kodu.']);
        exit;
    }

    // Kupon geçerlilik kontrolü
    $now = date('Y-m-d H:i:s');
    
    if ($coupon['valid_from'] && $now < $coupon['valid_from']) {
        echo json_encode(['success' => false, 'error' => 'Kupon henüz geçerli değil.']);
        exit;
    }

    if ($coupon['valid_until'] && $now > $coupon['valid_until']) {
        echo json_encode(['success' => false, 'error' => 'Kupon süresi dolmuş.']);
        exit;
    }

    // Kullanım limiti kontrolü
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['success' => false, 'error' => 'Kupon kullanım limiti dolmuş.']);
        exit;
    }

    // Minimum tutar kontrolü
    if ($coupon['min_amount'] > 0 && $total_amount < $coupon['min_amount']) {
        echo json_encode([
            'success' => false, 
            'error' => 'Minimum ' . number_format($coupon['min_amount'], 2) . ' ₺ tutarında alışveriş gerekli.'
        ]);
        exit;
    }

    // Kullanıcının bu kuponu daha önce kullanıp kullanmadığını kontrol et
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM User_Coupons 
        WHERE user_id = ? AND coupon_id = ?
    ");
    $stmt->execute([$user_id, $coupon['id']]);
    $user_usage_count = $stmt->fetchColumn();

    if ($user_usage_count > 0) {
        echo json_encode(['success' => false, 'error' => 'Bu kuponu daha önce kullandınız.']);
        exit;
    }

    // İndirim tutarını hesapla
    $discount_amount = 0;
    
    if ($coupon['discount_type'] === 'percentage') {
        $discount_amount = $total_amount * ($coupon['discount_value'] / 100);
    } else if ($coupon['discount_type'] === 'fixed') {
        $discount_amount = $coupon['discount_value'];
    }

    // İndirim tutarının toplam tutarı geçmemesini sağla
    $discount_amount = min($discount_amount, $total_amount);

    echo json_encode([
        'success' => true,
        'discount_amount' => $discount_amount,
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value'],
        'final_amount' => $total_amount - $discount_amount
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası oluştu.']);
}
?>
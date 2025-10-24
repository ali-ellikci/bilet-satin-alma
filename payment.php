<?php
session_start();

// --- Giriş kontrolü ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(403);
    die("Erişim Yetkiniz Yok!");
}

// --- Verileri al ---
$trip_id = $_POST['trip_id'] ?? null;
$selected_seats_str = $_POST['selected_seats'] ?? null;
$selected_seats = $selected_seats_str ? explode(',', $selected_seats_str) : [];
$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

if (!$trip_id || empty($selected_seats)) {
    $_SESSION['error_message'] = "Eksik bilgi gönderildi veya koltuk seçilmedi.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// --- DB Bağlantısı ---
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Trip bilgilerini çek ---
$stmt_trip = $db->prepare("
    SELECT t.*, c.name AS company_name 
    FROM Trips t 
    JOIN Bus_Company c ON t.company_id = c.id 
    WHERE t.id = :trip_id
");
$stmt_trip->execute([':trip_id' => $trip_id]);
$trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

if (!$trip) die("Geçersiz sefer ID'si.");

$original_price = count($selected_seats) * $trip['price'];
$total_price = $original_price;
$discount_amount = 0;
$coupon_info = null;

// --- Kupon kontrolü ---
if (!empty($coupon_code)) {
    $stmt_coupon = $db->prepare("
        SELECT c.*, COUNT(uc.id) as used_count 
        FROM Coupons c 
        LEFT JOIN User_Coupons uc ON c.id = uc.coupon_id 
        WHERE c.code = :code 
        GROUP BY c.id
    ");
    $stmt_coupon->execute([':code' => $coupon_code]);
    $coupon = $stmt_coupon->fetch(PDO::FETCH_ASSOC);
    
    if ($coupon) {
        // Kupon geçerlilik kontrolleri
        $errors = [];
        
        // Süre kontrolü
        if (strtotime($coupon['expire_date']) <= time()) {
            $errors[] = "Kupon süresi dolmuş.";
        }
        
        // Kullanım limiti kontrolü
        if ($coupon['used_count'] >= $coupon['usage_limit']) {
            $errors[] = "Kupon kullanım limiti dolmuş.";
        }
        
        // Kullanıcı daha önce bu kuponu kullanmış mı?
        $stmt_user_coupon = $db->prepare("SELECT id FROM User_Coupons WHERE coupon_id = ? AND user_id = ? LIMIT 1");
        $stmt_user_coupon->execute([$coupon['id'], $_SESSION['user_id']]);
        if ($stmt_user_coupon->fetch()) {
            $errors[] = "Bu kuponu daha önce kullandınız.";
        }
        
        if (empty($errors)) {
            if ($coupon['discount_type'] === 'percentage') {
                $discount_amount = ($original_price * $coupon['discount_value']) / 100;
            } else {
                $discount_amount = min($coupon['discount_value'], $original_price);
            }
            $total_price = $original_price - $discount_amount;
            $coupon_info = $coupon;
        } else {
            $_SESSION['error_message'] = "Kupon hatası: " . implode(", ", $errors);
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Geçersiz kupon kodu.";
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit;
    }
}

// --- Bilet ve Koltuk ekleme ---
try {
    $db->beginTransaction();

    // Ticket ekle
    $ticket_id = 'TICKET_' . uniqid();
    $stmt_ticket = $db->prepare("
        INSERT INTO Tickets (id, trip_id, user_id, total_price, status) 
        VALUES (:id, :trip_id, :user_id, :total_price, 'active')
    ");
    $stmt_ticket->execute([
        ':id' => $ticket_id,
        ':trip_id' => $trip_id,
        ':user_id' => $_SESSION['user_id'],
        ':total_price' => $total_price
    ]);

    // Booked_Seats ekle
    $stmt_seat = $db->prepare("
        INSERT INTO Booked_Seats (id, ticket_id, seat_number) 
        VALUES (:id, :ticket_id, :seat_number)
    ");
    foreach ($selected_seats as $seat) {
        $stmt_seat->execute([
            ':id' => 'SEAT_' . uniqid(),
            ':ticket_id' => $ticket_id,
            ':seat_number' => $seat
        ]);
    }

    // Kupon kullanıldıysa User_Coupons tablosuna ekle
    if ($coupon_info) {
        $stmt_user_coupon = $db->prepare("
            INSERT INTO User_Coupons (id, coupon_id, user_id) 
            VALUES (?, ?, ?)
        ");
        $stmt_user_coupon->execute(['UC_' . uniqid(), $coupon_info['id'], $_SESSION['user_id']]);
    }

    $db->commit();
} catch (PDOException $e) {
    $db->rollBack();
    die("Bilet eklenirken hata oluştu: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ödeme Başarılı</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : '' ?>">
<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container" style="max-width: 700px; margin-top: 50px; text-align: center;">
    <h1 style="color: #16a34a;">İşlem Başarılı!</h1>
    <p style="font-size: 18px;">Seçtiğiniz koltuklar başarıyla rezerve edildi.</p>

    <div style="margin-top: 30px; text-align: left; background: #f9f9f9; padding: 20px; border-radius: 12px; border: 1px solid #ddd;">
        <h2>Bilet Detayları</h2>
        <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name']) ?></p>
        <p><strong>Güzergah:</strong> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
        <p><strong>Kalkış:</strong> <?= date('d F Y, H:i', strtotime($trip['departure_time'])) ?></p>
        <p><strong>Varış:</strong> <?= date('d F Y, H:i', strtotime($trip['arrival_time'])) ?></p>
        <p><strong>Koltuklar:</strong> <?= implode(', ', $selected_seats) ?></p>
        
        <?php if ($coupon_info): ?>
            <div style="background: #dcfce7; padding: 15px; border-radius: 8px; margin: 15px 0; border: 1px solid #bbf7d0;">
                <p style="margin: 0; color: #15803d;"><strong>🎫 Kupon Uygulandı:</strong> <?= htmlspecialchars($coupon_info['code']) ?></p>
                <p style="margin: 5px 0 0 0; color: #15803d;">
                    <?= $coupon_info['discount_type'] === 'percentage' ? '%' . $coupon_info['discount_value'] . ' indirim' : $coupon_info['discount_value'] . ' ₺ indirim' ?>
                </p>
            </div>
            <p><strong>Orijinal Tutar:</strong> <?= number_format($original_price, 2) ?> ₺</p>
            <p><strong>İndirim:</strong> -<?= number_format($discount_amount, 2) ?> ₺</p>
        <?php endif; ?>
        
        <p><strong>Toplam Tutar:</strong> <?= number_format($total_price, 2) ?> ₺</p>
    </div>

    <a href="index.php" class="btn" style="margin-top: 30px; display: inline-block;">Anasayfaya Dön</a>
</div>
</body>
</html>

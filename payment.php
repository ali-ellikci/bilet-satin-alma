<?php
session_start();

// --- 1. ADIM: GÜVENLİK VE YETKİLENDİRME ---
// Kullanıcı giriş yapmış ve rolü 'user' olmalı.
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(403); // Forbidden
    die("Erişim Yetkiniz Yok! Bu işlemi yalnızca yolcu hesapları gerçekleştirebilir.");
}

// --- 2. ADIM: VERİLERİ AL VE DOĞRULA ---
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$trip_id = $_POST['trip_id'] ?? null;
$selected_seats_str = $_POST['selected_seats'] ?? null;
$selected_seats = $selected_seats_str ? explode(',', $selected_seats_str) : [];

// Gelen veriler eksikse, geldiği sayfaya hata mesajıyla geri yolla.
if (!$trip_id || empty($selected_seats)) {
    $_SESSION['error_message'] = "Eksik bilgi gönderildi veya koltuk seçilmedi.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// --- 3. ADIM: BİLET SATIN ALMA İŞLEMLERİ ---
try {
    $db->beginTransaction();

    // Gerekli güncel bilgileri çek
    $user_balance = $db->query("SELECT balance FROM User WHERE id = '{$_SESSION['user_id']}'")->fetchColumn();
    $ticket_price = $db->query("SELECT price FROM Trips WHERE id = '{$trip_id}'")->fetchColumn();

    if ($ticket_price === false) {
        throw new Exception("Geçersiz sefer ID'si.");
    }

    // Dolu koltukları kontrol et (Bu sorgu doğru, çünkü Tickets tablosu üzerinden trip_id'ye bakıyor)
    $stmt_booked = $db->prepare("SELECT seat_number FROM Booked_Seats JOIN Tickets ON Booked_Seats.ticket_id = Tickets.id WHERE Tickets.trip_id = :trip_id AND Tickets.status = 'active'");
    $stmt_booked->execute([':trip_id' => $trip_id]);
    $booked_seats = $stmt_booked->fetchAll(PDO::FETCH_COLUMN);

    // Kontrolleri yap
    foreach ($selected_seats as $seat) {
        if (in_array($seat, $booked_seats)) {
            throw new Exception("Üzgünüz, seçtiğiniz koltuklardan biri (" . $seat . " numara) siz işlemi tamamlarken başkası tarafından satın alındı.");
        }
    }

    $total_price = count($selected_seats) * $ticket_price;
    if ($user_balance < $total_price) {
        throw new Exception("Yetersiz bakiye! Mevcut bakiyeniz: " . number_format($user_balance, 2) . " ₺, gereken tutar: " . number_format($total_price, 2) . " ₺.");
    }

    // Veritabanına yazma işlemleri
    $new_balance = $user_balance - $total_price;
    $db->prepare("UPDATE User SET balance = ? WHERE id = ?")->execute([$new_balance, $_SESSION['user_id']]);

    $ticket_id = 'TICKET_' . uniqid();
    $db->prepare("INSERT INTO Tickets (id, trip_id, user_id, total_price, status) VALUES (?, ?, ?, ?, 'active')")->execute([$ticket_id, $trip_id, $_SESSION['user_id'], $total_price]);

    // --- EN ÖNEMLİ DÜZELTME BURADA ---
    // Booked_Seats tablosuna artık SADECE senin belirttiğin sütunlara veri ekliyoruz.
    $stmt_book_seat = $db->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number) VALUES (?, ?, ?)");
    foreach ($selected_seats as $seat) {
        $stmt_book_seat->execute(['SEAT_' . uniqid(), $ticket_id, $seat]);
    }

    $db->commit();

    // Başarılı sayfaya yönlendir
    header('Location: my_tickets.php?purchase=success');
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $error_message = $e->getMessage();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İşlem Başarısız!</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="text-align: center; max-width: 600px; margin-top: 50px;">
    <h1 style="color: #dc2626;">İşlem Başarısız Oldu!</h1>
    <p style="font-size: 18px;"><?= htmlspecialchars($error_message) ?></p>
    <br>
    <a href="sefer_detay.php?trip_id=<?= htmlspecialchars($trip_id) ?>" class="btn">Geri Dön ve Tekrar Dene</a>
</div>
</body>
</html>
<?php
    exit();
}
?>
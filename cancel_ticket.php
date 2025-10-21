<?php
session_start();

// --- Yetki Kontrolü: user veya company_admin ---
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['user', 'company_admin'])) {
    http_response_code(403);
    die("Erişim Yetkiniz Yok!");
}

$ticket_id = $_GET['ticket_id'] ?? null;
if (!$ticket_id) {
    die("Geçersiz bilet ID'si!");
}

// DB bağlantısı
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Biletin kullanıcıya ait ve aktif olup olmadığını kontrol et
    if ($_SESSION['user_role'] === 'user') {
        $stmt = $db->prepare("
            SELECT t.*, trips.departure_time 
            FROM Tickets t
            JOIN Trips trips ON t.trip_id = trips.id
            WHERE t.id = :ticket_id AND t.user_id = :user_id AND t.status = 'active'
        ");
        $stmt->execute([':ticket_id' => $ticket_id, ':user_id' => $_SESSION['user_id']]);
    } else { // company_admin
        $stmt = $db->prepare("
            SELECT t.*, trips.departure_time 
            FROM Tickets t
            JOIN Trips trips ON t.trip_id = trips.id
            WHERE t.id = :ticket_id AND t.status = 'active'
        ");
        $stmt->execute([':ticket_id' => $ticket_id]);
    }

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die("Bilet bulunamadı veya zaten iptal edilmiş.");
    }

    // --- Kalkış saatinden 1 saat öncesi kontrol ---
    $departure_time = strtotime($ticket['departure_time']);
    $one_hour_before = $departure_time - 3600; // 3600 saniye = 1 saat
    if (time() > $one_hour_before) {
        die("Üzgünüz, bilet kalkış saatinden 1 saat öncesine kadar iptal edilebilir.");
    }

    // --- Bilet iptal işlemleri ---
    $db->beginTransaction();
    $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = :ticket_id")
        ->execute([':ticket_id' => $ticket_id]);
    $db->prepare("DELETE FROM Booked_Seats WHERE ticket_id = :ticket_id")
        ->execute([':ticket_id' => $ticket_id]);
    $db->commit();

    header("Location: my_tickets.php?cancel=success");
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    die("Bilet iptali başarısız: " . $e->getMessage());
}
?>

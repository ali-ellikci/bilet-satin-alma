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

$total_price = count($selected_seats) * $trip['price'];

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
        <p><strong>Toplam Tutar:</strong> <?= number_format($total_price, 2) ?> ₺</p>
    </div>

    <a href="index.php" class="btn" style="margin-top: 30px; display: inline-block;">Anasayfaya Dön</a>
</div>
</body>
</html>

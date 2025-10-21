<?php
session_start();

// --- Giriş ve yetki kontrolü ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'user') {
    http_response_code(403);
    die("Erişim Yetkiniz Yok!");
}

// --- DB bağlantısı ---
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Kullanıcının biletlerini çek ---
$user_id = $_SESSION['user_id'];
$stmt_tickets = $db->prepare("
    SELECT tickets.id AS ticket_id, tickets.total_price, tickets.status,
           trips.departure_city, trips.destination_city, trips.departure_time, trips.arrival_time,
           bus_company.name AS company_name
    FROM Tickets tickets
    JOIN Trips trips ON tickets.trip_id = trips.id
    JOIN Bus_Company bus_company ON trips.company_id = bus_company.id
    WHERE tickets.user_id = :user_id
    ORDER BY trips.departure_time DESC
");
$stmt_tickets->execute([':user_id' => $user_id]);
$tickets = $stmt_tickets->fetchAll(PDO::FETCH_ASSOC);

// --- Koltuk bilgilerini her bilet için al ---
$ticket_seats = [];
foreach ($tickets as $t) {
    $stmt_seats = $db->prepare("SELECT seat_number FROM Booked_Seats WHERE ticket_id = :ticket_id");
    $stmt_seats->execute([':ticket_id' => $t['ticket_id']]);
    $ticket_seats[$t['ticket_id']] = $stmt_seats->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Benim Biletlerim</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/my_tickets.css">
</head>
<body>
<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container tickets-page">
    <h1>Benim Biletlerim</h1>

    <?php if (empty($tickets)): ?>
        <p class="no-tickets">Henüz biletiniz yok. <a href="index.php">Seferleri görüntüleyin</a>.</p>
    <?php else: ?>
        <div class="tickets-grid">
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-card <?= $ticket['status'] ?>">
                    <h2><?= htmlspecialchars($ticket['company_name']) ?></h2>
                    <p><strong>Güzergah:</strong> <?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['destination_city']) ?></p>
                    <p><strong>Kalkış:</strong> <?= date('d F Y, H:i', strtotime($ticket['departure_time'])) ?></p>
                    <p><strong>Varış:</strong> <?= date('d F Y, H:i', strtotime($ticket['arrival_time'])) ?></p>
                    <p><strong>Koltuklar:</strong> <?= implode(', ', $ticket_seats[$ticket['ticket_id']]) ?></p>
                    <p><strong>Toplam Tutar:</strong> <?= number_format($ticket['total_price'], 2) ?> ₺</p>
                <p class="status">Durum: <span><?= $ticket['status'] === 'active' ? 'Aktif' : 'İptal' ?></span></p>

                <?php
                // Kalkış saatinden 1 saat öncesi kontrol
                $departure_time = strtotime($ticket['departure_time']);
                $one_hour_before = $departure_time - 3600;
                if ($ticket['status'] === 'active' && time() <= $one_hour_before):
                ?>
                <button class="btn cancel-btn" onclick="confirmCancel('<?= $ticket['ticket_id'] ?>')">Bileti İptal Et</button>
                <?php
                // Kart içindeki bilet durumu ve iptal butonundan sonra
                if ($ticket['status'] === 'active' || $ticket['status'] === 'expired') {
                    echo '<a href="download_ticket.php?ticket_id=' . $ticket['ticket_id'] . '" class="btn" target="_blank">PDF İndir</a>';
                }
?>
        <?php endif; ?>
</div>

<script>
function confirmCancel(ticketId) {
    if (confirm("Bu bileti iptal etmek istediğinize emin misiniz?")) {
        window.location.href = "cancel_ticket.php?ticket_id=" + ticketId;
    }
}
</script>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Tema toggle (navbar.js yerine direkt sayfaya ekleyebilirsin)
const themeToggle = document.getElementById('theme-toggle');

if(localStorage.getItem('theme') === 'dark'){
    document.body.classList.add('dark');
}

themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
});
</script>
</body>
</html>

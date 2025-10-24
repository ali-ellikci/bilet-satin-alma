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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biletlerim - Bilet Satın Alma</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            text-decoration: none;
        }

        .logo i {
            margin-right: 10px;
            color: #667eea;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .user-name {
            color: #2c3e50;
            font-weight: 500;
        }

        .header-btn {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #667eea;
            color: white;
        }

        .header-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }

        /* No Tickets */
        .no-tickets-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .no-tickets-icon {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .no-tickets-title {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .no-tickets-text {
            color: #7f8c8d;
            margin-bottom: 30px;
        }

        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        /* Tickets Grid */
        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }

        .ticket-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .ticket-card.cancelled {
            border-left-color: #e74c3c;
            opacity: 0.8;
        }

        .ticket-card.active {
            border-left-color: #27ae60;
        }

        .ticket-status {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .ticket-status.active {
            background: #d5f7e8;
            color: #27ae60;
        }

        .ticket-status.cancelled {
            background: #fdeaea;
            color: #e74c3c;
        }

        .company-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .company-icon {
            background: #667eea;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .company-name {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .route-section {
            margin-bottom: 20px;
        }

        .route-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .route-cities {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .route-arrow {
            color: #667eea;
        }

        .ticket-price {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        .trip-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .detail-item {
            text-align: center;
        }

        .detail-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: 600;
        }

        .seats-section {
            margin-bottom: 20px;
        }

        .seats-title {
            color: #2c3e50;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .seats-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .seat-number {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .ticket-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
        }

        .cancel-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .download-btn {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px 10px;
            }

            .tickets-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 2rem;
            }

            .ticket-card {
                padding: 20px;
            }

            .route-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .details-grid {
                grid-template-columns: 1fr 1fr;
            }

            .ticket-actions {
                flex-direction: column;
            }

            .action-btn {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-bus"></i>
                Bilet Satın Alma
            </a>
            
            <div class="header-actions">
                <span class="user-name">
                    <i class="fas fa-user"></i> 
                    <?= htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı') ?>
                </span>
                <a href="my_account.php" class="header-btn">
                    <i class="fas fa-user-circle"></i> Hesabım
                </a>
                <a href="index.php" class="header-btn">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-ticket-alt"></i> Biletlerim
            </h1>
            <p class="page-subtitle">
                Satın aldığınız biletleri görüntüleyin ve yönetin
            </p>
        </div>

        <?php if (empty($tickets)): ?>
            <div class="no-tickets-container">
                <div class="no-tickets-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h2 class="no-tickets-title">Henüz biletiniz yok</h2>
                <p class="no-tickets-text">
                    İlk biletinizi satın almak için seferleri inceleyin
                </p>
                <a href="index.php" class="search-btn">
                    <i class="fas fa-search"></i>
                    Sefer Ara
                </a>
            </div>
        <?php else: ?>
            <div class="tickets-grid">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card <?= $ticket['status'] ?>">
                        <div class="ticket-status <?= $ticket['status'] ?>">
                            <?= $ticket['status'] === 'active' ? 'Aktif' : 'İptal' ?>
                        </div>

                        <div class="company-header">
                            <div class="company-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="company-name">
                                <?= htmlspecialchars($ticket['company_name']) ?>
                            </div>
                        </div>

                        <div class="route-section">
                            <div class="route-header">
                                <div class="route-cities">
                                    <?= htmlspecialchars($ticket['departure_city']) ?>
                                    <i class="fas fa-arrow-right route-arrow"></i>
                                    <?= htmlspecialchars($ticket['destination_city']) ?>
                                </div>
                                <div class="ticket-price">
                                    <?= number_format($ticket['total_price'], 2) ?> ₺
                                </div>
                            </div>
                        </div>

                        <div class="trip-details">
                            <div class="details-grid">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-plane-departure"></i> Kalkış
                                    </div>
                                    <div class="detail-value">
                                        <?= date('d.m.Y', strtotime($ticket['departure_time'])) ?><br>
                                        <?= date('H:i', strtotime($ticket['departure_time'])) ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-plane-arrival"></i> Varış
                                    </div>
                                    <div class="detail-value">
                                        <?= date('d.m.Y', strtotime($ticket['arrival_time'])) ?><br>
                                        <?= date('H:i', strtotime($ticket['arrival_time'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="seats-section">
                            <div class="seats-title">
                                <i class="fas fa-chair"></i> Koltuk Numaraları
                            </div>
                            <div class="seats-list">
                                <?php foreach ($ticket_seats[$ticket['ticket_id']] as $seat): ?>
                                    <span class="seat-number"><?= htmlspecialchars($seat) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="ticket-actions">
                            <?php
                            // Kalkış saatinden 1 saat öncesi kontrol
                            $departure_time = strtotime($ticket['departure_time']);
                            $one_hour_before = $departure_time - 3600;
                            if ($ticket['status'] === 'active' && time() <= $one_hour_before):
                            ?>
                                <button class="action-btn cancel-btn" onclick="confirmCancel('<?= $ticket['ticket_id'] ?>')">
                                    <i class="fas fa-times"></i> İptal Et
                                </button>
                            <?php endif; ?>

                            <?php if ($ticket['status'] === 'active' || $ticket['status'] === 'expired'): ?>
                                <a href="download_ticket.php?ticket_id=<?= $ticket['ticket_id'] ?>" 
                                   class="action-btn download-btn" target="_blank">
                                    <i class="fas fa-download"></i> PDF İndir
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function confirmCancel(ticketId) {
            if (confirm("Bu bileti iptal etmek istediğinize emin misiniz?")) {
                window.location.href = "cancel_ticket.php?ticket_id=" + ticketId;
            }
        }
    </script>
</body>
</html>

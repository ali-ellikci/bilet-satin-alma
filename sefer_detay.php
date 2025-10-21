<?php
session_start();
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) {
    die("Hata: Sefer ID'si belirtilmemiş.");
}

try {
    $stmt_trip = $db->prepare("
        SELECT t.*, c.name AS company_name 
        FROM Trips t 
        JOIN Bus_Company c ON t.company_id = c.id 
        WHERE t.id = :trip_id
    ");
    $stmt_trip->execute([':trip_id' => $trip_id]);
    $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

    if (!$trip) die("Hata: Belirtilen sefer bulunamadı.");

    $stmt_seats = $db->prepare("
        SELECT bs.seat_number 
        FROM Booked_Seats bs
        JOIN Tickets t ON bs.ticket_id = t.id
        WHERE t.trip_id = :trip_id AND t.status = 'active'
    ");
    $stmt_seats->execute([':trip_id' => $trip_id]);
    $booked_seats = $stmt_seats->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}

$total_seats = $trip['capacity'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bilet Detayları ve Koltuk Seçimi</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/sefer_detay.css">
</head>
<body class="<?= (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : '' ?>">
<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container">
    <div class="seat-selection-container">
        <div class="trip-details">
            <h1>Sefer Detayları</h1>
            <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name']) ?></p>
            <p><strong>Güzergah:</strong> <?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></p>
            <p><strong>Kalkış:</strong> <?= date('d F Y, H:i', strtotime($trip['departure_time'])) ?></p>
            <p><strong>Varış:</strong> <?= date('d F Y, H:i', strtotime($trip['arrival_time'])) ?></p>
            <p><strong>Tek Koltuk Fiyatı:</strong> <?= number_format($trip['price'], 2) ?> ₺</p>
        </div>

        <div class="seat-map-container">
            <h1>Koltuk Seçimi</h1>
            <form id="booking-form" action="payment.php" method="POST">
                <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip['id']) ?>">
                <div class="seat-map">
                    <?php
                    $seat_number = 1;
                    while ($seat_number <= $total_seats) {
                        $is_booked = in_array($seat_number, $booked_seats);
                        echo '<div class="seat ' . ($is_booked ? 'booked' : 'empty') . '" data-seat-number="' . $seat_number . '">' . $seat_number . '</div>';
                        $seat_number++;

                        echo '<div class="aisle-space"></div>';

                        for ($i = 0; $i < 2; $i++) {
                            if ($seat_number <= $total_seats) {
                                $is_booked = in_array($seat_number, $booked_seats);
                                echo '<div class="seat ' . ($is_booked ? 'booked' : 'empty') . '" data-seat-number="' . $seat_number . '">' . $seat_number . '</div>';
                                $seat_number++;
                            } else {
                                echo '<div class="aisle-space"></div>';
                            }
                        }
                    }
                    ?>
                </div>

                <div class="summary">
                    <h3>Seçilen Koltuklar: <span id="selected-seats-display">Yok</span></h3>
                    <p>Toplam Tutar: <strong id="total-price-display">0.00 ₺</strong></p>
                    <input type="hidden" name="selected_seats" id="selected-seats-input">
                    <button class="btn" type="submit" id="submit-button" disabled>Ödemeye Geç</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const themeToggle = document.getElementById('theme-toggle');
themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    document.cookie = 'theme=' + (document.body.classList.contains('dark') ? 'dark' : 'light') + '; path=/';
});

document.addEventListener('DOMContentLoaded', () => {
    const seatMap = document.querySelector('.seat-map');
    const selectedSeatsDisplay = document.getElementById('selected-seats-display');
    const totalPriceDisplay = document.getElementById('total-price-display');
    const selectedSeatsInput = document.getElementById('selected-seats-input');
    const submitButton = document.getElementById('submit-button');
    const tripPrice = <?= floatval($trip['price']) ?>;
    let selectedSeats = [];

    seatMap.addEventListener('click', (e) => {
        const seat = e.target;
        if (seat.classList.contains('seat') && !seat.classList.contains('booked')) {
            const seatNumber = parseInt(seat.dataset.seatNumber);
            if (seat.classList.contains('selected')) {
                seat.classList.remove('selected');
                seat.classList.add('empty');
                selectedSeats = selectedSeats.filter(s => s !== seatNumber);
            } else {
                seat.classList.add('selected');
                seat.classList.remove('empty');
                selectedSeats.push(seatNumber);
            }
            updateSelection();
        }
    });

    function updateSelection() {
        selectedSeats.sort((a, b) => a - b);
        if (selectedSeats.length > 0) {
            selectedSeatsDisplay.textContent = selectedSeats.join(', ');
            submitButton.disabled = false;
        } else {
            selectedSeatsDisplay.textContent = 'Yok';
            submitButton.disabled = true;
        }
        totalPriceDisplay.textContent = (selectedSeats.length * tripPrice).toFixed(2) + ' ₺';
        selectedSeatsInput.value = selectedSeats.join(',');
    }
});
</script>
</body>
</html>

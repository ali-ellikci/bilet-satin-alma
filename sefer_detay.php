<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Giriş yapmamışsa, login'e yönlendir ve geri dönebilmesi için bu adresi kaydet.
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; 
    header('Location: login.php');
    exit;
}

// 2. Giriş yapmış, peki rolü 'user' mı?
if ($_SESSION['user_role'] !== 'user') {
    // Eğer rolü 'user' DEĞİLSE (admin, company_admin vs.), ana sayfaya yönlendir.
    // İsteğe bağlı olarak bir hata mesajı da gösterebiliriz.
    $_SESSION['error_message'] = "Bu sayfaya yalnızca yolcu hesapları erişebilir.";
    header('Location: index.php');
    exit;
}




$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Sefer ID'sini al
$trip_id = $_GET['trip_id'] ?? null;
if (!$trip_id) {
    die("Hata: Sefer ID'si belirtilmemiş.");
}

try {
    // YENİLENDİ: Sorguya "capacity" sütunu eklendi.
    $stmt_trip = $db->prepare("
        SELECT t.*, c.name AS company_name 
        FROM Trips t 
        JOIN Bus_Company c ON t.company_id = c.id 
        WHERE t.id = :trip_id
    ");
    $stmt_trip->execute([':trip_id' => $trip_id]);
    $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        die("Hata: Belirtilen sefer bulunamadı.");
    }

    // Dolu koltukları çek (Bu kısım aynı, sorunsuz çalışıyor)
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

// YENİLENDİ: Toplam koltuk sayısı artık veritabanından dinamik olarak geliyor!
$total_seats = $trip['capacity'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bilet Detayları ve Koltuk Seçimi</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .seat-selection-container { display: flex; flex-wrap: wrap; gap: 40px; }
        .trip-details { flex: 1; min-width: 300px; }
        .seat-map-container { flex: 2; min-width: 340px; }
        .seat-map {
            display: grid;
            grid-template-columns: repeat(4, 1fr); 
            gap: 12px;
            max-width: 340px;
            margin: 20px auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        body.dark .seat-map { background-color: #2b0000; border-color: #444; }
        .seat, .aisle-space {
            aspect-ratio: 1 / 1; display: flex; justify-content: center; align-items: center;
            border-radius: 8px; font-weight: bold; color: white;
            transition: all 0.2s ease; user-select: none;
        }
        .aisle-space { background-color: transparent; }
        .seat { cursor: pointer; }
        .seat.empty { background-color: #4caf50; }
        .seat.empty:hover { background-color: #45a049; transform: scale(1.05); }
        .seat.booked { background-color: #dc2626; cursor: not-allowed; opacity: 0.7; }
        .seat.selected { background-color: #2196F3; transform: scale(1.1); box-shadow: 0 0 10px #2196F3; }
        .summary { text-align: center; margin-top: 20px; }
        .summary h3 { margin-bottom: 5px; }
        .summary #total-price-display { color: #dc2626; font-size: 24px; }
        body.dark .summary #total-price-display { color: #ef4444; }
    </style>
</head>
<body>







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
                    // SORUNU ÇÖZEN YENİ DÖNGÜ
                    $seat_number = 1;
                    while ($seat_number <= $total_seats) {
                        // Sol Tekli Koltuk
                        $is_booked = in_array($seat_number, $booked_seats);
                        echo '<div class="seat ' . ($is_booked ? 'booked' : 'empty') . '" data-seat-number="' . $seat_number . '">' . $seat_number . '</div>';
                        $seat_number++;

                        // Koridor Boşluğu
                        echo '<div class="aisle-space"></div>';

                        // Sağ İkili Koltuk (1)
                        if ($seat_number <= $total_seats) {
                            $is_booked = in_array($seat_number, $booked_seats);
                            echo '<div class="seat ' . ($is_booked ? 'booked' : 'empty') . '" data-seat-number="' . $seat_number . '">' . $seat_number . '</div>';
                            $seat_number++;
                        } else { echo '<div class="aisle-space"></div>'; } // Sıra dolsun diye boşluk

                        // Sağ İkili Koltuk (2)
                        if ($seat_number <= $total_seats) {
                            $is_booked = in_array($seat_number, $booked_seats);
                             echo '<div class="seat ' . ($is_booked ? 'booked' : 'empty') . '" data-seat-number="' . $seat_number . '">' . $seat_number . '</div>';
                            $seat_number++;
                        } else { echo '<div class="aisle-space"></div>'; } // Sıra dolsun diye boşluk
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
// Javascript kodunda değişiklik yok, sorunsuz çalışmaya devam edecek.
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
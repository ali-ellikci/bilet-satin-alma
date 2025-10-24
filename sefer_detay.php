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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Detayları ve Bilet Al</title>
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

        .booking-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Trip Details Card */
        .trip-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .trip-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .trip-title {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .company-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
        }

        .company-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .route-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .route-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: 1.4rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .route-arrow {
            color: #667eea;
        }

        .trip-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
            font-size: 1.1rem;
        }

        .price-highlight {
            background: #667eea;
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }

        .price-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .price-amount {
            font-size: 1.5rem;
            font-weight: bold;
        }

        /* Seat Selection Card */
        .seat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .seat-title {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .legend-seat {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid #ddd;
        }

        .legend-seat.empty {
            background: #ecf0f1;
        }

        .legend-seat.selected {
            background: #3498db;
            border-color: #2980b9;
        }

        .legend-seat.booked {
            background: #e74c3c;
            border-color: #c0392b;
        }

        .seat-map {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 30px;
            justify-items: center;
        }

        .seat {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .seat.empty {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .seat.empty:hover {
            background: #3498db;
            color: white;
            border-color: #2980b9;
            transform: scale(1.1);
        }

        .seat.selected {
            background: #3498db;
            color: white;
            border-color: #2980b9;
            transform: scale(1.1);
        }

        .seat.booked {
            background: #e74c3c;
            color: white;
            border-color: #c0392b;
            cursor: not-allowed;
        }

        .aisle-space {
            width: 10px;
        }

        /* Booking Summary */
        .booking-summary {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .summary-title {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .selected-seats {
            margin-bottom: 20px;
        }

        .selected-seats-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .selected-seats-list {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .coupon-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .coupon-label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }

        .coupon-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .coupon-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .coupon-input:focus {
            border-color: #3498db;
            outline: none;
        }

        .coupon-btn {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .coupon-btn:hover {
            background: #229954;
        }

        .coupon-message {
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .coupon-message.success {
            color: #27ae60;
        }

        .coupon-message.error {
            color: #e74c3c;
        }

        .price-breakdown {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }

        .price-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .price-line.total {
            font-weight: bold;
            font-size: 1.1rem;
            color: #2c3e50;
            border-top: 1px solid #ecf0f1;
            padding-top: 10px;
        }

        .payment-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .payment-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
        }

        .payment-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .booking-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .main-container {
                padding: 20px 10px;
            }

            .trip-details-grid {
                grid-template-columns: 1fr;
            }

            .seat-map {
                gap: 6px;
            }

            .seat {
                width: 30px;
                height: 30px;
                font-size: 0.7rem;
            }

            .coupon-input-group {
                flex-direction: column;
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
                <?php if (isset($_SESSION['user_role'])): ?>
                    <span style="color: #2c3e50;">
                        <i class="fas fa-user"></i> 
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı') ?>
                    </span>
                    <a href="my_account.php" class="header-btn">
                        <i class="fas fa-user-circle"></i> Hesabım
                    </a>
                <?php else: ?>
                    <a href="login.php" class="header-btn">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </a>
                <?php endif; ?>
                <a href="index.php" class="header-btn">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <div class="booking-container">
            <!-- Trip Details -->
            <div class="trip-card">
                <div class="trip-header">
                    <h1 class="trip-title">
                        <i class="fas fa-bus"></i> Sefer Detayları
                    </h1>
                    
                    <div class="company-info">
                        <div class="company-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="company-name"><?= htmlspecialchars($trip['company_name']) ?></div>
                    </div>
                </div>

                <div class="route-section">
                    <div class="route-header">
                        <?= htmlspecialchars($trip['departure_city']) ?>
                        <i class="fas fa-arrow-right route-arrow"></i>
                        <?= htmlspecialchars($trip['destination_city']) ?>
                    </div>
                    
                    <div class="trip-details-grid">
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-plane-departure"></i> Kalkış
                            </div>
                            <div class="detail-value">
                                <?= date('d.m.Y', strtotime($trip['departure_time'])) ?><br>
                                <?= date('H:i', strtotime($trip['departure_time'])) ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-plane-arrival"></i> Varış
                            </div>
                            <div class="detail-value">
                                <?= date('d.m.Y', strtotime($trip['arrival_time'])) ?><br>
                                <?= date('H:i', strtotime($trip['arrival_time'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="price-highlight">
                    <div class="price-label">Koltuk Başına Fiyat</div>
                    <div class="price-amount"><?= number_format($trip['price'], 2) ?> ₺</div>
                </div>
            </div>

            <!-- Seat Selection -->
            <div class="seat-card">
                <h2 class="seat-title">
                    <i class="fas fa-chair"></i> Koltuk Seçimi
                </h2>
                
                <div class="seat-legend">
                    <div class="legend-item">
                        <div class="legend-seat empty"></div>
                        <span>Boş</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat selected"></div>
                        <span>Seçili</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-seat booked"></div>
                        <span>Dolu</span>
                    </div>
                </div>

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
                    
                    <input type="hidden" name="selected_seats" id="selected-seats-input">
                    <input type="hidden" name="applied_coupon" id="applied-coupon-input">
                </form>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="booking-summary">
            <h3 class="summary-title">
                <i class="fas fa-receipt"></i> Rezervasyon Özeti
            </h3>
            
            <div class="selected-seats">
                <div class="selected-seats-label">Seçilen Koltuklar:</div>
                <div class="selected-seats-list" id="selected-seats-display">Henüz koltuk seçilmedi</div>
            </div>
            
            <div class="coupon-section">
                <label class="coupon-label" for="coupon_code">
                    <i class="fas fa-ticket-alt"></i> Kupon Kodu (İsteğe Bağlı)
                </label>
                <div class="coupon-input-group">
                    <input type="text" name="coupon_code" id="coupon_code" class="coupon-input" placeholder="Kupon kodunuzu girin">
                    <button type="button" id="apply-coupon" class="coupon-btn">
                        <i class="fas fa-check"></i> Uygula
                    </button>
                </div>
                <div id="coupon-message" class="coupon-message"></div>
            </div>
            
            <div class="price-breakdown">
                <div class="price-line">
                    <span>Ara Toplam:</span>
                    <span id="total-price-display">0.00 ₺</span>
                </div>
                <div id="discount-info" class="price-line" style="display: none;">
                    <span>İndirim:</span>
                    <span id="discount-amount" style="color: #27ae60;">-0.00 ₺</span>
                </div>
                <div class="price-line total">
                    <span>Toplam Tutar:</span>
                    <span id="discounted-total">0.00 ₺</span>
                </div>
            </div>
            
            <button class="payment-btn" type="submit" form="booking-form" id="submit-button" disabled>
                <i class="fas fa-credit-card"></i> Ödemeye Geç
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const seatMap = document.querySelector('.seat-map');
            const selectedSeatsDisplay = document.getElementById('selected-seats-display');
            const totalPriceDisplay = document.getElementById('total-price-display');
            const selectedSeatsInput = document.getElementById('selected-seats-input');
            const submitButton = document.getElementById('submit-button');
            const tripPrice = <?= floatval($trip['price']) ?>;
            let selectedSeats = [];
            let appliedCoupon = null;
            let discountAmount = 0;

            // Kupon ile ilgili elementler
            const couponCodeInput = document.getElementById('coupon_code');
            const applyCouponBtn = document.getElementById('apply-coupon');
            const couponMessage = document.getElementById('coupon-message');
            const discountInfo = document.getElementById('discount-info');
            const discountAmountDisplay = document.getElementById('discount-amount');
            const discountedTotalDisplay = document.getElementById('discounted-total');
            const appliedCouponInput = document.getElementById('applied-coupon-input');

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
                if (selectedSeats.length === 0) {
                    selectedSeatsDisplay.textContent = 'Henüz koltuk seçilmedi';
                    submitButton.disabled = true;
                } else {
                    selectedSeatsDisplay.textContent = selectedSeats.sort((a, b) => a - b).join(', ');
                    submitButton.disabled = false;
                }

                const totalPrice = selectedSeats.length * tripPrice;
                totalPriceDisplay.textContent = totalPrice.toFixed(2) + ' ₺';
                
                const finalPrice = Math.max(0, totalPrice - discountAmount);
                discountedTotalDisplay.textContent = finalPrice.toFixed(2) + ' ₺';
                
                if (discountAmount > 0) {
                    discountInfo.style.display = 'flex';
                    discountAmountDisplay.textContent = '-' + discountAmount.toFixed(2) + ' ₺';
                } else {
                    discountInfo.style.display = 'none';
                }

                selectedSeatsInput.value = selectedSeats.join(',');
            }

            applyCouponBtn.addEventListener('click', () => {
                if (appliedCoupon) {
                    removeCoupon();
                    return;
                }

                const couponCode = couponCodeInput.value.trim();
                if (!couponCode) {
                    couponMessage.textContent = 'Lütfen bir kupon kodu girin.';
                    couponMessage.className = 'coupon-message error';
                    return;
                }

                if (selectedSeats.length === 0) {
                    couponMessage.textContent = 'Önce koltuk seçmelisiniz.';
                    couponMessage.className = 'coupon-message error';
                    return;
                }

                // AJAX ile kupon doğrulama
                fetch('validate_coupon.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'coupon_code=' + encodeURIComponent(couponCode) + 
                          '&total_amount=' + encodeURIComponent(selectedSeats.length * tripPrice)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        appliedCoupon = couponCode;
                        discountAmount = data.discount_amount;
                        appliedCouponInput.value = couponCode;
                        
                        couponMessage.textContent = 'Kupon başarıyla uygulandı!';
                        couponMessage.className = 'coupon-message success';
                        
                        couponCodeInput.disabled = true;
                        applyCouponBtn.innerHTML = '<i class="fas fa-times"></i> Kaldır';
                        
                        updateSelection();
                    } else {
                        couponMessage.textContent = data.error;
                        couponMessage.className = 'coupon-message error';
                    }
                })
                .catch(error => {
                    couponMessage.textContent = 'Kupon doğrulanırken hata oluştu.';
                    couponMessage.className = 'coupon-message error';
                });
            });

            function removeCoupon() {
                appliedCoupon = null;
                discountAmount = 0;
                appliedCouponInput.value = '';
                
                couponMessage.textContent = '';
                couponMessage.className = 'coupon-message';
                
                couponCodeInput.disabled = false;
                couponCodeInput.value = '';
                applyCouponBtn.innerHTML = '<i class="fas fa-check"></i> Uygula';
                
                updateSelection();
            }
        });
    </script>
</body>
</html>
<?php
session_start();
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Türkiye'nin 81 ili
$turkey_cities = [
    'Adana','Adıyaman','Afyonkarahisar','Ağrı','Amasya','Ankara','Antalya','Artvin','Aydın','Balıkesir',
    'Bilecik','Bingöl','Bitlis','Bolu','Burdur','Bursa','Çanakkale','Çankırı','Çorum','Denizli','Diyarbakır',
    'Edirne','Elazığ','Erzincan','Erzurum','Eskişehir','Gaziantep','Giresun','Gümüşhane','Hakkari','Hatay',
    'Isparta','Mersin','İstanbul','İzmir','Kars','Kastamonu','Kayseri','Kırıkkale','Kırklareli','Kırşehir',
    'Kocaeli','Konya','Kütahyalı','Malatya','Manisa','Kahramanmaraş','Mardin','Muğla','Muş','Nevşehir','Niğde',
    'Ordu','Rize','Sakarya','Samsun','Siirt','Sinop','Sivas','Tekirdağ','Tokat','Trabzon','Tunceli','Şanlıurfa',
    'Uşak','Van','Yozgat','Zonguldak','Aksaray','Bayburt','Karaman','Batman','Şırnak','Bartın','Ardahan',
    'Iğdır','Yalova','Karabük','Kilis','Osmaniye','Düzce'
];

// Form gönderildiyse arama yap
$trips = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $origin = $_POST['origin'] ?? '';
    $destination = $_POST['destination'] ?? '';
    $date = $_POST['date'] ?? '';

    $query = "SELECT t.*, c.name AS company_name
              FROM Trips t
              JOIN Bus_Company c ON t.company_id = c.id
              WHERE t.departure_city = :origin
              AND t.destination_city = :destination
              AND date(t.departure_time) = :date
              ORDER BY t.departure_time ASC";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':origin' => $origin,
        ':destination' => $destination,
        ':date' => $date
    ]);
    $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet Satın Alma - Ana Sayfa</title>
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
        }

        .header-btn {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .login-btn {
            background: transparent;
            color: #2c3e50;
            border: 2px solid #667eea;
        }

        .login-btn:hover {
            background: #667eea;
            color: white;
        }

        .register-btn {
            background: #667eea;
            color: white;
        }

        .register-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-name {
            color: #2c3e50;
            font-weight: 500;
        }

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .hero-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .hero-title {
            color: white;
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        /* Search Form */
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-select {
            padding: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .swap-btn {
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .swap-btn:hover {
            background: #764ba2;
            transform: rotate(180deg);
        }

        .date-group {
            display: flex;
            flex-direction: column;
        }

        .date-options {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .date-option {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #2c3e50;
        }

        .date-option input {
            margin-right: 5px;
        }

        .form-date {
            padding: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-date:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 15px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        /* Trip Results */
        .trips-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .trips-title {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .trips-grid {
            display: grid;
            gap: 20px;
        }

        .trip-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
        }

        .trip-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .trip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .company-name {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .trip-price {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .trip-body {
            margin-bottom: 15px;
        }

        .trip-route {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .trip-time {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .trip-footer {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }

        .trip-link {
            background: #667eea;
            color: white;
            padding: 10px 25px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .trip-link:hover {
            background: #764ba2;
        }

        .no-trips {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.1rem;
            padding: 40px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .swap-btn {
                order: 2;
                justify-self: center;
            }

            .hero-title {
                font-size: 2rem;
            }

            .search-container {
                padding: 20px;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .date-options {
                flex-direction: column;
                gap: 5px;
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
                    <div class="user-menu">
                        <span class="user-name">
                            <i class="fas fa-user"></i> 
                            <?= htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı') ?>
                        </span>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="admin.php" class="header-btn register-btn">
                                <i class="fas fa-tachometer-alt"></i> Admin Panel
                            </a>
                        <?php elseif ($_SESSION['user_role'] === 'company_admin'): ?>
                            <a href="company_admin.php" class="header-btn register-btn">
                                <i class="fas fa-building"></i> Firma Panel
                            </a>
                        <?php else: ?>
                            <a href="my_account.php" class="header-btn register-btn">
                                <i class="fas fa-user-circle"></i> Hesabım
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="header-btn login-btn">
                            <i class="fas fa-sign-out-alt"></i> Çıkış
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="header-btn login-btn">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </a>
                    <a href="register.php" class="header-btn register-btn">
                        <i class="fas fa-user-plus"></i> Kayıt Ol
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title">
                <i class="fas fa-bus"></i> Otobüs Bileti Ara
            </h1>
            <p class="hero-subtitle">
                En uygun fiyatlarla otobüs biletinizi kolayca bulun ve satın alın
            </p>
        </div>

        <!-- Search Form -->
        <div class="search-container">
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Nereden</label>
                    <select name="origin" id="origin-select" class="form-select" required>
                        <?php foreach($turkey_cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= (isset($_POST['origin']) && $_POST['origin']==$city)?'selected':'' ?>>
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" id="swap-button" class="swap-btn" title="Yer Değiştir">
                    <i class="fas fa-exchange-alt"></i>
                </button>

                <div class="form-group">
                    <label><i class="fas fa-flag-checkered"></i> Nereye</label>
                    <select name="destination" id="destination-select" class="form-select" required>
                        <?php foreach($turkey_cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= (isset($_POST['destination']) && $_POST['destination']==$city)?'selected':'' ?>>
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group date-group">
                    <label><i class="fas fa-calendar-alt"></i> Gidiş Tarihi</label>
                    <div class="date-options">
                        <label class="date-option">
                            <input type="radio" name="date_option" value="today" checked> Bugün
                        </label>
                        <label class="date-option">
                            <input type="radio" name="date_option" value="tomorrow"> Yarın
                        </label>
                        <label class="date-option">
                            <input type="radio" name="date_option" value="custom"> Tarih Seç
                        </label>
                    </div>
                    <input type="date" name="date" id="date-input" class="form-date" required value="<?= date('Y-m-d') ?>">
                </div>

                <button class="search-btn" type="submit">
                    <i class="fas fa-search"></i> Ara
                </button>
            </form>
        </div>

        <!-- Trip Results -->
        <?php if (!empty($trips)): ?>
            <div class="trips-container">
                <h2 class="trips-title">
                    <i class="fas fa-list"></i> Bulunan Seferler (<?= count($trips) ?> adet)
                </h2>
                <div class="trips-grid">
                    <?php foreach ($trips as $trip): 
                        $is_full = false;
                        $link = $is_full ? '#' : 'sefer_detay.php?trip_id=' . htmlspecialchars($trip['id']);
                    ?>
                    <div class="trip-card">
                        <div class="trip-header">
                            <span class="company-name">
                                <i class="fas fa-building"></i>
                                <?= htmlspecialchars($trip['company_name']) ?>
                            </span>
                            <span class="trip-price"><?= htmlspecialchars($trip['price']) ?> ₺</span>
                        </div>
                        <div class="trip-body">
                            <div class="trip-route">
                                <?= htmlspecialchars($trip['departure_city']) ?>
                                <i class="fas fa-arrow-right"></i>
                                <?= htmlspecialchars($trip['destination_city']) ?>
                            </div>
                            <div class="trip-time">
                                <i class="fas fa-clock"></i>
                                Kalkış: <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?>
                            </div>
                        </div>
                        <div class="trip-footer">
                            <a href="<?= $link ?>" class="trip-link">
                                <i class="fas fa-ticket-alt"></i> Detayları Gör ve Bilet Al
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="trips-container">
                <div class="no-trips">
                    <i class="fas fa-search" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 15px;"></i>
                    <h3>Aradığınız kriterlere uygun sefer bulunamadı</h3>
                    <p>Lütfen farklı tarih veya güzergah deneyin</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Swap button functionality
        document.addEventListener('DOMContentLoaded', () => {
            const swapButton = document.getElementById('swap-button');
            const originSelect = document.getElementById('origin-select');
            const destinationSelect = document.getElementById('destination-select');

            if (swapButton) {
                swapButton.addEventListener('click', () => {
                    const tempValue = originSelect.value;
                    originSelect.value = destinationSelect.value;
                    destinationSelect.value = tempValue;
                });
            }

            // Date functionality
            const dateInput = document.getElementById('date-input');
            const todayRadio = document.querySelector('input[name="date_option"][value="today"]');
            const tomorrowRadio = document.querySelector('input[name="date_option"][value="tomorrow"]');
            const customDateRadio = document.querySelector('input[name="date_option"][value="custom"]');

            if (dateInput && todayRadio && tomorrowRadio && customDateRadio) {
                const today = new Date();
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);
                
                const formatDate = (date) => date.toISOString().split('T')[0];

                todayRadio.addEventListener('change', () => {
                    if (todayRadio.checked) {
                        dateInput.value = formatDate(today);
                    }
                });

                tomorrowRadio.addEventListener('change', () => {
                    if (tomorrowRadio.checked) {
                        dateInput.value = formatDate(tomorrow);
                    }
                });

                dateInput.addEventListener('change', () => {
                    customDateRadio.checked = true;
                });
                
                dateInput.value = formatDate(today);
            }
        });
    </script>
</body>
</html>


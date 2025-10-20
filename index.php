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
    <title>Otobüs Seferleri</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>
    <div class="nav-container">
        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="my_tickets.php" class="nav-link">Biletlerim</a>
                <a href="my_account.php" class="account-icon" title="Hesabım">&#128100;</a>
            <?php else: ?>
                <a href="login.php" class="account-icon" title="Giriş Yap">&#128100;</a>
            <?php endif; ?>
            <button id="theme-toggle" class="theme-icon" title="Tema">&#9788;</button>
        </div>
    </div>
</header>

<div class="container">
    <h1>Otobüs Seferi Ara</h1>

    <div class="search-container">
        <form method="POST" class="new-search-form">
            <div class="form-row">

                <div class="input-group">
                    <label>Nereden</label>
                    <select name="origin" id="origin-select" required>
                        <?php foreach($turkey_cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= (isset($_POST['origin']) && $_POST['origin']==$city)?'selected':'' ?>>
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" id="swap-button" class="swap-btn" title="Yer Değiştir">&rlarr;</button>

                <div class="input-group">
                    <label>Nereye</label>
                    <select name="destination" id="destination-select" required>
                        <?php foreach($turkey_cities as $city): ?>
                            <option value="<?= htmlspecialchars($city) ?>" <?= (isset($_POST['destination']) && $_POST['destination']==$city)?'selected':'' ?>>
                                <?= htmlspecialchars($city) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="input-group date-group">
                    <label>Gidiş Tarihi</label>
                    <div class="date-controls">
                        <div class="date-options">
                            <label><input type="radio" name="date_option" value="today" checked> Bugün</label>
                            <label><input type="radio" name="date_option" value="tomorrow"> Yarın</label>
                            <label><input type="radio" name="date_option" value="custom"> Tarih Seç</label> 
                        </div>
                        <input type="date" name="date" id="date-input" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

            </div>
            <button class="btn search-btn-main" type="submit">Ara</button>
        </form>
    </div>

    <?php if (!empty($trips)): ?>
        <div class="trips-grid">
            <?php foreach ($trips as $trip): 
                $is_full = false;
                $fullClass = $is_full ? 'full' : '';
                $link = $is_full ? '#' : 'sefer_detay.php?trip_id=' . htmlspecialchars($trip['id']);
            ?>
            <a href="<?= $link ?>" class="trip-card-link <?= $fullClass ?>" <?= $is_full ? 'data-tooltip="Bu sefer tamamen dolu"' : '' ?>>
                <div class="trip-card">
                    <div class="trip-header">
                        <span class="company"><?= htmlspecialchars($trip['company_name']) ?></span>
                        <span class="price"><?= htmlspecialchars($trip['price']) ?> ₺</span>
                    </div>
                    <div class="trip-body">
                        <div class="route"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></div>
                        <div class="time">Kalkış: <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?></div>
                    </div>
                    <div class="trip-footer">
                        <span>Detayları Gör ve Bilet Al</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p style="text-align:center; color:red;">Aradığınız kriterlere uygun sefer bulunamadı 😔</p>
    <?php endif; ?>
</div>

<script>
// --- TEMA DEĞİŞTİRME KODU ---
const themeToggle = document.getElementById('theme-toggle');
const body = document.body;

if(localStorage.getItem('theme') === 'dark'){
    body.classList.add('dark');
} else {
    body.classList.remove('dark');
}

themeToggle.addEventListener('click', () => {
    body.classList.toggle('dark');
    localStorage.setItem('theme', body.classList.contains('dark') ? 'dark' : 'light');
});


// --- ARAMA BARI İÇİN JAVASCRIPT KODU ---
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
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
    'Kocaeli','Konya','Kütahya','Malatya','Manisa','Kahramanmaraş','Mardin','Muğla','Muş','Nevşehir','Niğde',
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
              AND t.departure_time LIKE :date
              ORDER BY t.departure_time ASC";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':origin' => $origin,
        ':destination' => $destination,
        ':date' => "%$date%"
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
            <a href="login.php" class="account-icon" title="Hesabım">&#128100;</a>
            <button id="theme-toggle" class="theme-icon" title="Tema">&#9788;</button>
        </div>
    </div>
</header>

<div class="container">
    <h1>Otobüs Seferi Ara</h1>
    <form method="POST" class="search-form">
        <select name="origin" required>
            <option value="">Nereden</option>
            <?php foreach($turkey_cities as $city): ?>
                <option value="<?= htmlspecialchars($city) ?>" <?= (isset($_POST['origin']) && $_POST['origin']==$city)?'selected':'' ?>>
                    <?= htmlspecialchars($city) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="destination" required>
            <option value="">Nereye</option>
            <?php foreach($turkey_cities as $city): ?>
                <option value="<?= htmlspecialchars($city) ?>" <?= (isset($_POST['destination']) && $_POST['destination']==$city)?'selected':'' ?>>
                    <?= htmlspecialchars($city) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" required value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">
        <button class="btn" type="submit">Ara</button>
    </form>

    <?php if (!empty($trips)): ?>
        <div class="trips-grid">
            <?php foreach ($trips as $trip): 
                // Örnek doluluk oranı (0-100)
                $occupancy = rand(0, 100);
                $fullClass = $occupancy >= 100 ? 'full' : '';
            ?>
            <div class="trip-card">
                <div class="trip-header">
                    <span class="company"><?= htmlspecialchars($trip['company_name']) ?></span>
                    <span class="price"><?= htmlspecialchars($trip['price']) ?> ₺</span>
                </div>
                <div class="trip-body">
                    <div class="route"><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']) ?></div>
                    <div class="time">Kalkış: <?= htmlspecialchars($trip['departure_time']) ?> | Varış: <?= htmlspecialchars($trip['arrival_time']) ?></div>
                </div>
                <button class="buy <?= $fullClass ?>" <?= $fullClass?'disabled':'' ?> data-tooltip="<?= $occupancy>=100?'Sefer Dolu':'Doluluk: '.$occupancy.'%' ?>">
                    Bilet Al
                </button>
                <div class="occupancy-bar">
                    <div class="progress" style="width: <?= $occupancy ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p style="text-align:center; color:red;">Hiç sefer bulunamadı 😔</p>
    <?php endif; ?>
</div>

<script>
const themeToggle = document.getElementById('theme-toggle');
const body = document.body;

// Sayfa yüklenirken localStorage kontrolü
if(localStorage.getItem('theme') === 'dark'){
    body.classList.add('dark');
} else {
    body.classList.remove('dark');
}

// Tema değiştir
themeToggle.addEventListener('click', () => {
    body.classList.toggle('dark');
    localStorage.setItem('theme', body.classList.contains('dark') ? 'dark' : 'light');
});
</script>
</body>
</html>

<?php
session_start();

// Kullanıcı giriş yapmamışsa, doğrudan login.php'ye yönlendir.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['user_role'] == 'admin') {
    header('Location: index.php');
    exit;
}

// Veritabanı bağlantısı
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Giriş yapan kullanıcının bilgilerini veritabanından çek
try {
    $stmt = $db->prepare("SELECT full_name, email, balance FROM User WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesabım</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .account-details { 
            line-height: 2; 
            font-size: 18px;
        }
        .account-details strong { 
            min-width: 120px; 
            display: inline-block; 
            color: #555;
        }
        body.dark .account-details strong {
            color: #ccc;
        }
        .action-buttons {
            margin-top: 30px;
        }

        /* --- DEĞİŞİKLİK BURADA BAŞLIYOR --- */

        .action-buttons .btn {
            display: block;
            text-align: center;
            margin-bottom: 15px;
            
            /* İstenen Stiller */
            background-color: #dc2626; /* Sitenin ana kırmızı rengi */
            color: white;              /* Yazı rengi beyaz */
            padding: 12px 15px;        /* Buton iç boşluğu */
            border-radius: 8px;        /* Köşeleri yuvarlatma */
            font-weight: bold;         /* Yazıyı kalın yapma */
            transition: all 0.2s ease; /* Üzerine gelince animasyon */
        }

        /* Üzerine gelince biraz koyulaşsın */
        .action-buttons .btn:hover {
            background-color: #b91c1c;
            transform: scale(1.02);
        }
        


    </style>
</head>
<body>

<?php include __DIR__ . '/partials/navbar.php'; ?>

<div class="container" style="max-width: 600px; margin-top: 50px;">
    <h1>Hesap Bilgilerim</h1>
    
    <div class="account-details">
        <p><strong>Ad Soyad:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Bakiye:</strong> <?= number_format($user['balance'], 2) ?> ₺</p>
    </div>

    <hr style="margin: 30px 0;">

    <div class="action-buttons">
        <a href="my_tickets.php" class="btn">Biletlerim</a>
        
        <a href="logout.php" class="btn">Çıkış Yap</a>
    </div>
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
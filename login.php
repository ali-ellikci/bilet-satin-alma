<?php
session_start(); // Session'ı başlatarak oturum bilgilerine erişelim
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (form gönderme kodun aynı kalacak) ...
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header>
    <div class="nav-container">
        <div class="nav-right">
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="admin.php" class="nav-link" style="font-size: 16px;">Admin Paneline Dön</a>
                <a href="logout.php" class="account-icon" title="Çıkış Yap">&#10162;</a>
            <?php else: ?>
                <a href="login.php" class="account-icon" title="Hesabım">&#128100;</a>
            <?php endif; ?>
            <button id="theme-toggle" class="theme-icon">&#9788;</button>
        </div>
    </div>
</header>

<div class="container auth-page" style="max-width:400px; text-align:center;">
    </div>

</body>
</html>
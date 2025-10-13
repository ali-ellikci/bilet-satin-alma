<?php
session_start();
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM User WHERE email = :email LIMIT 1");
    $stmt->execute([':email'=>$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Email veya şifre yanlış!";
    }
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
            <button id="theme-toggle" class="theme-icon">&#9788;</button>
        </div>
    </div>
</header>

<div class="container auth-page" style="max-width:400px; text-align:center;">
    <h1>Giriş Yap</h1>
    <?php if($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST" class="auth-form">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Şifre" required>
        <button class="btn" type="submit">Giriş Yap</button>
    </form>
    <p style="margin-top:15px;">Hesabınız yok mu? <a href="register.php" style="color:#dc2626; font-weight:bold;">Kayıt Ol</a></p>
</div>

<script>
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
</script>
</body>
</html>

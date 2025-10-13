<?php
session_start();
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if($password !== $password2){
        $error = "Şifreler eşleşmiyor!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $id = uniqid();
        try {
            $stmt = $db->prepare("INSERT INTO User (id, full_name, email, role, password) VALUES (:id, :full_name, :email, 'user', :password)");
            $stmt->execute([
                ':id'=>$id,
                ':full_name'=>$full_name,
                ':email'=>$email,
                ':password'=>$hashed
            ]);
            header('Location: login.php');
            exit;
        } catch(PDOException $e){
            $error = "Bu email zaten kayıtlı!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kayıt Ol</title>
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
    <h1>Kayıt Ol</h1>
    <?php if($error): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST" class="auth-form">
        <input type="text" name="full_name" placeholder="Ad Soyad" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Şifre" required>
        <input type="password" name="password2" placeholder="Şifre (Tekrar)" required>
        <button class="btn" type="submit">Kayıt Ol</button>
    </form>
    <p style="margin-top:15px;">Zaten hesabınız var mı? <a href="login.php" style="color:#dc2626; font-weight:bold;">Giriş Yap</a></p>
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

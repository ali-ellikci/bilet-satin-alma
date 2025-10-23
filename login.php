<?php
session_start(); // Session'ı başlatarak oturum bilgilerine erişelim
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';

if (isset($_SESSION['user_role']))  {
    if ($_SESSION['user_role'] === 'admin'){
        header("Location: admin.php");
        exit;
    }
    else if($_SESSION['user_role'] === 'user' ){
        header("Location: my_account.php");
        exit;
    }
    
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Boş alan kontrolü
    if (empty($email) || empty($password)) {
        $error = "Lütfen e-posta ve şifre giriniz.";
    } else {
        // Veritabanında kullanıcıyı ara
        $stmt = $db->prepare("SELECT * FROM User WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Giriş başarılı
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role']; // 'admin' veya 'user'
            // Store company_id and username for company admins and display
            $_SESSION['company_id'] = $user['company_id'] ?? null;
            $_SESSION['username'] = $user['full_name'] ?? '';
            $_SESSION['username'] = $user['full_name'];
            $_SESSION['company_id'] = $user['company_id'];
            
            // Role göre yönlendir
            if ($user['role'] === 'admin') {
                header("Location: admin.php");
                exit;
            } else {
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "E-posta veya şifre hatalı!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<?php include __DIR__ . '/partials/navbar.php'; ?>
<div class="container auth-page login-container">
    <h2>Giriş Yap</h2>
    <form method="POST" class="auth-form">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Şifre" required>
        <button class="btn" type="submit">Giriş Yap</button>

    </form>

    <p class="signup-text">
            Hesabınız yok mu? <a href="register.php" style="color:#dc2626; font-weight:bold;">Kayıt Ol</a>
    </p>

    <?php if (!empty($error)): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>




</body>
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

</html>
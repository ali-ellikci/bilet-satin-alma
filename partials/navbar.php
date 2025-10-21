<?php
// Navbar partial: expects session variables (user_id, user_fullname, user_role) to be available
?>
<header>
    <div class="nav-container">
        <a href="index.php" class="site-logo"><img src="logos/logo.svg" alt="Bilet"></a>
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

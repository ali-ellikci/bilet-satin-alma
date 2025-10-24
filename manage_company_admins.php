<?php
session_start();

// Only super admins (role 'admin') allowed
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die("<h1>Erişim Yetkiniz Yok!</h1><p>Bu alana yalnızca yöneticiler erişebilir.</p>");
}

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Veritabanına bağlanılamadı: ' . $e->getMessage());
}

// Flash messages
$msg = '';
if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

$editing = false;
$admin_user = [
    'id' => '',
    'full_name' => '',
    'email' => '',
    'company_id' => ''
];

// Check if editing
if (!empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM User WHERE id = ? AND role = 'company_admin' LIMIT 1");
    $stmt->execute([$_GET['id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $editing = true;
        $admin_user = $u;
    } else {
        $_SESSION['flash_msg'] = '⚠️ Firma yöneticisi bulunamadı.';
        header('Location: manage_company_admins.php');
        exit;
    }
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $company_id = $_POST['company_id'] ?? '';
    
    if ($full_name === '' || $email === '' || $company_id === '') {
        $errors[] = 'Ad soyad, e-posta ve firma alanları zorunludur.';
    }
    
    if (!$editing && $password === '') {
        $errors[] = 'Yeni kullanıcı için şifre zorunludur.';
    }

    // Check email uniqueness
    if (!empty($email)) {
        $stmt = $db->prepare('SELECT id FROM User WHERE email = ? AND id != ? LIMIT 1');
        $stmt->execute([$email, $admin_user['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Bu e-posta adresi zaten kullanılıyor.';
        }
    }

    if (empty($errors)) {
        if (!empty($_POST['id'])) {
            // Update existing admin
            $id = $_POST['id'];
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE User SET full_name = ?, email = ?, password = ?, company_id = ? WHERE id = ?');
                $params = [$full_name, $email, $hashed, $company_id, $id];
            } else {
                $stmt = $db->prepare('UPDATE User SET full_name = ?, email = ?, company_id = ? WHERE id = ?');
                $params = [$full_name, $email, $company_id, $id];
            }
            
            try {
                $stmt->execute($params);
                $_SESSION['flash_msg'] = '✅ Firma yöneticisi güncellendi.';
                header('Location: manage_company_admins.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        } else {
            // Create new admin
            $id = uniqid('USR');
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('INSERT INTO User (id, full_name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?, ?)');
            try {
                $stmt->execute([$id, $full_name, $email, $hashed, 'company_admin', $company_id]);
                $_SESSION['flash_msg'] = '✅ Firma yöneticisi eklendi.';
                header('Location: manage_company_admins.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $db->prepare('DELETE FROM User WHERE id = ? AND role = ?');
    $stmt->execute([$id, 'company_admin']);
    $_SESSION['flash_msg'] = '🗑️ Firma yöneticisi silindi.';
    header('Location: manage_company_admins.php');
    exit;
}

// Fetch all company admins with company names
$admins = $db->query("
    SELECT u.*, c.name as company_name 
    FROM User u 
    LEFT JOIN Bus_Company c ON u.company_id = c.id 
    WHERE u.role = 'company_admin' 
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all companies for dropdown
$companies = $db->query('SELECT * FROM Bus_Company ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma Yöneticisi Yönetimi - Admin Panel</title>
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
            background-color: #f5f5f5;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
            background-color: #2c3e50;
        }

        .sidebar-header h2 {
            color: #ecf0f1;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background-color: #34495e;
            border-left-color: #3498db;
            padding-left: 25px;
        }

        .sidebar-menu a.active {
            background-color: #3498db;
            border-left-color: #2980b9;
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 0;
            flex: 1;
        }

        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #7f8c8d;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .content-area {
            padding: 30px;
        }

        /* Action Bar */
        .action-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message-success {
            background: #d5f7e8;
            color: #27ae60;
            border-left: 4px solid #27ae60;
        }

        .message-error {
            background: #fdeaea;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-title {
            color: #2c3e50;
            font-size: 1.3rem;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        /* Companies Table */
        .companies-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-title {
            color: #2c3e50;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-container {
            overflow-x: auto;
        }

        .companies-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .companies-table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }

        .companies-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .companies-table tr:hover {
            background: #f8f9fa;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .company-icon {
            background: #3498db;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .company-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .company-id {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .action-bar {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .table-container {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tachometer-alt"></i> Admin Panel</h2>
                <p><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="admin.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="manage_companies.php"><i class="fas fa-building"></i> Firmalar</a></li>
                <li><a href="manage_company_admins.php" class="active"><i class="fas fa-user-tie"></i> Firma Adminleri</a></li>
                <li><a href="manage_coupons.php"><i class="fas fa-ticket-alt"></i> Kuponlar</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <i class="fas fa-building"></i> Firma Yönetimi
                </div>
                <div class="breadcrumb">
                    <a href="admin.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Firmalar</span>
                </div>
            </div>

            <div class="content-area">
                <!-- Action Bar -->
                <div class="action-bar">
                    <div>
                        <h3>Firma İşlemleri</h3>
                        <p>Otobüs firmalarını yönetin</p>
                    </div>
                    <div class="action-buttons">
                        <a href="admin.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Dashboard'a Dön
                        </a>
                        <?php if (!$editing): ?>
                            <a href="manage_companies.php?new=1" class="btn btn-success">
                                <i class="fas fa-plus"></i> Yeni Firma Ekle
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (!empty($msg)): ?>
                    <div class="message message-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="message message-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form Card -->
                <?php if ($editing || isset($_GET['new'])): ?>
                    <div class="form-card">
                        <h2 class="form-title">
                            <i class="fas fa-<?= $editing ? 'edit' : 'plus' ?>"></i>
                            <?= $editing ? 'Firmayı Düzenle' : 'Yeni Firma Ekle' ?>
                        </h2>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($company['id']) ?>">
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-building"></i> Firma Adı
                                </label>
                                <input type="text" name="name" class="form-input" 
                                       value="<?= htmlspecialchars($company['name']) ?>" 
                                       placeholder="Firma adını giriniz" required>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i>
                                    <?= $editing ? 'Güncelle' : 'Firma Ekle' ?>
                                </button>
                                <a href="manage_companies.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> İptal
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Companies Table -->
                <div class="companies-card">
                    <div class="table-header">
                        <h2 class="table-title">
                            <i class="fas fa-list"></i> Kayıtlı Firmalar
                        </h2>
                        <span class="badge"><?= count($companies) ?> firma</span>
                    </div>
                    
                    <div class="table-container">
                        <table class="companies-table">
                            <thead>
                                <tr>
                                    <th>Firma Bilgileri</th>
                                    <th>Firma ID</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companies as $c): ?>
                                    <tr>
                                        <td>
                                            <div class="company-info">
                                                <div class="company-icon">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                                <div>
                                                    <div class="company-name"><?= htmlspecialchars($c['name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="company-id"><?= htmlspecialchars($c['id']) ?></span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="manage_companies.php?id=<?= urlencode($c['id']) ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit"></i> Düzenle
                                                </a>
                                                <a href="manage_companies.php?delete=<?= urlencode($c['id']) ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Bu firmayı silmek istediğinize emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
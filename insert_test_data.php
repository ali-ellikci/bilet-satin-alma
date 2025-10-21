<?php
// Bu dosya, Otobüs Arama uygulamanız için başlangıç verilerini ekler.

// UUID benzeri basit bir ID üreticisi
function generate_id($prefix = '') {
    return $prefix . uniqid();
}

try {
    // Veritabanı bağlantısı
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Veritabanı bağlantısı başarılı. Veriler ekleniyor...\n";

    // --- 1. Bus_Company (Şirket) Ekleme ---
    $companies = [
        ['id' => generate_id('CMP'), 'name' => 'Mega Turizm', 'logo_path' => '/logos/mega.png'],
        ['id' => generate_id('CMP'), 'name' => 'Süper Seyahat', 'logo_path' => '/logos/super.png'],
    ];

    $stmt_company = $db->prepare("INSERT OR IGNORE INTO Bus_Company (id, name, logo_path) VALUES (:id, :name, :logo_path)");
    foreach ($companies as $company) {
        $stmt_company->execute($company);
    }
    echo "✅ 2 Şirket eklendi (varsa atlandı).\n";

    // Şirket ID'lerini al
    $mega_turizm_id = $db->query("SELECT id FROM Bus_Company WHERE name='Mega Turizm'")->fetchColumn();
    $super_seyahat_id = $db->query("SELECT id FROM Bus_Company WHERE name='Süper Seyahat'")->fetchColumn();

    // --- 2. Trips (Sefer) Ekleme ---
    $trips = [
        [
            'id' => generate_id('TRP'), 
            'company_id' => $mega_turizm_id, 
            'departure_city' => 'İstanbul',
            'destination_city' => 'Ankara',
            'departure_time' => '2025-10-10 08:00:00', 
            'arrival_time' => '2025-10-10 14:00:00', 
            'price' => 550.00,
            'capacity' => 45 // Kapasite sütununu eklediysen bu değeri kullanabilirsin
        ],
        [
            'id' => generate_id('TRP'), 
            'company_id' => $super_seyahat_id, 
            'departure_city' => 'Ankara',
            'destination_city' => 'İzmir',
            'departure_time' => '2025-10-10 12:00:00', 
            'arrival_time' => '2025-10-10 20:00:00', 
            'price' => 620.50,
            'capacity' => 40
        ],
        [
        'id' => generate_id('TRP'), 
        'company_id' => $mega_turizm_id, 
        'departure_city' => 'İstanbul',
        'destination_city' => 'Ankara',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+35 days 08:00')), 
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+35 days 14:00')), 
        'price' => 550.00,
        'capacity' => 45
        ],
        [
        'id' => generate_id('TRP'), 
        'company_id' => $super_seyahat_id, 
        'departure_city' => 'Ankara',
        'destination_city' => 'İzmir',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+36 days 12:00')), 
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+36 days 20:00')), 
        'price' => 620.50,
        'capacity' => 40
        ],
        [
        'id' => generate_id('TRP'), 
        'company_id' => $mega_turizm_id, 
        'departure_city' => 'İzmir',
        'destination_city' => 'Antalya',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+37 days 09:00')), 
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+37 days 15:30')), 
        'price' => 700.00,
        'capacity' => 50
        ],
        [
        'id' => generate_id('TRP'), 
        'company_id' => $super_seyahat_id, 
        'departure_city' => 'Antalya',
        'destination_city' => 'İstanbul',
        'departure_time' => date('Y-m-d H:i:s', strtotime('+38 days 07:30')), 
        'arrival_time' => date('Y-m-d H:i:s', strtotime('+38 days 13:30')), 
        'price' => 750.00,
        'capacity' => 45
        ],
        // Diğer seferlerin...
    ];
    
    $stmt_trip = $db->prepare("
        INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
        VALUES (:id, :company_id, :departure_city, :destination_city, :departure_time, :arrival_time, :price, :capacity)
    ");
    
    foreach ($trips as $trip) {
        $stmt_trip->execute($trip);
    }

    echo "✅ Test Seferleri eklendi.\n";

    // --- 3. YENİ KULLANICI EKLEME BÖLÜMÜ ---

    // Parolayı güvenli bir şekilde şifrele
    $hashed_password = password_hash('test', PASSWORD_DEFAULT);

    // Eklenecek kullanıcı bilgileri
    $test_user = [
        'id' => generate_id('USR'),
        'full_name' => 'test',
        'email' => 'test@test.test',
        'role' => 'user',
        'password' => $hashed_password,
        'company_id' => null, // Normal kullanıcı olduğu için company_id boş
        'balance' => 999999999.0
    ];
    
    // Kullanıcıyı veritabanına ekle. 'INSERT OR IGNORE' sayesinde kullanıcı zaten varsa hata vermez.
    $stmt_user = $db->prepare(
        "INSERT OR IGNORE INTO User (id, full_name, email, role, password, company_id, balance) 
         VALUES (:id, :full_name, :email, :role, :password, :company_id, :balance)"
    );

    $stmt_user->execute($test_user);

    echo "✅ 'test' kullanıcısı başarıyla eklendi (varsa atlandı).\n";


    echo "\n\nBaşlangıç verileri başarıyla eklendi! Artık uygulamayı kullanabilirsiniz.\n";

} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage() . PHP_EOL;
}
?>
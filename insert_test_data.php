<?php

function generate_id($prefix = '') {
    return $prefix . uniqid();
}

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Veritabanı bağlantısı başarılı. Veriler ekleniyor...\n";
    $companies = [
        ['id' => generate_id('CMP'), 'name' => 'Mega Turizm', 'logo_path' => '/logos/mega.png'],
        ['id' => generate_id('CMP'), 'name' => 'Süper Seyahat', 'logo_path' => '/logos/super.png'],
    ];

    $stmt_company = $db->prepare("INSERT OR IGNORE INTO Bus_Company (id, name, logo_path) VALUES (:id, :name, :logo_path)");
    foreach ($companies as $company) {
        $stmt_company->execute($company);
    }
    echo "✅ 2 Şirket eklendi (varsa atlandı).\n";

    $mega_turizm_id = $db->query("SELECT id FROM Bus_Company WHERE name='Mega Turizm'")->fetchColumn();
    $super_seyahat_id = $db->query("SELECT id FROM Bus_Company WHERE name='Süper Seyahat'")->fetchColumn();
    $trips = [
        [
            'id' => generate_id('TRP'), 
            'company_id' => $mega_turizm_id, 
            'departure_city' => 'İstanbul',
            'destination_city' => 'Ankara',
            'departure_time' => '2025-10-10 08:00:00', 
            'arrival_time' => '2025-10-10 14:00:00', 
            'price' => 550.00,
            'capacity' => 45
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
    ];
    
    $stmt_trip = $db->prepare("
        INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) 
        VALUES (:id, :company_id, :departure_city, :destination_city, :departure_time, :arrival_time, :price, :capacity)
    ");
    
    foreach ($trips as $trip) {
        $stmt_trip->execute($trip);
    }

    echo "✅ Test Seferleri eklendi.\n";

    // --- 3. Normal test kullanıcısı ---
    $hashed_password = password_hash('test', PASSWORD_DEFAULT);

    $test_user = [
        'id' => generate_id('USR'),
        'full_name' => 'test',
        'email' => 'test@test.test',
        'role' => 'user',
        'password' => $hashed_password,
        'company_id' => null,
        'balance' => 999999999.0
    ];
    
    $stmt_user = $db->prepare(
        "INSERT OR IGNORE INTO User (id, full_name, email, role, password, company_id, balance) 
         VALUES (:id, :full_name, :email, :role, :password, :company_id, :balance)"
    );
    $stmt_user->execute($test_user);
    echo "✅ Seferler eklendi (varsa atlandı).\n";

    $hashed_password = password_hash('test', PASSWORD_DEFAULT);

    $test_user = [
        'id' => generate_id('USR'),
        'full_name' => 'test',
        'email' => 'test@test.test',
        'role' => 'user',
        'password' => $hashed_password,
        'company_id' => null,
        'balance' => 999999999.0
    ];
    
    $stmt_user = $db->prepare(
        "INSERT OR IGNORE INTO User (id, full_name, email, role, password, company_id, balance) 
         VALUES (:id, :full_name, :email, :role, :password, :company_id, :balance)"
    );
    $stmt_user->execute($test_user);
    echo "✅ 'test' kullanıcısı eklendi (varsa atlandı).\n";

    $hashed_firma_pass = password_hash('test_firma_admin', PASSWORD_DEFAULT);

    $test_firma_admin = [
        'id' => generate_id('USR'),
        'full_name' => 'Test Firma Admin',
        'email' => 'test_firma_admin@test.com',
        'role' => 'company_admin',
        'password' => $hashed_firma_pass,
        'company_id' => $mega_turizm_id,
        'balance' => 0.0
    ];

    $stmt_user->execute($test_firma_admin);
    echo "✅ 'test_firma_admin' kullanıcısı başarıyla eklendi.\n";

    $hashed_admin_pass = password_hash('admin', PASSWORD_DEFAULT);

    $test_admin = [
        'id' => generate_id('USR'),
        'full_name' => 'Sistem Admin',
        'email' => 'admin@test.com',
        'role' => 'admin',
        'password' => $hashed_admin_pass,
        'company_id' => null,
        'balance' => 0.0
    ];

    $stmt_user->execute($test_admin);
    echo "✅ 'admin' kullanıcısı başarıyla eklendi.\n";

    // Test kuponları ekle
    $coupons = [
        [
            'id' => generate_id('CPN'),
            'code' => 'YENI20',
            'discount_type' => 'percentage', 
            'discount_value' => 20.0,
            'min_amount' => 100.0,
            'usage_limit' => 100,
            'used_count' => 0,
            'valid_from' => '2025-01-01 00:00:00',
            'valid_until' => '2025-12-31 23:59:59',
            'is_active' => 1
        ],
        [
            'id' => generate_id('CPN'),
            'code' => 'INDIRIM50',
            'discount_type' => 'fixed',
            'discount_value' => 50.0,
            'min_amount' => 200.0,
            'usage_limit' => 50,
            'used_count' => 0,
            'valid_from' => '2025-01-01 00:00:00',
            'valid_until' => '2025-12-31 23:59:59',
            'is_active' => 1
        ],
        [
            'id' => generate_id('CPN'),
            'code' => 'WELCOME10',
            'discount_type' => 'percentage',
            'discount_value' => 10.0,
            'min_amount' => 50.0,
            'usage_limit' => 200,
            'used_count' => 0,
            'valid_from' => '2025-01-01 00:00:00',
            'valid_until' => '2025-12-31 23:59:59',
            'is_active' => 1
        ]
    ];

    $stmt_coupon = $db->prepare("INSERT OR IGNORE INTO Coupons (id, code, discount_type, discount_value, min_amount, usage_limit, used_count, valid_from, valid_until, is_active) VALUES (:id, :code, :discount_type, :discount_value, :min_amount, :usage_limit, :used_count, :valid_from, :valid_until, :is_active)");
    
    foreach ($coupons as $coupon) {
        $stmt_coupon->execute($coupon);
    }
    echo "✅ Test kuponları eklendi.\n";

    echo "\n🎉 Başlangıç verileri başarıyla eklendi! Artık uygulamayı test edebilirsin.\n";

} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage() . PHP_EOL;
}
?>

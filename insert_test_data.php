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
            'price' => 550.00
        ],
        [
            'id' => generate_id('TRP'), 
            'company_id' => $super_seyahat_id, 
            'departure_city' => 'Ankara',
            'destination_city' => 'İzmir',
            'departure_time' => '2025-10-10 12:00:00', 
            'arrival_time' => '2025-10-10 20:00:00', 
            'price' => 620.50
        ],
        [
            'id' => generate_id('TRP'), 
            'company_id' => $mega_turizm_id, 
            'departure_city' => 'Ankara',
            'destination_city' => 'İstanbul',
            'departure_time' => '2025-10-11 00:30:00', 
            'arrival_time' => '2025-10-11 06:30:00', 
            'price' => 575.00
        ],
        [
            'id' => generate_id('TRP'), 
            'company_id' => $mega_turizm_id, 
            'departure_city' => 'Ankara',
            'destination_city' => 'İstanbul',
            'departure_time' => '2025-10-10 10:00:00', 
            'arrival_time' => '2025-10-10 16:00:00', 
            'price' => 550.00
        ]
    ];
    
    $stmt_trip = $db->prepare("
        INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, created_date) 
        VALUES (:id, :company_id, :departure_city, :destination_city, :departure_time, :arrival_time, :price, CURRENT_TIMESTAMP)
    ");
    
    foreach ($trips as $trip) {
        $stmt_trip->execute([
            ':id' => $trip['id'],
            ':company_id' => $trip['company_id'],
            ':departure_city' => $trip['departure_city'],
            ':destination_city' => $trip['destination_city'],
            ':departure_time' => $trip['departure_time'],
            ':arrival_time' => $trip['arrival_time'],
            ':price' => $trip['price'],
        ]);
    }

    echo "✅ 4 Test Seferi eklendi.\n";
    echo "\n\nBaşlangıç verileri başarıyla eklendi! Artık index.php'yi çalıştırabilirsiniz.\n";

} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage() . PHP_EOL;
}
?>

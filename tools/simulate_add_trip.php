<?php
try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // get company_id for test_firma_admin
    $stmt = $db->prepare('SELECT company_id FROM User WHERE email = :email LIMIT 1');
    $stmt->execute([':email'=>'test_firma_admin@test.com']);
    $company_id = $stmt->fetchColumn();
    if (!$company_id) { echo "no company_id for user\n"; exit(1); }
    $id = uniqid('TRP');
    $departure = 'TestCityA';
    $arrival = 'TestCityB';
    $departure_time = date('Y-m-d H:i:s', strtotime('+2 days 08:00'));
    $arrival_time = date('Y-m-d H:i:s', strtotime('+2 days 12:00'));
    $price = 123.45;
    $capacity = 40;
    $stmt = $db->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $company_id, $departure, $arrival, $departure_time, $arrival_time, $price, $capacity]);
    echo "Inserted trip id=$id for company=$company_id\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

<?php
$db = new PDO('sqlite:../database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$email = 'test_firma_admin@test.com';
$stmt = $db->prepare('SELECT id, full_name, email, role, company_id FROM User WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "User not found: $email\n";
    exit(0);
}
echo "User:\n" . json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
$company_id = $user['company_id'];
if (!$company_id) {
    echo "User has no company_id assigned.\n";
    exit(0);
}
$stmt = $db->prepare('SELECT id, departure_city, destination_city, departure_time, price, company_id FROM Trips WHERE company_id = :cid');
$stmt->execute([':cid'=>$company_id]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Trips for company_id=$company_id:\n" . json_encode($trips, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

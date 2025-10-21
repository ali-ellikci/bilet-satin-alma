<?php
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$origin = 'İstanbul';
$destination = 'Ankara';
$date = '2025-10-10';
$query = "SELECT t.*, c.name AS company_name
          FROM Trips t
          JOIN Bus_Company c ON t.company_id = c.id
          WHERE t.departure_city = :origin
          AND t.destination_city = :destination
          AND date(t.departure_time) = :date
          ORDER BY t.departure_time ASC";
$stmt = $db->prepare($query);
$stmt->execute([':origin'=>$origin, ':destination'=>$destination, ':date'=>$date]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

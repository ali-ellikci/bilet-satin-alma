<?php
$db = new PDO('sqlite:database.db');
$stmt = $db->query('SELECT * FROM Coupons');
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Kupon sayısı: " . count($coupons) . PHP_EOL;
foreach($coupons as $c) {
    echo "Kod: " . $c['code'] . ", İndirim: " . $c['discount_value'] . ", Tip: " . $c['discount_type'] . PHP_EOL;
}
?>
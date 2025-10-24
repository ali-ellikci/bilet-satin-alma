<?php
echo "🔄 Kupon tablosu güncelleniyor...\n";

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mevcut kupon tablosunun yapısını kontrol et
    $stmt = $db->query("PRAGMA table_info(Coupons)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existing_columns = array_column($columns, 'name');
    
    echo "📋 Mevcut sütunlar: " . implode(', ', $existing_columns) . "\n";

    // Gerekli sütunları kontrol et ve ekle
    $required_columns = [
        'discount_type' => "ALTER TABLE Coupons ADD COLUMN discount_type TEXT CHECK(discount_type IN ('percentage', 'fixed')) DEFAULT 'percentage'",
        'discount_value' => "ALTER TABLE Coupons ADD COLUMN discount_value REAL",
        'min_amount' => "ALTER TABLE Coupons ADD COLUMN min_amount REAL DEFAULT 0",
        'used_count' => "ALTER TABLE Coupons ADD COLUMN used_count INTEGER DEFAULT 0",
        'valid_from' => "ALTER TABLE Coupons ADD COLUMN valid_from DATETIME",
        'valid_until' => "ALTER TABLE Coupons ADD COLUMN valid_until DATETIME",
        'is_active' => "ALTER TABLE Coupons ADD COLUMN is_active INTEGER DEFAULT 1"
    ];

    foreach ($required_columns as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            echo "➕ Sütun ekleniyor: $column\n";
            $db->exec($sql);
        } else {
            echo "✅ Sütun zaten mevcut: $column\n";
        }
    }

    // Eski discount sütunu varsa yeni sisteme dönüştür
    if (in_array('discount', $existing_columns) && in_array('discount_value', $existing_columns)) {
        echo "🔄 Eski discount verilerini yeni sisteme dönüştürüyor...\n";
        
        // Eski discount değerlerini yeni discount_value'ya kopyala
        $db->exec("UPDATE Coupons SET discount_value = discount WHERE discount_value IS NULL");
        
        // Eski expire_date'i valid_until'e kopyala
        if (in_array('expire_date', $existing_columns)) {
            $db->exec("UPDATE Coupons SET valid_until = expire_date WHERE valid_until IS NULL");
        }
        
        echo "✅ Veri dönüşümü tamamlandı.\n";
    }

    echo "🎉 Kupon tablosu başarıyla güncellendi!\n";

} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
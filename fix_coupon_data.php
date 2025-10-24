<?php
try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔄 Mevcut kupon verilerini güncelliyorum...\n";
    
    // Eski discount değerlerini yeni discount_value'ya kopyala
    $db->exec('UPDATE Coupons SET discount_value = discount WHERE discount_value IS NULL OR discount_value = 0');
    
    // Eski expire_date'i valid_until'e kopyala  
    $db->exec('UPDATE Coupons SET valid_until = expire_date WHERE valid_until IS NULL');
    
    // Default değerleri ayarla
    $db->exec('UPDATE Coupons SET discount_type = "percentage" WHERE discount_type IS NULL');
    $db->exec('UPDATE Coupons SET used_count = 0 WHERE used_count IS NULL');
    $db->exec('UPDATE Coupons SET is_active = 1 WHERE is_active IS NULL');
    $db->exec('UPDATE Coupons SET min_amount = 0 WHERE min_amount IS NULL');
    
    echo "✅ Mevcut kupon verileri güncellendi!\n";
    
    // Kupon sayısını kontrol et
    $stmt = $db->query('SELECT COUNT(*) FROM Coupons');
    $count = $stmt->fetchColumn();
    echo "📊 Toplam kupon sayısı: $count\n";
    
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
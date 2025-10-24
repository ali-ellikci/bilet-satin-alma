#!/bin/bash

echo "🚀 Bilet Satın Alma Sistemi Docker Kurulumu Başlatılıyor..."

# Docker'ın yüklü olup olmadığını kontrol et
if ! command -v docker &> /dev/null; then
    echo "❌ Docker yüklü değil! Lütfen Docker Desktop'ı yükleyin."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose yüklü değil! Lütfen Docker Compose'u yükleyin."
    exit 1
fi

echo "✅ Docker ve Docker Compose bulundu."

# Mevcut konteynerları durdur
echo "🛑 Mevcut konteynerları durduruyor..."
docker-compose down 2>/dev/null

# Konteynerları başlat
echo "🔄 Docker konteynerlerini başlatıyor..."
docker-compose up -d

# Konteynerların hazır olmasını bekle
echo "⏳ Konteynerların hazır olması bekleniyor..."
sleep 10

# Veritabanının var olup olmadığını kontrol et
if docker exec bilet-satinalma-web test -f database.db; then
    echo "✅ Veritabanı mevcut."
else
    echo "🗄️ Veritabanı oluşturuluyor..."
    docker exec bilet-satinalma-web php database_setup.php
    
    echo "📊 Test verileri yükleniyor..."
    docker exec bilet-satinalma-web php insert_test_data.php
fi

# İzinleri düzelt
echo "🔐 Dosya izinleri düzeltiliyor..."
docker exec bilet-satinalma-web chown -R www-data:www-data /var/www/html
docker exec bilet-satinalma-web chmod 666 /var/www/html/database.db 2>/dev/null || true

echo ""
echo "🎉 Kurulum tamamlandı!"
echo ""
echo "🌐 Erişim URL'leri:"
echo "   Ana Site: http://localhost:8080"
echo "   SQLite Yöneticisi: http://localhost:8081"
echo ""
echo "👥 Test Kullanıcıları:"
echo "   Admin: admin / admin123"
echo "   Firma Admin: test_firma_admin / password123"
echo "   Kullanıcı: test_user / password123"
echo ""
echo "🛠️ Faydalı Komutlar:"
echo "   Logları görüntüle: docker-compose logs -f"
echo "   Durdur: docker-compose down"
echo "   Yeniden başlat: docker-compose restart"
echo ""
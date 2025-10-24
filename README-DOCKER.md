# Bilet Satın Alma Sistemi - Docker Kurulumu

Bu proje Docker ile kolayca çalıştırılabilir.

## 🚀 Hızlı Başlangıç

### Gereksinimler
- Docker Desktop (Mac/Windows) veya Docker Engine (Linux)
- Docker Compose

### Kurulum ve Çalıştırma

1. **Projeyi klonlayın veya indirin**
```bash
cd bilet-satin-alma
```

2. **Docker konteynerlerini başlatın**
```bash
docker-compose up -d
```

3. **Veritabanını oluşturun ve test verilerini yükleyin**
```bash
# Konteynerin içine girin
docker exec -it bilet-satinalma-web bash

# Veritabanını oluştur
php database_setup.php

# Test verilerini yükle (isteğe bağlı)
php insert_test_data.php

# Konteynerden çık
exit
```

4. **Uygulamayı açın**
- Ana site: http://localhost:8080
- SQLite Veritabanı Yöneticisi: http://localhost:8081

## 📁 Servisler

### Web Uygulaması (Port 8080)
- PHP 8.2 + Apache
- SQLite veritabanı
- Tüm proje dosyaları

### SQLite Browser (Port 8081)
- Veritabanını görsel olarak yönetme
- Sorgu çalıştırma
- Tablo yapısını inceleme

## 🛠️ Docker Komutları

```bash
# Konteynerları başlat
docker-compose up -d

# Konteynerları durdur
docker-compose down

# Logları görüntüle
docker-compose logs -f

# Web konteynerine bağlan
docker exec -it bilet-satinalma-web bash

# Veritabanını sıfırla
docker exec -it bilet-satinalma-web php database_setup.php

# Test verilerini yeniden yükle
docker exec -it bilet-satinalma-web php insert_test_data.php
```

## 🗄️ Veritabanı Yönetimi

SQLite veritabanı dosyası: `database.db`

### Manuel Veritabanı İşlemleri
```bash
# Konteynere gir
docker exec -it bilet-satinalma-web bash

# SQLite komut satırı
sqlite3 database.db

# Tabloları listele
.tables

# Çık
.quit
```

## 🔧 Geliştirme

Kod değişiklikleri otomatik olarak yansıtılır (volume mapping sayesinde).

### Performans için öneriler:
- Geliştirme sırasında `docker-compose.override.yml` kullanın
- Production için optimized Dockerfile oluşturun

## 🚨 Sorun Giderme

### Port çakışması
Eğer 8080 veya 8081 portları kullanımda ise:
```yaml
# docker-compose.yml içinde portları değiştirin
ports:
  - "8090:80"  # 8080 yerine 8090
```

### İzin sorunları
```bash
# Dosya izinlerini düzelt
docker exec -it bilet-satinalma-web chown -R www-data:www-data /var/www/html
docker exec -it bilet-satinalma-web chmod 666 /var/www/html/database.db
```

### Veritabanı sıfırlama
```bash
# Veritabanını sil ve yeniden oluştur
docker exec -it bilet-satinalma-web rm -f database.db
docker exec -it bilet-satinalma-web php database_setup.php
docker exec -it bilet-satinalma-web php insert_test_data.php
```

## 📱 Test Kullanıcıları

Sistem başlatıldıktan sonra aşağıdaki test kullanıcıları ile giriş yapabilirsiniz:

- **Admin**: admin / admin123
- **Firma Yöneticisi**: test_firma_admin / password123
- **Normal Kullanıcı**: test_user / password123

## 🌐 Erişim URL'leri

- Ana Sayfa: http://localhost:8080
- Admin Panel: http://localhost:8080/admin.php
- Giriş: http://localhost:8080/login.php
- SQLite Yöneticisi: http://localhost:8081
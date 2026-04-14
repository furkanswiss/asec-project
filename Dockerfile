# PHP 8.2 ve Apache yüklü resmi sürümü kullan
FROM php:8.2-apache

# Veritabanı bağlantısı için gerekli sürücüleri kur (Burası çok kritik)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# URL yapısı (SEO dostu linkler) için rewrite modülünü aç
RUN a2enmod rewrite

# Çalışma dizinini belirle
WORKDIR /var/www/html

# Klasör izinlerini ayarla (Linux tabanlı çalıştığı için www-data kullanıcısına veriyoruz)
RUN chown -R www-data:www-data /var/www/html
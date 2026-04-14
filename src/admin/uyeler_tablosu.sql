-- Üyeler tablosunu oluştur
CREATE TABLE IF NOT EXISTS uyeler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefon VARCHAR(20),
    adres VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

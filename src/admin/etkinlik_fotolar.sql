CREATE TABLE etkinlik_fotolar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etkinlik_id INT NOT NULL,
    dosya_yolu VARCHAR(255) NOT NULL,
    FOREIGN KEY (etkinlik_id) REFERENCES etkinlikler(id) ON DELETE CASCADE
);

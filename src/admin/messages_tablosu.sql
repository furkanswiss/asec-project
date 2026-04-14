-- Messages (Mesajlar) Tablosunu Oluştur
-- This table allows communication between Individual and Corporate users
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    sender_type ENUM('individual', 'corporate') NOT NULL,
    receiver_id INT NOT NULL,
    receiver_type ENUM('individual', 'corporate') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message_body TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_receiver (receiver_id, receiver_type, is_read),
    INDEX idx_sender (sender_id, sender_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


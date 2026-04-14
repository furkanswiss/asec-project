-- Create database
CREATE DATABASE IF NOT EXISTS asec_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE asec_db;

-- Create blog_posts table
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(100),
    author VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample blog posts
INSERT INTO blog_posts (title, content, category, author) VALUES
('ASEC Kulübü''nün İlk Projesi', 'Kulübümüz ilk projesini başarıyla tamamladı. Bu projede...', 'Projeler', 'Admin'),
('Yeni Dönem Etkinliklerimiz', 'Bu dönem yapacağımız etkinlikler ve çalışmalar...', 'Etkinlikler', 'Admin'),
('Teknoloji Günleri Etkinliği', 'Teknoloji günleri kapsamında düzenlediğimiz etkinlikte...', 'Etkinlikler', 'Admin');

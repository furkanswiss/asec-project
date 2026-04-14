-- Multi-Language Support Migration for Blog, Events, Announcements, and Important Information
-- This script adds English columns to multiple tables

-- Add English columns to blog_posts table
ALTER TABLE blog_posts 
ADD COLUMN IF NOT EXISTS title_en VARCHAR(255) NULL AFTER title,
ADD COLUMN IF NOT EXISTS content_en LONGTEXT NULL AFTER content;

-- Add English columns to etkinlikler table
ALTER TABLE etkinlikler 
ADD COLUMN IF NOT EXISTS baslik_en VARCHAR(255) NULL AFTER baslik,
ADD COLUMN IF NOT EXISTS aciklama_en LONGTEXT NULL AFTER aciklama;

-- Add English columns to duyurular table
ALTER TABLE duyurular 
ADD COLUMN IF NOT EXISTS baslik_en VARCHAR(255) NULL AFTER baslik,
ADD COLUMN IF NOT EXISTS icerik_en LONGTEXT NULL AFTER icerik;

-- Add English columns to onemli_bilgiler table
ALTER TABLE onemli_bilgiler 
ADD COLUMN IF NOT EXISTS baslik_en VARCHAR(255) NULL AFTER baslik,
ADD COLUMN IF NOT EXISTS aciklama_en TEXT NULL AFTER aciklama,
ADD COLUMN IF NOT EXISTS icerik_en LONGTEXT NULL AFTER icerik;

-- Note: If your MySQL version doesn't support IF NOT EXISTS in ALTER TABLE,
-- you may need to run these separately and handle errors manually.


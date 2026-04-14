<?php
require_once 'db.php';
ob_start(); // Docker'da hata almamak için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu başlat
}

?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('gallery.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/galeri.css">
    <style>
        .folder-header {
            background: linear-gradient(135deg, #1b1f3b, #1b1f3b, #a298b7, #9370db);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
            color: #ffffff;
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .folder-header h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .folder-header .folder-description {
            font-size: 1.1rem;
            margin: 1rem 0;
            line-height: 1.6;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            opacity: 0.95;
        }
        .folder-header .folder-date {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 1rem;
        }
        .folder-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }
        .folder-photo-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            cursor: pointer;
            aspect-ratio: 1;
        }
        .folder-photo-item:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        .folder-photo-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.5s ease;
        }
        .folder-photo-item:hover img {
            transform: scale(1.1);
        }
        .back-to-gallery {
            display: inline-block;
            margin: 2rem;
            padding: 0.8rem 1.5rem;
            background: linear-gradient(135deg, #9370db, #6A0DAD);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .back-to-gallery:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(147, 112, 219, 0.3);
            color: #fff;
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1001;
        }
        .lightbox-nav:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }
        .lightbox-nav.prev {
            left: 20px;
        }
        .lightbox-nav.next {
            right: 20px;
        }
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        .lightbox-counter {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            color: #fff;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <?php
        require_once 'includes/lang.php';
        $folder_id = intval($_GET['id'] ?? 0);
        
        if($folder_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM gallery_folders WHERE id = ?");
            $stmt->execute([$folder_id]);
            $folder = $stmt->fetch();
            
            if($folder) {
                $photos = $pdo->prepare("SELECT * FROM galeri WHERE folder_id = ? ORDER BY id ASC");
                $photos->execute([$folder_id]);
                $photos = $photos->fetchAll();
                ?>
                <section class="folder-header">
                    <div class="container">
                        <h2><?= htmlspecialchars($folder['baslik']) ?></h2>
                        <?php if(!empty($folder['aciklama'])): ?>
                            <p class="folder-description"><?= nl2br(htmlspecialchars($folder['aciklama'])) ?></p>
                        <?php endif; ?>
                        <p class="folder-date">
                            <i class="fas fa-calendar"></i> <?= date('d F Y', strtotime($folder['olusturma_tarihi'])) ?>
                        </p>
                    </div>
                </section>
                
                <a href="galeri.php" class="back-to-gallery">
                    <i class="fas fa-arrow-left"></i> <?php echo $langCode === 'en' ? 'Back to Gallery' : 'Galeriye Dön'; ?>
                </a>
                
                <section class="gallery-grid">
                    <div class="container">
                        <div class="folder-photos-grid">
                            <?php foreach($photos as $index => $photo): ?>
                                <div class="folder-photo-item" data-photo-index="<?= $index ?>" data-photo-src="<?= htmlspecialchars($photo['dosya_yolu']) ?>">
                                    <img src="<?= htmlspecialchars($photo['dosya_yolu']) ?>" 
                                         alt="<?= htmlspecialchars($photo['baslik']) ?>"
                                         loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php
            } else {
                echo '<div class="container" style="padding: 4rem 2rem; text-align: center;"><h2>Klasör bulunamadı!</h2><a href="galeri.php">Galeriye Dön</a></div>';
            }
        } else {
            echo '<div class="container" style="padding: 4rem 2rem; text-align: center;"><h2>Geçersiz klasör ID!</h2><a href="galeri.php">Galeriye Dön</a></div>';
        }
        ?>
    </main>
    <?php include 'footer.php'; ?>
    
    <!-- Enhanced Lightbox with Navigation -->
    <div class="lightbox" id="lightbox">
        <div class="lightbox-content">
            <button class="lightbox-nav prev" id="lightboxPrev">
                <i class="fas fa-chevron-left"></i>
            </button>
            <img src="" alt="" id="lightboxImg">
            <button class="lightbox-nav next" id="lightboxNext">
                <i class="fas fa-chevron-right"></i>
            </button>
            <span class="lightbox-close"><i class="fas fa-times"></i></span>
            <div class="lightbox-counter" id="lightboxCounter"></div>
        </div>
    </div>
    
    <script src="javascript/script.js"></script>
    <script src="javascript/image-optimizer.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const photoItems = document.querySelectorAll('.folder-photo-item');
            const lightbox = document.getElementById('lightbox');
            const lightboxImg = document.getElementById('lightboxImg');
            const lightboxClose = document.querySelector('.lightbox-close');
            const lightboxPrev = document.getElementById('lightboxPrev');
            const lightboxNext = document.getElementById('lightboxNext');
            const lightboxCounter = document.getElementById('lightboxCounter');
            
            let currentIndex = 0;
            const photos = Array.from(photoItems).map(item => ({
                src: item.dataset.photoSrc,
                index: parseInt(item.dataset.photoIndex)
            }));
            
            function openLightbox(index) {
                currentIndex = index;
                lightboxImg.src = photos[index].src;
                lightbox.classList.add('active');
                updateCounter();
            }
            
            function updateCounter() {
                lightboxCounter.textContent = (currentIndex + 1) + ' / ' + photos.length;
            }
            
            function nextPhoto() {
                currentIndex = (currentIndex + 1) % photos.length;
                lightboxImg.src = photos[currentIndex].src;
                updateCounter();
            }
            
            function prevPhoto() {
                currentIndex = (currentIndex - 1 + photos.length) % photos.length;
                lightboxImg.src = photos[currentIndex].src;
                updateCounter();
            }
            
            photoItems.forEach((item, index) => {
                item.addEventListener('click', () => openLightbox(index));
            });
            
            lightboxNext.addEventListener('click', (e) => {
                e.stopPropagation();
                nextPhoto();
            });
            
            lightboxPrev.addEventListener('click', (e) => {
                e.stopPropagation();
                prevPhoto();
            });
            
            lightboxClose.addEventListener('click', () => {
                lightbox.classList.remove('active');
            });
            
            lightbox.addEventListener('click', function(e) {
                if (e.target === this) {
                    lightbox.classList.remove('active');
                }
            });
            
            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (!lightbox.classList.contains('active')) return;
                
                if (e.key === 'ArrowRight') {
                    nextPhoto();
                } else if (e.key === 'ArrowLeft') {
                    prevPhoto();
                } else if (e.key === 'Escape') {
                    lightbox.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>


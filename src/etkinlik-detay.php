<?php
require_once 'db.php';
ob_start(); // Docker'da hata almamak için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu başlat
}
// Etkinlik Detay Sayfası

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM etkinlikler WHERE id=?');
$stmt->execute([$id]);
$etkinlik = $stmt->fetch();
if (!$etkinlik) die('Etkinlik bulunamadı!');

// Determine language (use cookie from lang.php, fallback to 'tr')
require_once 'includes/lang.php';
$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');

// Select title and description based on language
if ($currentLang == 'en' && !empty($etkinlik['baslik_en']) && !empty($etkinlik['aciklama_en'])) {
    $display_baslik = $etkinlik['baslik_en'];
    $display_aciklama = $etkinlik['aciklama_en'];
} else {
    // Default to Turkish
    $display_baslik = $etkinlik['baslik'];
    $display_aciklama = $etkinlik['aciklama'];
}

$fotolar = $pdo->prepare('SELECT * FROM etkinlik_fotolar WHERE etkinlik_id=?');
$fotolar->execute([$id]);
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?= htmlspecialchars($display_baslik) ?> - ASEC</title>
    <link rel="stylesheet" href="css/etkinlik-detay.css">
    <style>
        /* Lightbox Modal Styles */
        #imageViewerModal {
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        
        #imageViewerModal.active {
            display: flex;
        }
        
        #imageViewerModal .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        #imageViewerModal .modal-content img {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }
        
        #imageViewerModal .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
            line-height: 1;
            transition: opacity 0.3s;
            background: rgba(0, 0, 0, 0.5);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        #imageViewerModal .modal-close:hover {
            opacity: 0.8;
            background: rgba(0, 0, 0, 0.7);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
    <div class="event-detail-container">
        <div class="event-detail-card">
            <div class="event-header">
                <h1><?= htmlspecialchars($display_baslik) ?></h1>
                <?php 
                $bugun = date('Y-m-d');
                if($etkinlik['tarih'] >= $bugun): 
                ?>
                <span class="event-status upcoming"><?php echo __t('event.status.upcoming'); ?></span>
                <?php else: ?>
                <span class="event-status past"><?php echo __t('event.status.past'); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="event-content">
                <div class="event-info-grid">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="info-text">
                            <div class="info-label"><?php echo __t('event.info.date'); ?></div>
                            <div class="info-value"><?= htmlspecialchars($etkinlik['tarih']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-text">
                            <div class="info-label"><?php echo __t('event.info.time'); ?></div>
                            <div class="info-value"><?= htmlspecialchars($etkinlik['saat']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-text">
                            <div class="info-label"><?php echo __t('event.info.place'); ?></div>
                            <div class="info-value"><?= htmlspecialchars($etkinlik['yer']) ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="event-description">
                    <h2><?php echo __t('event.description'); ?></h2>
                    <p><?= nl2br(htmlspecialchars($display_aciklama)) ?></p>
                </div>
                
                <?php if (!empty($etkinlik['kayit_link'])): ?>
                    <a href="<?= htmlspecialchars($etkinlik['kayit_link']) ?>" target="_blank" class="register-btn">
                        <i class="fas fa-user-plus"></i> <?php echo __t('event.register'); ?>
                    </a>
                <?php endif; ?>
                
                <div class="gallery-section">
                    <h2><?php echo __t('event.gallery'); ?></h2>
                    <div class="gallery-grid">
                        <?php if($fotolar->rowCount() > 0): ?>
                            <?php foreach($fotolar as $foto): ?>
                                <div class="gallery-item">
                                    <img src="<?= htmlspecialchars($foto['dosya_yolu']) ?>" 
                                         alt="Etkinlik Fotoğrafı">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-photos">
                                <i class="fas fa-images"></i>
                                <p><?php echo __t('event.no_photos'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <a href="etkinlikler.php" class="back-to-events">
                    <i class="fas fa-arrow-left"></i> <?php echo __t('event.back'); ?>
                </a>
            </div>
        </div>
    </div>
    </main>
    
    <?php include 'footer.php'; ?>
    
    <!-- Lightbox Modal -->
    <div id="imageViewerModal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <img src="" alt="Full Screen Image">
        </div>
    </div>
    
    <script src="javascript/script.js"></script>
    <script src="javascript/image-optimizer.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all gallery images
            const galleryImages = document.querySelectorAll('.gallery-grid .gallery-item img');
            const modal = document.getElementById('imageViewerModal');
            const modalImg = modal.querySelector('img');
            const modalClose = modal.querySelector('.modal-close');
            
            // Add click event to each gallery image
            galleryImages.forEach(function(img) {
                img.style.cursor = 'pointer';
                img.addEventListener('click', function() {
                    // Get the source of the clicked image
                    const imgSrc = this.getAttribute('src');
                    // Set modal image source
                    modalImg.setAttribute('src', imgSrc);
                    // Show the modal
                    modal.classList.add('active');
                });
            });
            
            // Close modal when clicking the X button
            modalClose.addEventListener('click', function(e) {
                e.stopPropagation();
                modal.classList.remove('active');
            });
            
            // Close modal when clicking outside the image
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>

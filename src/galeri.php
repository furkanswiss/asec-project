<?php
require_once 'db.php'; // Veritabanı bağlantısı
ob_start(); // Docker için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu yakalamak için şart
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('gallery.title'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/galeri.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <section class="gallery-header">
            <div class="container">
                <h2><?php echo __t('gallery.title'); ?></h2>
                <p><?php echo __t('gallery.subtitle'); ?></p>
            </div>
        </section>
        <section class="gallery-filters">
            <div class="container">
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all"><?php echo __t('gallery.filter.all'); ?></button>
                    <button class="filter-btn" data-filter="events"><?php echo __t('gallery.filter.events'); ?></button>
                    <button class="filter-btn" data-filter="workshops"><?php echo __t('gallery.filter.workshops'); ?></button>
                    <button class="filter-btn" data-filter="teams"><?php echo __t('gallery.filter.teams'); ?></button>
                </div>
            </div>
        </section>
        <section class="gallery-grid">
            <div class="container">
                <div class="gallery-items">
                    <?php
                    require_once 'db.php';
                    require_once 'includes/lang.php';
                    
                    // Get folders with photo counts and cover images
                    $folders = $pdo->query("
                        SELECT f.*, 
                               COUNT(g.id) as photo_count,
                               (SELECT dosya_yolu FROM galeri WHERE folder_id = f.id ORDER BY id ASC LIMIT 1) as cover_image
                        FROM gallery_folders f
                        LEFT JOIN galeri g ON g.folder_id = f.id
                        GROUP BY f.id
                        HAVING photo_count > 0
                        ORDER BY f.olusturma_tarihi DESC
                    ")->fetchAll();
                    
                    foreach($folders as $folder): 
                        $category = $folder['kategori'];
                    ?>
                        <a href="galeri-klasor.php?id=<?= $folder['id'] ?>" class="gallery-item" data-category="<?= htmlspecialchars($category) ?>" data-folder-id="<?= $folder['id'] ?>">
                            <?php if($folder['cover_image']): ?>
                                <img src="<?= htmlspecialchars($folder['cover_image']) ?>" 
                                     alt="<?= htmlspecialchars($folder['baslik']) ?>">
                            <?php else: ?>
                                <div class="folder-placeholder-img">
                                    <i class="fas fa-folder"></i>
                                </div>
                            <?php endif; ?>
                            <div class="gallery-item-info">
                                <h3><?= htmlspecialchars($folder['baslik']) ?></h3>
                                <p>
                                    <i class="fas fa-images"></i> <?= $folder['photo_count'] ?> <?php echo $langCode === 'en' ? 'Photos' : 'Fotoğraf'; ?>
                                    <br>
                                    <i class="fas fa-calendar"></i> <?= date('d.m.Y', strtotime($folder['olusturma_tarihi'])) ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    
                    <?php
                    // Also show standalone photos (without folder)
                    $standalonePhotos = $pdo->query("SELECT * FROM galeri WHERE folder_id IS NULL ORDER BY tarih DESC")->fetchAll();
                    foreach($standalonePhotos as $item): 
                    ?>
                        <div class="gallery-item" data-category="<?= htmlspecialchars($item['kategori']) ?>">
                            <img src="<?= htmlspecialchars($item['dosya_yolu']) ?>" 
                                 alt="<?= htmlspecialchars($item['baslik']) ?>">
                            <div class="gallery-item-info">
                                <h3><?= htmlspecialchars($item['baslik']) ?></h3>
                                <p><?= date('F Y', strtotime($item['tarih'])) ?></p>
                                <a href="<?= htmlspecialchars($item['dosya_yolu']) ?>" class="lightbox-trigger">
                                    <i class="fas fa-expand"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    
    <!-- Lightbox -->
    <div class="lightbox">
        <div class="lightbox-content">
            <img src="" alt="">
            <span class="lightbox-close"><i class="fas fa-times"></i></span>
        </div>
    </div>
    
    <script src="javascript/script.js"></script>
    <script src="javascript/image-optimizer.js"></script>
    <script>
        // Gallery filtering
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const galleryItems = document.querySelectorAll('.gallery-item');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    galleryItems.forEach(item => {
                        if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
            
            // Lightbox for standalone photos
            const lightbox = document.querySelector('.lightbox');
            const lightboxImg = lightbox.querySelector('img');
            const lightboxClose = lightbox.querySelector('.lightbox-close');
            const lightboxTriggers = document.querySelectorAll('.lightbox-trigger');
            
            lightboxTriggers.forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    const imgSrc = this.getAttribute('href');
                    lightbox.classList.add('active');
                    lightboxImg.src = imgSrc;
                });
            });
            
            lightboxClose.addEventListener('click', function() {
                lightbox.classList.remove('active');
            });
            
            lightbox.addEventListener('click', function(e) {
                if (e.target === this) {
                    lightbox.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>

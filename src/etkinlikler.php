<?php
require_once 'db.php'; // Veritabanını dahil et
ob_start(); // Docker'da hata almamak için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu başlat
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('nav.events'); ?> - ASEC</title>
    <link rel="stylesheet" href="css/etkinlikler.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main>
        <div class="events-container">
            <h2 class="page-title"><?php echo __t('events.page.title'); ?></h2>
            <?php
            require_once 'db.php';
            require_once 'includes/lang.php';
            
            // Access language arrays globally
            global $translations, $langCode;
            
            // Determine language (use cookie from lang.php, fallback to 'tr')
            $currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');
            
            $today = date('Y-m-d');
            
            // Fetch all events
            $stmt = $pdo->prepare("SELECT * FROM etkinlikler");
            $stmt->execute();
            $all_etkinlikler = $stmt->fetchAll();
            
            // Split events into upcoming and past
            $upcoming_events = [];
            $past_events = [];
            
            foreach ($all_etkinlikler as $etkinlik) {
                if ($etkinlik['tarih'] >= $today) {
                    $upcoming_events[] = $etkinlik;
                } else {
                    $past_events[] = $etkinlik;
                }
            }
            
            // Sort upcoming events: ASCENDING (nearest date first)
            usort($upcoming_events, function($a, $b) {
                // First compare by date
                $dateCompare = strcmp($a['tarih'], $b['tarih']);
                if ($dateCompare !== 0) {
                    return $dateCompare; // ASC: $a < $b means return -1 (a comes first)
                }
                // If dates are equal, compare by time
                return strcmp($a['saat'] ?? '', $b['saat'] ?? '');
            });
            
            // Sort past events: DESCENDING (most recent past first)
            usort($past_events, function($a, $b) {
                // First compare by date (reversed for DESC)
                $dateCompare = strcmp($b['tarih'], $a['tarih']);
                if ($dateCompare !== 0) {
                    return $dateCompare; // DESC: $b < $a means return -1 (b comes first)
                }
                // If dates are equal, compare by time (reversed for DESC)
                return strcmp($b['saat'] ?? '', $a['saat'] ?? '');
            });
            ?>
            <!-- Yaklaşan Etkinlikler -->
            <section class="events-section">
                <h3 class="section-title"><?php echo __t('events.upcoming.title'); ?></h3>
                <div class="events-grid">
                <?php foreach ($upcoming_events as $etkinlik):
                    // Select title and description based on language
                    if ($currentLang == 'en' && !empty($etkinlik['baslik_en']) && !empty($etkinlik['aciklama_en'])) {
                        $display_baslik = $etkinlik['baslik_en'];
                        $display_aciklama = $etkinlik['aciklama_en'];
                    } else {
                        // Default to Turkish
                        $display_baslik = $etkinlik['baslik'];
                        $display_aciklama = $etkinlik['aciklama'];
                    }
                ?>
                    <div class="event-card upcoming">
                        <div class="event-date">
                            <?php 
                            $t = strtotime($etkinlik['tarih']); 
                            $monthNum = (int)date('n', $t);
                            $monthLong = isset($translations[$langCode]['months_long'][$monthNum]) 
                                ? $translations[$langCode]['months_long'][$monthNum] 
                                : date('F', $t);
                            $year = date('Y', $t);
                            ?>
                            <span class="day"><?= date('d', $t) ?></span>
                            <span class="month"><?= $monthLong ?></span>
                            <span class="year" style="font-size: 0.8em; display: block;"><?= $year ?></span>
                        </div>
                        <div class="event-details">
                            <h4><?= htmlspecialchars($display_baslik) ?></h4>
                            <p class="event-description"><?= htmlspecialchars(mb_substr($display_aciklama, 0, 120)) . (mb_strlen($display_aciklama) > 120 ? '...' : '') ?></p>
                            <div class="event-info">
                                <span><i class="fas fa-clock"></i> <?= htmlspecialchars($etkinlik['saat']) ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($etkinlik['yer']) ?></span>
                            </div>
                            <div style="margin-top: 8px; display: flex; gap: 8px;">
                                <a href="etkinlik-detay.php?id=<?= $etkinlik['id'] ?>" class="btn btn-sm btn-info"><?php echo __t('events.detail'); ?></a>
<a href="#" class="btn btn-sm btn-info detay-modal-btn" data-id="<?= $etkinlik['id'] ?>" ><?php echo __t('events.quick_detail'); ?></a>
                                <?php if (!empty($etkinlik['kayit_link'])): ?>
                                    <a href="<?= htmlspecialchars($etkinlik['kayit_link']) ?>" class="attend-btn" target="_blank"><?php echo __t('events.register'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </section>
            <!-- Geçmiş Etkinlikler -->
            <section class="events-section past-events">
                <h3 class="section-title"><?php echo __t('events.past.title'); ?></h3>
                <div class="events-grid">
                <?php foreach ($past_events as $etkinlik):
                    // Select title and description based on language
                    if ($currentLang == 'en' && !empty($etkinlik['baslik_en']) && !empty($etkinlik['aciklama_en'])) {
                        $display_baslik = $etkinlik['baslik_en'];
                        $display_aciklama = $etkinlik['aciklama_en'];
                    } else {
                        // Default to Turkish
                        $display_baslik = $etkinlik['baslik'];
                        $display_aciklama = $etkinlik['aciklama'];
                    }
                ?>
                    <div class="event-card past">
                        <div class="event-date">
                            <?php 
                            $t = strtotime($etkinlik['tarih']); 
                            $monthNum = (int)date('n', $t);
                            $monthLong = isset($translations[$langCode]['months_long'][$monthNum]) 
                                ? $translations[$langCode]['months_long'][$monthNum] 
                                : date('F', $t);
                            $year = date('Y', $t);
                            ?>
                            <span class="day"><?= date('d', $t) ?></span>
                            <span class="month"><?= $monthLong ?></span>
                            <span class="year" style="font-size: 0.8em; display: block;"><?= $year ?></span>
                        </div>
                        <div class="event-details">
                            <h4><?= htmlspecialchars($display_baslik) ?></h4>
                            <p class="event-description"><?= htmlspecialchars(mb_substr($display_aciklama, 0, 120)) . (mb_strlen($display_aciklama) > 120 ? '...' : '') ?></p>
                            <div class="event-info">
                                <span><i class="fas fa-clock"></i> <?= htmlspecialchars($etkinlik['saat']) ?></span>
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($etkinlik['yer']) ?></span>
                            </div>
                            <div style="margin-top: 8px; display: flex; gap: 8px;">
                                <a href="etkinlik-detay.php?id=<?= $etkinlik['id'] ?>" class="btn btn-sm btn-info"><?php echo __t('events.detail'); ?></a>
<a href="#" class="btn btn-sm btn-info detay-modal-btn" data-id="<?= $etkinlik['id'] ?>"><?php echo __t('events.quick_detail'); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    
    <!-- Etkinlik Detay Modal -->
    <div id="etkinlikDetayModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="modalContent" class="modal-body">
                <!-- AJAX ile yüklenecek içerik -->
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> <?php echo __t('events.loading'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="javascript/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal elementlerini seç
        const modal = document.getElementById('etkinlikDetayModal');
        const modalContent = document.getElementById('modalContent');
        const closeBtn = document.getElementsByClassName('close')[0];
        
        // Tüm hızlı detay butonlarını seç
        const detayBtns = document.querySelectorAll('.detay-modal-btn');
        
        // Her butona tıklama olayı ekle
        detayBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const etkinlikId = this.getAttribute('data-id');
                
                // Modal'ı göster
                modal.style.display = 'block';
                        modalContent.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> ' + <?php echo json_encode(__t('events.loading')); ?> + '</div>';
                
                // Get current language from cookie or default to 'tr'
                const currentLang = document.cookie.match(/lang=([^;]+)/) ? document.cookie.match(/lang=([^;]+)/)[1] : 'tr';
                
                // AJAX ile etkinlik detaylarını getir (pass language parameter)
                fetch('ajax/etkinlik-detay-getir.php?id=' + etkinlikId + '&lang=' + currentLang)
                    .then(response => response.text())
                    .then(data => {
                        modalContent.innerHTML = data;
                    })
                    .catch(error => {
                        modalContent.innerHTML = '<div class="error">' + <?php echo json_encode(__t('events.load_error')); ?> + '</div>';
                        console.error('Hata:', error);
                    });
            });
        });
        
        // Kapatma düğmesine tıklama olayı
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Modal dışına tıklayarak kapatma
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>

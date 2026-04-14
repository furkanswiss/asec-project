<?php
require_once 'db.php';
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/lang.php';
$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');

// Ensure sponsors table exists (in case admin page hasn't been visited yet)
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS sponsors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firma_adi VARCHAR(255) NOT NULL,
        logo_yolu VARCHAR(255),
        aciklama_tr TEXT,
        aciklama_en TEXT,
        web_site VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    
    // Add kategori column if it doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM sponsors LIKE 'kategori'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE sponsors ADD COLUMN kategori ENUM('surekli', 'etkinlik') DEFAULT 'etkinlik'");
    }
} catch (PDOException $e) {
    // Table might already exist, continue
}

// Fetch all sponsors and separate by category
$all_sponsors = $pdo->query('SELECT * FROM sponsors ORDER BY created_at DESC')->fetchAll();
$surekli_sponsors = [];
$etkinlik_sponsors = [];

foreach ($all_sponsors as $sponsor) {
    if (isset($sponsor['kategori']) && $sponsor['kategori'] == 'surekli') {
        $surekli_sponsors[] = $sponsor;
    } else {
        $etkinlik_sponsors[] = $sponsor;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo __t('nav.sponsors'); ?> - ASEC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/etkinlikler.css">
    <style>
        /* Override container to match events page */
        .sponsors-container {
            max-width: 1200px;
            margin: 3rem auto 5rem;
            padding: 0 1.5rem;
        }
        
        /* Page title matches events page exactly */
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1b1f3b;
            margin-bottom: 2.5rem;
            text-align: center;
            position: relative;
            padding-bottom: 1rem;
        }
        
        .page-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 2px;
            background-color: #9370db;
            border-radius: 2px;
        }
        
        /* Section titles match events page exactly */
        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1b1f3b;
            margin: 2.5rem 0 3rem;
            position: relative;
            text-align: center;
            padding-bottom: 0.8rem;
        }
        
        .section-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: #9370db;
            border-radius: 2px;
        }
        
        /* Section wrapper to match events page */
        .sponsors-section {
            margin-bottom: 3rem;
        }
        .sponsors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 2rem;
            justify-content: center;
            padding: 1rem;
        }
        .sponsor-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .sponsor-card.h-100 {
            height: 100%;
        }
        .sponsor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        .sponsor-logo-container {
            padding: 30px 20px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 150px;
        }
        .sponsor-logo {
            max-width: 100%;
            max-height: 120px;
            object-fit: contain;
        }
        .sponsor-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .sponsor-body.d-flex.flex-column {
            display: flex;
            flex-direction: column;
        }
        .sponsor-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .sponsor-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .sponsor-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            margin-top: auto;
        }
        .btn-visit-website {
            width: 100%;
            padding: 10px 20px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background 0.3s ease;
            font-weight: 500;
        }
        .btn-visit-website:hover {
            background: #2980b9;
            color: #fff;
            text-decoration: none;
        }
        .no-sponsors {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        .no-sponsors i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        .no-sponsors h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        /* Responsive - matches events page */
        @media (max-width: 992px) {
            .sponsors-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .sponsors-grid {
                grid-template-columns: 1fr;
            }
            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <div class="sponsors-container">
            <h2 class="page-title"><?php echo __t('nav.sponsors'); ?></h2>
            
            <?php if(empty($surekli_sponsors) && empty($etkinlik_sponsors)): ?>
                <div class="no-sponsors">
                    <i class="fas fa-handshake"></i>
                    <h3><?php echo __t('sponsors.empty.title'); ?></h3>
                    <p><?php echo __t('sponsors.empty.desc'); ?></p>
                </div>
            <?php else: ?>
                <!-- Section 1: Continuous Sponsors (Sürekli Sponsorlar) -->
                <?php if(!empty($surekli_sponsors)): ?>
                    <section class="sponsors-section">
                        <h3 class="section-title"><?php echo __t('header_surekli_sponsor'); ?></h3>
                        <div class="sponsors-grid">
                            <?php foreach($surekli_sponsors as $sponsor): ?>
                                <?php
                                // Select description based on language
                                if ($currentLang == 'en' && !empty($sponsor['aciklama_en'])) {
                                    $display_description = $sponsor['aciklama_en'];
                                } else {
                                    // Default to Turkish
                                    $display_description = $sponsor['aciklama_tr'] ?? '';
                                }
                                ?>
                                <div class="sponsor-card h-100">
                                    <div class="sponsor-logo-container">
                                        <?php if(!empty($sponsor['logo_yolu'])): ?>
                                            <img src="<?= htmlspecialchars($sponsor['logo_yolu']) ?>" alt="<?= htmlspecialchars($sponsor['firma_adi']) ?>" class="sponsor-logo">
                                        <?php else: ?>
                                            <i class="fas fa-building" style="font-size: 4rem; color: #bdc3c7;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sponsor-body d-flex flex-column">
                                        <h3 class="sponsor-name"><?= htmlspecialchars($sponsor['firma_adi']) ?></h3>
                                        <?php if(!empty($display_description)): ?>
                                            <p class="sponsor-description"><?= nl2br(htmlspecialchars($display_description)) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(!empty($sponsor['web_site'])): ?>
                                        <div class="sponsor-footer mt-auto">
                                            <a href="<?= htmlspecialchars($sponsor['web_site']) ?>" target="_blank" rel="noopener noreferrer" class="btn-visit-website">
                                                <i class="fas fa-external-link-alt"></i> <?php echo __t('sponsors.visit_website'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <!-- Section 2: Event Sponsors (Etkinlik Sponsorları) -->
                <?php if(!empty($etkinlik_sponsors)): ?>
                    <section class="sponsors-section">
                        <h3 class="section-title"><?php echo __t('header_etkinlik_sponsor'); ?></h3>
                        <div class="sponsors-grid">
                            <?php foreach($etkinlik_sponsors as $sponsor): ?>
                                <?php
                                // Select description based on language
                                if ($currentLang == 'en' && !empty($sponsor['aciklama_en'])) {
                                    $display_description = $sponsor['aciklama_en'];
                                } else {
                                    // Default to Turkish
                                    $display_description = $sponsor['aciklama_tr'] ?? '';
                                }
                                ?>
                                <div class="sponsor-card h-100">
                                    <div class="sponsor-logo-container">
                                        <?php if(!empty($sponsor['logo_yolu'])): ?>
                                            <img src="<?= htmlspecialchars($sponsor['logo_yolu']) ?>" alt="<?= htmlspecialchars($sponsor['firma_adi']) ?>" class="sponsor-logo">
                                        <?php else: ?>
                                            <i class="fas fa-building" style="font-size: 4rem; color: #bdc3c7;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sponsor-body d-flex flex-column">
                                        <h3 class="sponsor-name"><?= htmlspecialchars($sponsor['firma_adi']) ?></h3>
                                        <?php if(!empty($display_description)): ?>
                                            <p class="sponsor-description"><?= nl2br(htmlspecialchars($display_description)) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if(!empty($sponsor['web_site'])): ?>
                                        <div class="sponsor-footer mt-auto">
                                            <a href="<?= htmlspecialchars($sponsor['web_site']) ?>" target="_blank" rel="noopener noreferrer" class="btn-visit-website">
                                                <i class="fas fa-external-link-alt"></i> <?php echo __t('sponsors.visit_website'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
    <script src="javascript/script.js"></script>
</body>
</html>


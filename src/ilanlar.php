<?php

require_once 'db.php';

ob_start(); // Docker'da hata almamak için tamponlama

if (session_status() === PHP_SESSION_NONE) {

    session_start(); // Oturumu başlat

}



require_once 'includes/lang.php';



// Access language arrays globally

global $translations, $langCode;



// Check if user is logged in

if (!isset($_SESSION['user'])) {

    header('Location: login.php');

    exit;

}



// Fetch all ilanlar from database

$stajIlanlari = [];

$bursIlanlari = [];

$isIlanlari = [];

$bireyselIlanlar = [];



// Self-healing: ensure info_tables and info_rows exist

try {

    // info_tables: stores table metadata

    $pdo->exec("

        CREATE TABLE IF NOT EXISTS info_tables (

            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            title_tr VARCHAR(255) NOT NULL,

            title_en VARCHAR(255) DEFAULT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

    ");



    // info_rows: stores rows for each info table

    $pdo->exec("

        CREATE TABLE IF NOT EXISTS info_rows (

            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

            table_id INT UNSIGNED NOT NULL,

            col1_tr TEXT DEFAULT NULL,

            col1_en TEXT DEFAULT NULL,

            col2_tr TEXT DEFAULT NULL,

            col2_en TEXT DEFAULT NULL,

            col3_tr TEXT DEFAULT NULL,

            col3_en TEXT DEFAULT NULL,

            sort_order INT DEFAULT 0,

            CONSTRAINT fk_info_rows_table

                FOREIGN KEY (table_id) REFERENCES info_tables(id)

                ON DELETE CASCADE

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

    ");

} catch (PDOException $e) {

    // If migration fails, continue without crashing the page

}



try {

    // Only show approved announcements

    // Admin-created announcements (corporate_user_id IS NULL) are automatically approved

    // Corporate-created announcements must be approved (exist in ilanlar table means approved)

    // Ensure corporate_user_id and user_id columns exist

    try {

        $columns = $pdo->query("SHOW COLUMNS FROM ilanlar")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('corporate_user_id', $columns)) {

            $pdo->exec("ALTER TABLE ilanlar ADD COLUMN corporate_user_id INT NULL AFTER id");

            $pdo->exec("ALTER TABLE ilanlar ADD INDEX idx_corporate_user_id (corporate_user_id)");

        }

        if (!in_array('user_id', $columns)) {

            $pdo->exec("ALTER TABLE ilanlar ADD COLUMN user_id INT NULL AFTER id");

            $pdo->exec("ALTER TABLE ilanlar ADD INDEX idx_user_id (user_id)");

        }

    } catch (PDOException $e) {

        // Columns might already exist, continue

    }

    $stmt = $pdo->query('SELECT * FROM ilanlar ORDER BY tarih DESC');

    $allIlanlar = $stmt->fetchAll();

   

    foreach ($allIlanlar as $ilan) {

        if ($ilan['kategori'] === 'Staj İlanları') {

            $stajIlanlari[] = $ilan;

        } elseif ($ilan['kategori'] === 'Burs İlanları') {

            $bursIlanlari[] = $ilan;

        } elseif ($ilan['kategori'] === 'İş İlanı') {

            $isIlanlari[] = $ilan;

        } elseif ($ilan['kategori'] === 'Bireysel İlanlar') {

            $bireyselIlanlar[] = $ilan;

        }

    }

} catch (PDOException $e) {

    // Table might not exist yet, that's okay

    $stajIlanlari = [];

    $bursIlanlari = [];

    $isIlanlari = [];

    $bireyselIlanlar = [];

}



// Determine current language

$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');



// Helper: convert URLs in text to clickable links

function makeLinksClickable($text) {

    if (empty($text)) {

        return '';

    }



    // Escape the text first for security

    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');



    // Pattern to match URLs (http, https, www, or email)

    $urlPattern = '/(https?:\/\/[^\s<>"&]+|www\.[^\s<>"&]+|[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i';



    return preg_replace_callback($urlPattern, function ($matches) {

        $url = html_entity_decode($matches[0], ENT_QUOTES, 'UTF-8');

        $originalUrl = $url;



        // Email address

        if (filter_var($url, FILTER_VALIDATE_EMAIL)) {

            $displayUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            return '<a href="mailto:' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="color: #007bff; text-decoration: underline;">' . $displayUrl . '</a>';

        }



        // Handle www.* without scheme

        if (preg_match('/^www\./i', $url)) {

            $url = 'http://' . $url;

        }



        // Ensure http:// or https://

        if (!preg_match('/^https?:\/\//i', $url)) {

            if (preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $url)) {

                $url = 'http://' . $url;

            } else {

                return htmlspecialchars($originalUrl, ENT_QUOTES, 'UTF-8');

            }

        }



        // Truncate long URLs for display

        $displayUrl = $originalUrl;

        if (strlen($displayUrl) > 50) {

            $displayUrl = substr($displayUrl, 0, 47) . '...';

        }



        $displayUrl = htmlspecialchars($displayUrl, ENT_QUOTES, 'UTF-8');

        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');



        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" style="color: #007bff; text-decoration: underline; word-break: break-all;">' . $displayUrl . '</a>';

    }, $text);

}



// Fetch info tables and rows for Bilgilendirmeler tab

$infoTables = [];

$infoRowsData = [];



try {

    $stmt = $pdo->query('SELECT * FROM info_tables ORDER BY created_at DESC');

    $infoTables = $stmt->fetchAll();



    foreach ($infoTables as $table) {

        $tableId = (int)$table['id'];

        $stmtRows = $pdo->prepare('SELECT * FROM info_rows WHERE table_id = ? ORDER BY sort_order ASC, id ASC');

        $stmtRows->execute([$tableId]);

        $infoRowsData[$tableId] = $stmtRows->fetchAll();

    }

} catch (PDOException $e) {

    // Tables might not exist yet or query might fail; show empty state

    $infoTables = [];

    $infoRowsData = [];

}

?>

<!DOCTYPE html>

<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">

<head>

    <?php include 'includes/head-meta.php'; ?>

    <title><?php echo __t('nav.jobs'); ?> - ASEC Kulübü</title>

    <link rel="stylesheet" href="css/ilanlar.css">

    <link rel="stylesheet" href="css/mobile-optimizations.css">

    <style>

        /* Header hover: remove underlines, use subtle background like other pages */

        header .navbar .nav-link,

        header .navbar .dropdown-item,

        header .navbar a {

            text-decoration: none !important;

            border-bottom: none !important;

            transition: background-color 0.3s ease;

        }



        header .navbar .nav-link:hover,

        header .navbar .dropdown-item:hover,

        header .navbar a:hover {

            text-decoration: none !important;

            border-bottom: none !important;

            background-color: rgba(0, 0, 0, 0.05);

            border-radius: 4px;

        }



        header .navbar .nav-link::after,

        header .navbar .nav-link::before {

            display: none !important;

        }



        /* Modal base z-index to avoid header overlap */

        .modal {

            z-index: 1050 !important;

        }



        .modal-backdrop {

            z-index: 1040 !important;

        }



        /* Bilgilendirmeler Modal Close Button */

        .modal[id^="infoModal"] .modal-header .close {

            color: #fff !important;

            background-color: rgba(255, 255, 255, 0.2) !important;

            border: 2px solid #fff !important;

            border-radius: 50% !important;

            width: 35px !important;

            height: 35px !important;

            display: flex !important;

            align-items: center !important;

            justify-content: center !important;

            padding: 0 !important;

            margin: 0 !important;

            position: absolute;

            right: 15px;

            top: 15px;

            opacity: 1 !important;

            cursor: pointer !important;

            outline: none !important;

        }



        .modal[id^="infoModal"] .modal-header .close:hover {

            background-color: #fff !important;

            color: #1B1F3B !important;

        }



        .modal[id^="infoModal"] .modal-header .close:hover span {

            color: #1B1F3B !important;

        }



        /* Read More button in Bilgilendirmeler cards */

        .announcement-card[data-category="bilgilendirme"] .read-more {

            width: 100% !important;

            display: flex !important;

            align-items: center !important;

            justify-content: center !important;

            gap: 0.5rem !important;

            padding: 0.8rem 2rem !important;

        }



        /* Table links inside bilgilendirme modal */

        .modal-body table td a {

            color: #007bff !important;

            text-decoration: underline !important;

        }

        /* =======================================================
           FINAL MODAL FIX: Centered, Beautiful, & CLOSABLE
           ======================================================= */

        /* 1. Modal Wrapper (The Overlay) - HIDDEN BY DEFAULT */
        .modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 1055 !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            padding-right: 0 !important;
            display: none; /* CRITICAL: Hidden until JS adds .show */
            background-color: rgba(0, 0, 0, 0.75) !important; /* Darker background */
        }

        /* 2. Show Modal ONLY when Bootstrap adds 'show' class */
        .modal.show {
            display: block !important;
            /* Background is handled in .modal base class or backdrop */
        }

        /* 3. The Modal Box (Size & Position) */
        .modal-dialog {
            position: relative !important;
            width: 90% !important;
            max-width: 1000px !important; /* Wider modal */
            margin: 1.75rem auto !important;
            pointer-events: none !important;
            
            /* Vertical Centering */
            display: flex !important;
            align-items: center !important;
            min-height: calc(100% - 3.5rem) !important;
        }

        /* 4. The Content (White Card) */
        .modal-content {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            pointer-events: auto !important;
            background-color: #fff !important;
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
            max-height: 90vh !important; /* Scroll if content is very long */
        }

        /* 5. Scrollable Body */
        .modal-body {
            overflow-y: auto !important;
            padding: 2rem !important;
        }

        /* 6. Table Styling */
        .table { margin-bottom: 0 !important; width: 100% !important; }
        .table th {
            background-color: #f8f9fa !important;
            color: #1B1F3B !important;
            font-weight: 600 !important;
            padding: 15px !important;
            border-bottom: 2px solid #dee2e6 !important;
            white-space: nowrap !important;
        }
        .table td {
            padding: 15px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #dee2e6 !important;
            color: #333 !important;
        }

        /* 7. Header & Footer */
        .modal-header {
            border-bottom: 1px solid #eee !important;
            padding: 1.5rem !important;
            border-radius: 12px 12px 0 0 !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        .modal-footer {
            border-top: 1px solid #eee !important;
            padding: 1rem 1.5rem !important;
            justify-content: flex-end !important;
        }

        /* 8. Fix Close Button (X) */
        .modal-header .close {
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
            border: none !important;
            font-size: 1.5rem !important;
            line-height: 1 !important;
            color: #fff !important;
            opacity: 0.8 !important;
            position: static !important; /* No absolute, flex handles it */
        }
        .modal-header .close:hover { opacity: 1 !important; }

        /* =======================================================
           COMPACT TAB NAVIGATION: Keep 5 buttons on one line
           ======================================================= */
        @media (min-width: 992px) {
            .tab-navigation {
                flex-wrap: nowrap !important; /* Prevent wrapping */
                gap: 0.5rem !important; /* Reduce gap between buttons */
                justify-content: center !important;
                width: 100% !important;
            }

            .tab-btn {
                padding: 0.8rem 1.2rem !important; /* Reduce side padding */
                font-size: 0.95rem !important; /* Slightly smaller font if needed */
                white-space: nowrap !important; /* Keep text on one line */
                min-width: auto !important; /* Allow buttons to shrink to fit text */
                flex-shrink: 1 !important; /* Allow slight shrinking if space is tight */
            }
        }

    </style>

</head>

<body>

    <?php include 'header.php'; ?>

    <main>

        <div class="ilanlar-container">

            <h2 class="page-title"><?php echo strtoupper(__t('nav.jobs')); ?></h2>

           

            <!-- Tab Navigation -->

            <div class="tab-navigation">

                <button class="tab-btn active" data-tab="staj">

                    <i class="fas fa-file-alt"></i>

                    <span><?php echo __t('jobs.tab.internship'); ?></span>

                </button>

                <button class="tab-btn" data-tab="burs">

                    <i class="fas fa-money-bill-wave"></i>

                    <span><?php echo __t('jobs.tab.scholarship'); ?></span>

                </button>

                <button class="tab-btn" data-tab="is">

                    <i class="fas fa-briefcase"></i>

                    <span><?php echo __t('type_job'); ?></span>

                </button>

                <button class="tab-btn" data-tab="bireysel">

                    <i class="fas fa-user"></i>

                    <span><?php echo __t('jobs.tab.individual'); ?></span>

                </button>

                <button class="tab-btn" data-tab="bilgilendirme">

                    <i class="fas fa-info-circle"></i>

                    <span><?php echo $currentLang === 'en' ? 'Information' : 'Bilgilendirmeler'; ?></span>

                </button>

            </div>



            <!-- Tab Content -->

            <div class="tab-content-wrapper">

                <!-- Staj İlanları Tab -->

                <div class="tab-content active" id="staj">

                    <div class="announcements-grid">

                        <?php if (empty($stajIlanlari)): ?>

                            <div class="no-announcements">

                                <i class="fas fa-file-alt"></i>

                                <h3><?php echo __t('jobs.tab.internship'); ?></h3>

                                <p><?php echo __t('jobs.empty.internship'); ?></p>

                            </div>

                        <?php else: ?>

                            <?php foreach($stajIlanlari as $ilan):

                                // Translate job type from DB value

                                $jobTypeMap = [

                                    'İş İlanı' => 'type_job',

                                    'Staj' => 'type_intern',

                                    'Staj İlanları' => 'type_intern',

                                    'Burs' => 'type_scholarship',

                                    'Burs İlanları' => 'type_scholarship',

                                    'Bireysel' => 'type_individual',

                                    'Bireysel İlanlar' => 'type_individual',

                                ];

                                $jobTypeKey = $jobTypeMap[$ilan['kategori']] ?? 'type_job';

                                $translatedCategory = isset($translations[$langCode][$jobTypeKey])

                                    ? $translations[$langCode][$jobTypeKey]

                                    : $ilan['kategori'];

                               

                                // Format date with short month name

                                $tarihTimestamp = strtotime($ilan['tarih']);

                                $monthNum = (int)date('n', $tarihTimestamp);

                                $monthShort = isset($translations[$langCode]['months'][$monthNum])

                                    ? $translations[$langCode]['months'][$monthNum]

                                    : date('F', $tarihTimestamp);

                                $formattedDate = date('d', $tarihTimestamp) . ' ' . $monthShort . ' ' . date('Y', $tarihTimestamp);

                                // Dynamic language switching for title and content
                                $display_title = ($currentLang == 'en' && !empty($ilan['baslik_en'])) ? $ilan['baslik_en'] : $ilan['baslik'];
                                $display_content = ($currentLang == 'en' && !empty($ilan['icerik_en'])) ? $ilan['icerik_en'] : $ilan['icerik'];

                            ?>

                                <div class="announcement-card" data-category="staj">

                                    <div class="announcement-header">

                                        <span class="badge" data-category="staj"><?= htmlspecialchars($translatedCategory) ?></span>

                                        <span class="date"><?= $formattedDate ?></span>

                                    </div>

                                    <h3><?= htmlspecialchars($display_title) ?></h3>

                                    <?php if(!empty($ilan['sirket'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-building"></i> <?= htmlspecialchars($ilan['sirket']) ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['lokasyon'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ilan['lokasyon']) ?></div>

                                    <?php endif; ?>

                                    <p><?= htmlspecialchars($display_content) ?></p>

                                    <?php if(!empty($ilan['son_basvuru'])):

                                        // Format deadline date with short month name

                                        $deadlineTimestamp = strtotime($ilan['son_basvuru']);

                                        $deadlineMonthNum = (int)date('n', $deadlineTimestamp);

                                        $deadlineMonthShort = isset($translations[$langCode]['months'][$deadlineMonthNum])

                                            ? $translations[$langCode]['months'][$deadlineMonthNum]

                                            : date('F', $deadlineTimestamp);

                                        $formattedDeadline = date('d', $deadlineTimestamp) . ' ' . $deadlineMonthShort . ' ' . date('Y', $deadlineTimestamp);

                                    ?>

                                        <div class="ilan-deadline"><i class="fas fa-calendar-alt"></i> <?php echo __t('label_deadline'); ?>: <?= $formattedDeadline ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['link'])): ?>

                                        <a href="<?= htmlspecialchars($ilan['link']) ?>" class="read-more" target="_blank"><?php echo __t('btn_detay'); ?> <i class="fas fa-arrow-right"></i></a>

                                    <?php endif; ?>

                                    <?php

                                    // Determine receiver for message button

                                    $receiver_id = null;

                                    $receiver_type = null;

                                    $show_message_button = false;

                                   

                                    // Hide message button if post was created by Admin

                                    if (isset($ilan['tip']) && $ilan['tip'] === 'admin') {

                                        $show_message_button = false;

                                    } elseif (!empty($ilan['corporate_user_id'])) {

                                        $receiver_id = $ilan['corporate_user_id'];

                                        $receiver_type = 'corporate';

                                        // Don't show if current user is the corporate owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'corporate') {

                                            $show_message_button = true;

                                        }

                                    } elseif (!empty($ilan['user_id'])) {

                                        $receiver_id = $ilan['user_id'];

                                        $receiver_type = 'individual';

                                        // Don't show if current user is the individual owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'individual') {

                                            $show_message_button = true;

                                        }

                                    }

                                   

                                    if ($show_message_button):

                                        $message_url = 'message-compose.php?receiver_id=' . $receiver_id . '&receiver_type=' . $receiver_type;

                                        if (!empty($display_title)) {

                                            $message_url .= '&subject=' . urlencode('Referans: ' . $display_title);

                                        }

                                    ?>

                                        <a href="<?= htmlspecialchars($message_url) ?>" class="read-more btn d-flex align-items-center justify-content-center" style="margin-top: 0.5rem;">

                                            <i class="fas fa-envelope" style="margin-right: 15px !important;"></i>

                                            <span><?php echo __t('btn_mesaj'); ?></span>

                                        </a>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>



                <!-- Burs İlanları Tab -->

                <div class="tab-content" id="burs">

                    <div class="announcements-grid">

                        <?php if (empty($bursIlanlari)): ?>

                            <div class="no-announcements">

                                <i class="fas fa-money-bill-wave"></i>

                                <h3><?php echo __t('jobs.tab.scholarship'); ?></h3>

                                <p><?php echo __t('jobs.empty.scholarship'); ?></p>

                            </div>

                        <?php else: ?>

                            <?php foreach($bursIlanlari as $ilan):

                                // Translate job type from DB value

                                $jobTypeMap = [

                                    'İş İlanı' => 'type_job',

                                    'Staj' => 'type_intern',

                                    'Staj İlanları' => 'type_intern',

                                    'Burs' => 'type_scholarship',

                                    'Burs İlanları' => 'type_scholarship',

                                    'Bireysel' => 'type_individual',

                                    'Bireysel İlanlar' => 'type_individual',

                                ];

                                $jobTypeKey = $jobTypeMap[$ilan['kategori']] ?? 'type_job';

                                $translatedCategory = isset($translations[$langCode][$jobTypeKey])

                                    ? $translations[$langCode][$jobTypeKey]

                                    : $ilan['kategori'];

                               

                                // Format date with short month name

                                $tarihTimestamp = strtotime($ilan['tarih']);

                                $monthNum = (int)date('n', $tarihTimestamp);

                                $monthShort = isset($translations[$langCode]['months'][$monthNum])

                                    ? $translations[$langCode]['months'][$monthNum]

                                    : date('F', $tarihTimestamp);

                                $formattedDate = date('d', $tarihTimestamp) . ' ' . $monthShort . ' ' . date('Y', $tarihTimestamp);

                                // Dynamic language switching for title and content
                                $display_title = ($currentLang == 'en' && !empty($ilan['baslik_en'])) ? $ilan['baslik_en'] : $ilan['baslik'];
                                $display_content = ($currentLang == 'en' && !empty($ilan['icerik_en'])) ? $ilan['icerik_en'] : $ilan['icerik'];

                            ?>

                                <div class="announcement-card" data-category="burs">

                                    <div class="announcement-header">

                                        <span class="badge" data-category="burs"><?= htmlspecialchars($translatedCategory) ?></span>

                                        <span class="date"><?= $formattedDate ?></span>

                                    </div>

                                    <h3><?= htmlspecialchars($display_title) ?></h3>

                                    <?php if(!empty($ilan['sirket'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-building"></i> <?= htmlspecialchars($ilan['sirket']) ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['lokasyon'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ilan['lokasyon']) ?></div>

                                    <?php endif; ?>

                                    <p><?= htmlspecialchars($display_content) ?></p>

                                    <?php if(!empty($ilan['son_basvuru'])):

                                        // Format deadline date with short month name

                                        $deadlineTimestamp = strtotime($ilan['son_basvuru']);

                                        $deadlineMonthNum = (int)date('n', $deadlineTimestamp);

                                        $deadlineMonthShort = isset($translations[$langCode]['months'][$deadlineMonthNum])

                                            ? $translations[$langCode]['months'][$deadlineMonthNum]

                                            : date('F', $deadlineTimestamp);

                                        $formattedDeadline = date('d', $deadlineTimestamp) . ' ' . $deadlineMonthShort . ' ' . date('Y', $deadlineTimestamp);

                                    ?>

                                        <div class="ilan-deadline"><i class="fas fa-calendar-alt"></i> <?php echo __t('label_deadline'); ?>: <?= $formattedDeadline ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['link'])): ?>

                                        <a href="<?= htmlspecialchars($ilan['link']) ?>" class="read-more" target="_blank"><?php echo __t('btn_detay'); ?> <i class="fas fa-arrow-right"></i></a>

                                    <?php endif; ?>

                                    <?php

                                    // Determine receiver for message button

                                    $receiver_id = null;

                                    $receiver_type = null;

                                    $show_message_button = false;

                                   

                                    // Hide message button if post was created by Admin

                                    if (isset($ilan['tip']) && $ilan['tip'] === 'admin') {

                                        $show_message_button = false;

                                    } elseif (!empty($ilan['corporate_user_id'])) {

                                        $receiver_id = $ilan['corporate_user_id'];

                                        $receiver_type = 'corporate';

                                        // Don't show if current user is the corporate owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'corporate') {

                                            $show_message_button = true;

                                        }

                                    } elseif (!empty($ilan['user_id'])) {

                                        $receiver_id = $ilan['user_id'];

                                        $receiver_type = 'individual';

                                        // Don't show if current user is the individual owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'individual') {

                                            $show_message_button = true;

                                        }

                                    }

                                   

                                    if ($show_message_button):

                                        $message_url = 'message-compose.php?receiver_id=' . $receiver_id . '&receiver_type=' . $receiver_type;

                                        if (!empty($display_title)) {

                                            $message_url .= '&subject=' . urlencode('Referans: ' . $display_title);

                                        }

                                    ?>

                                        <a href="<?= htmlspecialchars($message_url) ?>" class="read-more btn d-flex align-items-center justify-content-center" style="margin-top: 0.5rem;">

                                            <i class="fas fa-envelope" style="margin-right: 15px !important;"></i>

                                            <span><?php echo __t('btn_mesaj'); ?></span>

                                        </a>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>



                <!-- İş İlanı Tab -->

                <div class="tab-content" id="is">

                    <div class="announcements-grid">

                        <?php if (empty($isIlanlari)): ?>

                            <div class="no-announcements">

                                <i class="fas fa-briefcase"></i>

                                <h3><?php echo __t('type_job'); ?></h3>

                                <p><?php echo $langCode === 'en' ? 'There are currently no job listings. New listings will be added soon.' : 'Şu anda iş ilanı bulunmamaktadır. Yakında yeni ilanlar eklenecektir.'; ?></p>

                            </div>

                        <?php else: ?>

                            <?php foreach($isIlanlari as $ilan):

                                // Translate job type from DB value

                                $jobTypeMap = [

                                    'İş İlanı' => 'type_job',

                                    'Staj' => 'type_intern',

                                    'Staj İlanları' => 'type_intern',

                                    'Burs' => 'type_scholarship',

                                    'Burs İlanları' => 'type_scholarship',

                                    'Bireysel' => 'type_individual',

                                    'Bireysel İlanlar' => 'type_individual',

                                ];

                                $jobTypeKey = $jobTypeMap[$ilan['kategori']] ?? 'type_job';

                                $translatedCategory = isset($translations[$langCode][$jobTypeKey])

                                    ? $translations[$langCode][$jobTypeKey]

                                    : $ilan['kategori'];

                               

                                // Format date with short month name

                                $tarihTimestamp = strtotime($ilan['tarih']);

                                $monthNum = (int)date('n', $tarihTimestamp);

                                $monthShort = isset($translations[$langCode]['months'][$monthNum])

                                    ? $translations[$langCode]['months'][$monthNum]

                                    : date('F', $tarihTimestamp);

                                $formattedDate = date('d', $tarihTimestamp) . ' ' . $monthShort . ' ' . date('Y', $tarihTimestamp);

                                // Dynamic language switching for title and content
                                $display_title = ($currentLang == 'en' && !empty($ilan['baslik_en'])) ? $ilan['baslik_en'] : $ilan['baslik'];
                                $display_content = ($currentLang == 'en' && !empty($ilan['icerik_en'])) ? $ilan['icerik_en'] : $ilan['icerik'];

                            ?>

                                <div class="announcement-card" data-category="is">

                                    <div class="announcement-header">

                                        <span class="badge" data-category="is"><?= htmlspecialchars($translatedCategory) ?></span>

                                        <span class="date"><?= $formattedDate ?></span>

                                    </div>

                                    <h3><?= htmlspecialchars($display_title) ?></h3>

                                    <?php if(!empty($ilan['sirket'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-building"></i> <?= htmlspecialchars($ilan['sirket']) ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['lokasyon'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ilan['lokasyon']) ?></div>

                                    <?php endif; ?>

                                    <p><?= htmlspecialchars($display_content) ?></p>

                                    <?php if(!empty($ilan['son_basvuru'])):

                                        // Format deadline date with short month name

                                        $deadlineTimestamp = strtotime($ilan['son_basvuru']);

                                        $deadlineMonthNum = (int)date('n', $deadlineTimestamp);

                                        $deadlineMonthShort = isset($translations[$langCode]['months'][$deadlineMonthNum])

                                            ? $translations[$langCode]['months'][$deadlineMonthNum]

                                            : date('F', $deadlineTimestamp);

                                        $formattedDeadline = date('d', $deadlineTimestamp) . ' ' . $deadlineMonthShort . ' ' . date('Y', $deadlineTimestamp);

                                    ?>

                                        <div class="ilan-deadline"><i class="fas fa-calendar-alt"></i> <?php echo __t('label_deadline'); ?>: <?= $formattedDeadline ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['link'])): ?>

                                        <a href="<?= htmlspecialchars($ilan['link']) ?>" class="read-more" target="_blank"><?php echo __t('btn_detay'); ?> <i class="fas fa-arrow-right"></i></a>

                                    <?php endif; ?>

                                    <?php

                                    // Determine receiver for message button

                                    $receiver_id = null;

                                    $receiver_type = null;

                                    $show_message_button = false;

                                   

                                    // Hide message button if post was created by Admin

                                    if (isset($ilan['tip']) && $ilan['tip'] === 'admin') {

                                        $show_message_button = false;

                                    } elseif (!empty($ilan['corporate_user_id'])) {

                                        $receiver_id = $ilan['corporate_user_id'];

                                        $receiver_type = 'corporate';

                                        // Don't show if current user is the corporate owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'corporate') {

                                            $show_message_button = true;

                                        }

                                    } elseif (!empty($ilan['user_id'])) {

                                        $receiver_id = $ilan['user_id'];

                                        $receiver_type = 'individual';

                                        // Don't show if current user is the individual owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'individual') {

                                            $show_message_button = true;

                                        }

                                    }

                                   

                                    if ($show_message_button):

                                        $message_url = 'message-compose.php?receiver_id=' . $receiver_id . '&receiver_type=' . $receiver_type;

                                        if (!empty($display_title)) {

                                            $message_url .= '&subject=' . urlencode('Referans: ' . $display_title);

                                        }

                                    ?>

                                        <a href="<?= htmlspecialchars($message_url) ?>" class="read-more btn d-flex align-items-center justify-content-center" style="margin-top: 0.5rem;">

                                            <i class="fas fa-envelope" style="margin-right: 15px !important;"></i>

                                            <span><?php echo __t('btn_mesaj'); ?></span>

                                        </a>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>



                <!-- Bireysel İlanlar Tab -->

                <div class="tab-content" id="bireysel">

                    <?php

                    // Only show "Post Individual Ad" button for individual users or guests (not corporate users)

                    $user_type = $_SESSION['user_type'] ?? 'individual';

                    if ($user_type !== 'corporate'):

                    ?>

                    <div style="margin-bottom: 2rem; text-align: right;">

                        <a href="bireysel-ilan-ekle.php" class="btn-post-ad">

                            <i class="fas fa-plus-circle"></i> <?php echo $langCode === 'en' ? 'Post an Ad' : 'İlan Ver'; ?>

                        </a>

                    </div>

                    <?php endif; ?>

                    <div class="announcements-grid">

                        <?php if (empty($bireyselIlanlar)): ?>

                            <div class="no-announcements">

                                <i class="fas fa-user"></i>

                                <h3><?php echo __t('jobs.tab.individual'); ?></h3>

                                <p><?php echo __t('jobs.empty.individual'); ?></p>

                            </div>

                        <?php else: ?>

                            <?php foreach($bireyselIlanlar as $ilan):

                                // Check if current user owns this ad (for individual users only)

                                // Only show delete button if user_id exists and matches current user

                                $canDelete = false;

                                if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'individual'

                                    && isset($_SESSION['user_id'])

                                    && !empty($ilan['user_id'])

                                    && (int)$ilan['user_id'] === (int)$_SESSION['user_id']) {

                                    $canDelete = true;

                                }

                               

                                // Translate job type from DB value

                                $jobTypeMap = [

                                    'İş İlanı' => 'type_job',

                                    'Staj' => 'type_intern',

                                    'Staj İlanları' => 'type_intern',

                                    'Burs' => 'type_scholarship',

                                    'Burs İlanları' => 'type_scholarship',

                                    'Bireysel' => 'type_individual',

                                    'Bireysel İlanlar' => 'type_individual',

                                ];

                                $jobTypeKey = $jobTypeMap[$ilan['kategori']] ?? 'type_job';

                                $translatedCategory = isset($translations[$langCode][$jobTypeKey])

                                    ? $translations[$langCode][$jobTypeKey]

                                    : $ilan['kategori'];

                               

                                // Format date with short month name

                                $tarihTimestamp = strtotime($ilan['tarih']);

                                $monthNum = (int)date('n', $tarihTimestamp);

                                $monthShort = isset($translations[$langCode]['months'][$monthNum])

                                    ? $translations[$langCode]['months'][$monthNum]

                                    : date('F', $tarihTimestamp);

                                $formattedDate = date('d', $tarihTimestamp) . ' ' . $monthShort . ' ' . date('Y', $tarihTimestamp);

                                // Dynamic language switching for title and content
                                $display_title = ($currentLang == 'en' && !empty($ilan['baslik_en'])) ? $ilan['baslik_en'] : $ilan['baslik'];
                                $display_content = ($currentLang == 'en' && !empty($ilan['icerik_en'])) ? $ilan['icerik_en'] : $ilan['icerik'];

                            ?>

                                <div class="announcement-card" data-category="bireysel">

                                    <div class="announcement-header">

                                        <span class="badge" data-category="bireysel"><?= htmlspecialchars($translatedCategory) ?></span>

                                        <div style="display: flex; align-items: center; gap: 1rem;">

                                            <span class="date"><?= $formattedDate ?></span>

                                            <?php if ($canDelete): ?>

                                                <a href="individual-ilan-sil.php?id=<?= $ilan['id'] ?>"

                                                   class="btn-delete-ad"

                                                   onclick="return confirm('<?php echo $langCode === 'en' ? 'Are you sure you want to delete this ad?' : 'Bu ilanı silmek istediğinizden emin misiniz?'; ?>');"

                                                   title="<?php echo $langCode === 'en' ? 'Delete Ad' : 'İlanı Sil'; ?>">

                                                    <i class="fas fa-trash-alt"></i>

                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                    <h3><?= htmlspecialchars($display_title) ?></h3>

                                    <?php if(!empty($ilan['sirket'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-building"></i> <?= htmlspecialchars($ilan['sirket']) ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['lokasyon'])): ?>

                                        <div class="ilan-meta"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ilan['lokasyon']) ?></div>

                                    <?php endif; ?>

                                    <p><?= htmlspecialchars($display_content) ?></p>

                                    <?php if(!empty($ilan['son_basvuru'])):

                                        // Format deadline date with short month name

                                        $deadlineTimestamp = strtotime($ilan['son_basvuru']);

                                        $deadlineMonthNum = (int)date('n', $deadlineTimestamp);

                                        $deadlineMonthShort = isset($translations[$langCode]['months'][$deadlineMonthNum])

                                            ? $translations[$langCode]['months'][$deadlineMonthNum]

                                            : date('F', $deadlineTimestamp);

                                        $formattedDeadline = date('d', $deadlineTimestamp) . ' ' . $deadlineMonthShort . ' ' . date('Y', $deadlineTimestamp);

                                    ?>

                                        <div class="ilan-deadline"><i class="fas fa-calendar-alt"></i> <?php echo __t('label_deadline'); ?>: <?= $formattedDeadline ?></div>

                                    <?php endif; ?>

                                    <?php if(!empty($ilan['link'])): ?>

                                        <a href="<?= htmlspecialchars($ilan['link']) ?>" class="read-more" target="_blank"><?php echo __t('btn_detay'); ?> <i class="fas fa-arrow-right"></i></a>

                                    <?php endif; ?>

                                    <?php

                                    // Determine receiver for message button

                                    $receiver_id = null;

                                    $receiver_type = null;

                                    $show_message_button = false;

                                   

                                    // Hide message button if post was created by Admin

                                    if (isset($ilan['tip']) && $ilan['tip'] === 'admin') {

                                        $show_message_button = false;

                                    } elseif (!empty($ilan['corporate_user_id'])) {

                                        $receiver_id = $ilan['corporate_user_id'];

                                        $receiver_type = 'corporate';

                                        // Don't show if current user is the corporate owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'corporate') {

                                            $show_message_button = true;

                                        }

                                    } elseif (!empty($ilan['user_id'])) {

                                        $receiver_id = $ilan['user_id'];

                                        $receiver_type = 'individual';

                                        // Don't show if current user is the individual owner

                                        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $receiver_id || $_SESSION['user_type'] != 'individual') {

                                            $show_message_button = true;

                                        }

                                    }

                                   

                                    if ($show_message_button):

                                        $message_url = 'message-compose.php?receiver_id=' . $receiver_id . '&receiver_type=' . $receiver_type;

                                        if (!empty($display_title)) {

                                            $message_url .= '&subject=' . urlencode('Referans: ' . $display_title);

                                        }

                                    ?>

                                        <a href="<?= htmlspecialchars($message_url) ?>" class="read-more btn d-flex align-items-center justify-content-center" style="margin-top: 0.5rem;">

                                            <i class="fas fa-envelope" style="margin-right: 15px !important;"></i>

                                            <span><?php echo __t('btn_mesaj'); ?></span>

                                        </a>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>



                <!-- Bilgilendirmeler Tab -->

                <div class="tab-content" id="bilgilendirme">

                    <div class="announcements-grid">

                        <?php if (empty($infoTables)): ?>

                            <div class="no-announcements">

                                <i class="fas fa-info-circle"></i>

                                <h3>Bilgilendirmeler</h3>

                                <p>

                                    <?php

                                    echo $currentLang === 'en'

                                        ? 'There are currently no information tables available. New information will be added soon.'

                                        : 'Henüz bilgilendirme tablosu bulunmamaktadır. Yakında yeni bilgilendirmeler eklenecektir.';

                                    ?>

                                </p>

                            </div>

                        <?php else: ?>

                            <?php foreach ($infoTables as $table):

                                $title = ($currentLang === 'en' && !empty($table['title_en']))

                                    ? $table['title_en']

                                    : $table['title_tr'];

                                $rows = isset($infoRowsData[$table['id']]) ? $infoRowsData[$table['id']] : [];

                            ?>

                                <div class="announcement-card" data-category="bilgilendirme">

                                    <div class="announcement-header">

                                        <span class="badge" data-category="bilgilendirme">

                                            <i class="fas fa-info-circle"></i>

                                            <?php echo $currentLang === 'en' ? 'Information' : 'Bilgilendirme'; ?>

                                        </span>

                                    </div>

                                    <h3><?= htmlspecialchars($title) ?></h3>



                                    <?php if (!empty($rows)): ?>

                                        <p style="color: #6c757d; margin: 1rem 0; font-size: 0.9rem;">

                                            <?php

                                            echo $currentLang === 'en'

                                                ? count($rows) . ' rows of data'

                                                : count($rows) . ' satır veri';

                                            ?>

                                        </p>

                                    <?php else: ?>

                                        <p style="color: #6c757d; margin: 1rem 0; text-align: center;">

                                            <?php

                                            echo $currentLang === 'en'

                                                ? 'No data available'

                                                : 'Veri bulunmuyor';

                                            ?>

                                        </p>

                                    <?php endif; ?>



                                    <button

                                        type="button"

                                        class="read-more"

                                        data-toggle="modal"

                                        data-target="#infoModal<?= (int)$table['id'] ?>"

                                    >

                                        <i class="fas fa-eye"></i>

                                        <span>

                                            <?php echo $currentLang === 'en' ? 'View Details' : 'İncele'; ?>

                                        </span>

                                    </button>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </main>



    <?php if (!empty($infoTables)): ?>

        <?php foreach ($infoTables as $table):

            $title = ($currentLang === 'en' && !empty($table['title_en']))

                ? $table['title_en']

                : $table['title_tr'];

            $rows = isset($infoRowsData[$table['id']]) ? $infoRowsData[$table['id']] : [];

        ?>

            <div

                class="modal fade"

                id="infoModal<?= (int)$table['id'] ?>"

                tabindex="-1"

                role="dialog"

                aria-labelledby="infoModalLabel<?= (int)$table['id'] ?>"

                aria-hidden="true"

            >

                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">

                    <div class="modal-content">

                        <div class="modal-header" style="background-color: #1B1F3B; color: #fff; position: relative;">

                            <h5 class="modal-title" id="infoModalLabel<?= (int)$table['id'] ?>" style="flex: 1;">

                                <?= htmlspecialchars($title) ?>

                            </h5>

                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                                <span aria-hidden="true">&times;</span>

                            </button>

                        </div>

                        <div class="modal-body">

                            <?php if (empty($rows)): ?>

                                <p class="text-center text-muted">

                                    <?php

                                    echo $currentLang === 'en'

                                        ? 'No data available in this table.'

                                        : 'Bu tabloda henüz veri bulunmamaktadır.';

                                    ?>

                                </p>

                            <?php else: ?>

                                <div class="table-responsive">

                                    <table class="table table-bordered table-striped">

                                        <thead>

                                            <tr>

                                                <th style="background-color: #1B1F3B; color: #fff; text-align: center;">

                                                    <?php echo $currentLang === 'en' ? 'Company' : 'Şirket'; ?>

                                                </th>

                                                <th style="background-color: #1B1F3B; color: #fff; text-align: center;">

                                                    <?php echo $currentLang === 'en' ? 'Requirements' : 'Şartlar'; ?>

                                                </th>

                                                <th style="background-color: #1B1F3B; color: #fff; text-align: center;">

                                                    <?php echo $currentLang === 'en' ? 'Link' : 'Link'; ?>

                                                </th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            <?php foreach ($rows as $row): ?>

                                                <tr>

                                                    <td style="text-align: center; word-break: break-word;">

                                                        <?= makeLinksClickable(

                                                            $currentLang === 'en'

                                                                ? ($row['col1_en'] ?? '')

                                                                : ($row['col1_tr'] ?? '')

                                                        ) ?>

                                                    </td>

                                                    <td style="text-align: center; word-break: break-word;">

                                                        <?= makeLinksClickable(

                                                            $currentLang === 'en'

                                                                ? ($row['col2_en'] ?? '')

                                                                : ($row['col2_tr'] ?? '')

                                                        ) ?>

                                                    </td>

                                                    <td style="text-align: center; word-break: break-word;">

                                                        <?= makeLinksClickable(

                                                            $currentLang === 'en'

                                                                ? ($row['col3_en'] ?? '')

                                                                : ($row['col3_tr'] ?? '')

                                                        ) ?>

                                                    </td>

                                                </tr>

                                            <?php endforeach; ?>

                                        </tbody>

                                    </table>

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="modal-footer">

                            <button type="button" class="btn" data-dismiss="modal" style="background-color: #1c2444 !important; color: #fff !important; opacity: 1 !important; padding: 8px 20px; border-radius: 5px;">

                                <?php echo $currentLang === 'en' ? 'Close' : 'Kapat'; ?>

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>



    <?php include 'footer.php'; ?>



    <!-- Bootstrap JS for Modals (loaded before custom scripts) -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>



    <script src="javascript/script.js"></script>

    <script src="js/ilanlar.js"></script>

</body>

</html> 
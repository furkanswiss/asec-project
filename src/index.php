<?php
require_once 'db.php';
ob_start(); // Çıktı tamponlamayı başlat (Docker için hayati önem taşır!)
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu başlat
}
require_once 'includes/lang.php';
// Veritabanını çağır
// ... kodların devamı ...
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>ASEC Kulübü - Siber Güvenlik ve Yazılım Topluluğu</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/mobile-optimizations.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main>
        <section class="hero">
            <div class="hero-container">
                <!--
                <div class="binary-spiral-container">
                    <div class="binary-spiral"></div>
                </div>
                -->

                <!-- REPLACE existing animated-asec-container with this -->
<div class="animated-asec-container hero-asec-intro">
  <div class="intro">
    <span class="brace left-curly">{</span>
    <span class="brace right-curly">}</span>

    <div class="center">
      <!-- Typing (ilk görünen) -->
      <span class="typing">ASEC</span>

      <!-- Final glitch+glow (başlangıçta gizli, typing bittikten sonra görünür) -->
      <div class="animated-asec" aria-hidden="true">
        <span class="letter" data-text="A">A</span>
        <span class="letter" data-text="S">S</span>
        <span class="letter" data-text="E">E</span>
        <span class="letter" data-text="C">C</span>
      </div>
    </div>
  </div>
</div>

                
                <div class="hero-content">
                    <h2><?php echo __t('home.hero.title'); ?></h2>
                    <p><?php echo __t('home.hero.desc'); ?></p>
                    <div class="cta-buttons">
                        <?php
                        // Check for both regular user session and admin session
                        if (!isset($_SESSION['user']) && (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true)) {
                            // Show "Join Now" button when not logged in
                            echo '<a href="register" class="btn-primary">' . __t('home.hero.cta.join') . '</a>';
                        }
                        ?>
                        <a href="hakkimizda" class="btn-secondary"><?php echo __t('home.hero.cta.more'); ?></a>
                    </div>
                </div>
            </div>
        </section>
        
<section class="features">
  <h2><?php echo __t('home.features.title'); ?></h2>
  <div class="features-grid">
    <div class="feature-card">
      <div class="card-content">
        <lottie-player 
            src="images/animations/proje.json"
            background="transparent"  
            speed="1"   
            loop  
            autoplay>
        </lottie-player>
        <h3><?php echo __t('home.features.project.title'); ?></h3>
        <p><?php echo __t('home.features.project.desc'); ?></p>
      </div>
    </div>
    <div class="feature-card">
      <div class="card-content">
        <lottie-player 
            src="images/animations/event.json"
            background="transparent"  
            speed="1"   
            loop  
            autoplay>
        </lottie-player>
        <h3><?php echo __t('home.features.events.title'); ?></h3>
        <p><?php echo __t('home.features.events.desc'); ?></p>
      </div>
    </div>
    <div class="feature-card">
      <div class="card-content">
        <lottie-player 
            src="images/animations/kariyer.json"
            background="transparent"  
            speed="1"   
            loop  
            autoplay>
        </lottie-player>
        <h3><?php echo __t('home.features.career.title'); ?></h3>
        <p><?php echo __t('home.features.career.desc'); ?></p>
      </div>
    </div>
  </div>
</section>


        
<!--yaklaşan etkinlikler-->
<section class="upcoming-events">
  <h2><?php echo __t('home.upcoming.title'); ?></h2>

  <div class="events-slider">
      <?php
      require_once 'db.php';
      $today = date('Y-m-d');
      // Yaklaşan etkinlikleri tarihe göre sıralayarak en yakın 3 etkinliği getir
      $stmt = $pdo->prepare("SELECT * FROM etkinlikler WHERE tarih >= :today ORDER BY tarih ASC LIMIT 3");
      $stmt->execute(['today' => $today]);
      $yaklasan_etkinlikler = $stmt->fetchAll();
      
      if (count($yaklasan_etkinlikler) > 0) {
          // Ensure langCode is set
          $currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');
          
          foreach ($yaklasan_etkinlikler as $etkinlik) {
              // Parse and format date with translation support
              $eDate = strtotime($etkinlik['tarih']);
              $eDay = date('d', $eDate);
              $eYear = date('Y', $eDate);
              $eMonthNum = (int)date('n', $eDate);
              
              // Translation logic
              $eMonthName = isset($translations[$currentLang]['months'][$eMonthNum]) 
                            ? $translations[$currentLang]['months'][$eMonthNum] 
                            : date('F', $eDate);
              ?>
              <div class="event-card">
                  <div class="event-date"><?= $eDay ?> <?= $eMonthName ?> <?= $eYear ?></div>
                  <h3><?= htmlspecialchars($etkinlik['baslik']) ?></h3>
                  <p><?= htmlspecialchars(substr($etkinlik['aciklama'], 0, 150)) . (strlen($etkinlik['aciklama']) > 150 ? '...' : '') ?></p>
                  <a href="etkinlik-detay.php?id=<?= $etkinlik['id'] ?>" class="event-button"><?php echo __t('home.upcoming.details'); ?></a>
              </div>
              <?php
          }
      } else {
          ?>
          <div class="no-events">
              <lottie-player 
                src="images/animations/noevent.json"
                background="transparent"  
                speed="1"   
                loop  
                autoplay>
              </lottie-player>
              <p><?php echo __t('home.upcoming.none'); ?></p>
          </div>
          <?php
      }
      ?>
  </div>
  <div class="view-all-events">
      <a href="etkinlikler.php" class="view-all-button"><?php echo __t('home.upcoming.view_all'); ?></a>
  </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script src="javascript/animation.js"></script>
    <script src="javascript/script.js"></script>
    <script src="javascript/matrix-animation.js"></script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    
</body>
</html>

<?php
/**
 * ASEC Kulübü - Head Meta Etiketleri
 * Bu dosya, tüm sayfalarda kullanılacak ortak head meta etiketlerini içerir.
 */

// Google Analytics - Loads ONLY on Production AND if Consent is given
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'aybuasec.org') !== false) {
?>
<script>
  // 1. Define the loader function (does not run immediately)
  window.loadGoogleAnalytics = function() {
      // Prevent double loading
      if (document.getElementById('ga4-script')) return;

      console.log("✅ GA4 Consent Given. Loading Analytics...");
      
      var script = document.createElement('script');
      script.id = 'ga4-script';
      script.async = true;
      script.src = "https://www.googletagmanager.com/gtag/js?id=G-XV1CTB5E2K";
      document.head.appendChild(script);

      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-XV1CTB5E2K');
  };

  // 2. Check if user ALREADY accepted in a previous session
  if (localStorage.getItem('cookieConsent') === 'true') {
      window.loadGoogleAnalytics();
  }
</script>
<?php } ?>

<?php
require_once __DIR__ . '/lang.php';
?>
<!-- Meta Etiketleri -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Favicon -->
<link rel="icon" href="images/gallery/try.png" type="image/png">
<link rel="shortcut icon" href="images/gallery/try.png" type="image/png">
<link rel="apple-touch-icon" href="images/gallery/try.png">
<meta name="msapplication-TileImage" content="images/gallery/try.png">
<meta name="msapplication-TileColor" content="#1B1F3B">
<meta name="theme-color" content="#1B1F3B">

<!-- Ortak CSS Dosyaları -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/reset.css">
<link rel="stylesheet" href="css/header.css">
<link rel="stylesheet" href="css/footer.css">

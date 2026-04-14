<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Sayfa Bulunamadı - ASEC Kulübü</title>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <div class="error-container">
            <i class="fas fa-search error-icon"></i>
            <div class="error-code">404</div>
            <h1 class="error-title">Sayfa Bulunamadı</h1>
            <p class="error-message">
                Aradığınız sayfa mevcut değil veya taşınmış olabilir. Lütfen URL'yi kontrol edin veya ana sayfaya dönün.
            </p>
            <a href="/" class="btn-home">
                <i class="fas fa-home"></i> Ana Sayfaya Dön
            </a>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>

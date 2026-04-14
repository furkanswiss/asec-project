<!DOCTYPE html>
<html lang="tr">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Sunucu Hatası - ASEC Kulübü</title>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <div class="error-container">
            <i class="fas fa-exclamation-triangle error-icon"></i>
            <div class="error-code">500</div>
            <h1 class="error-title">Sunucu Hatası</h1>
            <p class="error-message">
                Üzgünüz, sunucuda beklenmeyen bir hata oluştu. Lütfen daha sonra tekrar deneyin veya ana sayfaya dönün.
            </p>
            <a href="/" class="btn-home">
                <i class="fas fa-home"></i> Ana Sayfaya Dön
            </a>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
</body>
</html>

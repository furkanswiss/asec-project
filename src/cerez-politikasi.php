<?php
require_once 'db.php';
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/lang.php';
$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title>Çerez Politikası - ASEC Kulübü</title>
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
        .policy-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            line-height: 1.8;
            color: #333;
        }
        .policy-container h1 {
            color: #1B1F3B;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: 700;
        }
        .policy-container h2 {
            color: #9370db;
            margin-top: 40px;
            margin-bottom: 20px;
            font-size: 1.8rem;
            font-weight: 600;
        }
        .policy-container p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        .policy-container ul {
            margin-bottom: 20px;
            padding-left: 30px;
        }
        .policy-container li {
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .policy-container .intro {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #9370db;
            margin-bottom: 30px;
        }
        .policy-container .contact-info {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <main>
        <div class="policy-container">
            <h1>Çerez (Cookie) Politikası ve Aydınlatma Metni</h1>
            
            <div class="intro">
                <p><strong>ASEC Kulübü</strong> olarak, web sitemizde kullanıcı deneyimini iyileştirmek ve site trafiğini analiz etmek amacıyla çerezler (cookies) kullanmaktayız. Bu politika, çerezlerin nasıl kullanıldığını ve haklarınızı açıklamaktadır.</p>
            </div>
            
            <h2>Çerez Nedir?</h2>
            <p>Çerezler, bir web sitesini ziyaret ettiğinizde tarayıcınız tarafından cihazınıza (bilgisayar, tablet, telefon) kaydedilen küçük metin dosyalarıdır. Bu dosyalar, web sitesinin düzgün çalışmasını sağlar ve kullanıcı deneyimini iyileştirir.</p>
            
            <h2>Kullandığımız Çerez Türleri</h2>
            <p>Web sitemizde aşağıdaki çerez türlerini kullanmaktayız:</p>
            
            <h3>1. Zorunlu Çerezler</h3>
            <p>Bu çerezler, web sitemizin temel işlevlerinin çalışması için gereklidir. Bu çerezler olmadan sitemizin bazı özellikleri düzgün çalışmayabilir.</p>
            <ul>
                <li><strong>Oturum Çerezleri:</strong> Giriş yaptığınızda oturumunuzun açık kalmasını sağlar.</li>
                <li><strong>Güvenlik Çerezleri:</strong> Güvenli bağlantılar ve form gönderimlerini korur.</li>
            </ul>
            
            <h3>2. Analiz Çerezleri</h3>
            <p>Web sitemizde Google Analytics kullanarak ziyaretçi trafiğini ve site kullanımını analiz ediyoruz. Bu çerezler:</p>
            <ul>
                <li>Sayfa görüntüleme sayılarını ölçer</li>
                <li>Kullanıcı davranışlarını anonim olarak analiz eder</li>
                <li>Site performansını iyileştirmek için veri toplar</li>
            </ul>
            <p><strong>Google Analytics Çerez ID:</strong> G-XV1CTB5E2K</p>
            
            <h2>Veri Kullanımı ve Gizlilik</h2>
            <p>Topladığımız veriler:</p>
            <ul>
                <li>Sadece istatistiksel analiz ve site iyileştirme amaçlı kullanılır</li>
                <li>Üçüncü taraflara satılmaz veya paylaşılmaz</li>
                <li>Kişisel kimlik bilgilerinizle ilişkilendirilmez</li>
                <li>Anonim ve toplu veri olarak işlenir</li>
            </ul>
            
            <h2>Çerezleri Nasıl Devre Dışı Bırakabilirsiniz?</h2>
            <p>Tarayıcı ayarlarınızı kullanarak çerezleri devre dışı bırakabilir veya silme tercihinde bulunabilirsiniz. Ancak, çerezleri devre dışı bırakmanız durumunda web sitemizin bazı özellikleri düzgün çalışmayabilir.</p>
            <p><strong>Popüler tarayıcılar için çerez ayarları:</strong></p>
            <ul>
                <li><strong>Google Chrome:</strong> Ayarlar > Gizlilik ve güvenlik > Çerezler ve diğer site verileri</li>
                <li><strong>Mozilla Firefox:</strong> Seçenekler > Gizlilik ve Güvenlik > Çerezler ve site verileri</li>
                <li><strong>Safari:</strong> Tercihler > Gizlilik > Çerezleri ve diğer web sitesi verilerini yönet</li>
                <li><strong>Microsoft Edge:</strong> Ayarlar > Gizlilik, arama ve hizmetler > Çerezler ve site izinleri</li>
            </ul>
            
            <h2>Çerez Politikası Güncellemeleri</h2>
            <p>Bu çerez politikası zaman zaman güncellenebilir. Önemli değişiklikler durumunda web sitemizde duyuru yapılacaktır. Politikanın güncel versiyonu her zaman bu sayfada yayınlanmaktadır.</p>
            <p><strong>Son Güncelleme:</strong> <?php echo date('d F Y', strtotime('now')); ?></p>
            
            <div class="contact-info">
                <h2>İletişim</h2>
                <p>Çerez politikamız hakkında sorularınız için bizimle iletişime geçebilirsiniz:</p>
                <p>
                    <strong>ASEC Kulübü</strong><br>
                    <a href="iletisim.php">İletişim Sayfası</a> üzerinden bize ulaşabilirsiniz.
                </p>
            </div>
        </div>
    </main>
    
    <?php include 'footer.php'; ?>
    <script src="javascript/script.js"></script>
</body>
</html>






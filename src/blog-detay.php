<?php
ob_start(); // Docker'da hata almamak için tamponlama
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Oturumu başlat
}
require_once 'admin/includes/config.php';

// Blog ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Blog yazısını çek
$sql = "SELECT * FROM blog_posts WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$blog = mysqli_fetch_assoc($result);

// Blog bulunamadıysa ana sayfaya yönlendir
if (!$blog) {
    header("Location: blog.php");
    exit;
}

// Determine language (use cookie from lang.php, fallback to 'tr')
$currentLang = isset($langCode) ? $langCode : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'tr');

// Select title and content based on language
if ($currentLang == 'en' && !empty($blog['title_en']) && !empty($blog['content_en'])) {
    $display_title = $blog['title_en'];
    $display_content = $blog['content_en'];
} else {
    // Default to Turkish
    $display_title = $blog['title'];
    $display_content = $blog['content'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($langCode) ? htmlspecialchars($langCode) : 'tr'; ?>">
<head>
    <?php include 'includes/head-meta.php'; ?>
    <title><?php echo htmlspecialchars($display_title); ?> - ASEC</title>
    <link rel="stylesheet" href="css/blog-detay.css">
</head>
<body>
        <?php include 'header.php'; ?>

    <main>
        <div class="blog-detail">
            <div class="blog-detail-header">
                <?php if (!empty($blog['image_url'])): ?>
                    <img src="admin/<?php echo !empty($blog['image_url']) ? htmlspecialchars($blog['image_url']) : 'fotograflar/default-blog.jpg'; ?>" alt="<?php echo htmlspecialchars($display_title); ?>">
                <?php endif; ?>
                <div class="category"><?php echo htmlspecialchars(!empty($blog['category']) ? $blog['category'] : __t('blog.category.general')); ?></div>
            </div>
            
            <div class="blog-detail-content">
                <h1 class="blog-detail-title"><?php echo htmlspecialchars($display_title); ?></h1>
                
                <div class="blog-detail-meta">
                    <?php
                    // Format date with full month name using translations
                    require_once 'includes/lang.php';
                    $blogDate = strtotime($blog['created_at']);
                    $blogDay = date('d', $blogDate);
                    $blogMonthNum = (int)date('n', $blogDate);
                    $blogYear = date('Y', $blogDate);
                    $blogMonthName = isset($translations[$currentLang]['months'][$blogMonthNum]) 
                        ? $translations[$currentLang]['months'][$blogMonthNum] 
                        : date('F', $blogDate);
                    $blogFormattedDate = $blogDay . ' ' . $blogMonthName . ' ' . $blogYear;
                    ?>
                    <span><i class="far fa-calendar"></i> <?php echo $blogFormattedDate; ?></span>
                    <span><i class="far fa-user"></i> <?php echo htmlspecialchars($blog['author']); ?></span>
                </div>

                <div class="blog-detail-text">
                    <?php 
                    // HTML stil etiketlerini temizle ve içeriği düzgün biçimlendir
                    $content = $display_content;
                    $content = preg_replace('/<p style="[^"]*">/', '<p>', $content);
                    $content = strip_tags($content, '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><strong><em><a><img>');
                    echo $content;
                    ?>
                </div>

                <a href="blog.php" class="back-to-blog"><i class="fas fa-arrow-left"></i> <?php echo __t('blog.back'); ?></a>
            </div>
        </div>
    </main>

        <?php include 'footer.php'; ?>

    <script src="javascript/script.js"></script>
</body>
</html> 
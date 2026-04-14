<?php
require_once 'includes/config.php';

// Oturum kontrolü
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Ensure English columns exist
try {
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM blog_posts LIKE 'title_en'");
    if (mysqli_num_rows($columns) == 0) {
        mysqli_query($conn, "ALTER TABLE blog_posts ADD COLUMN title_en VARCHAR(255) NULL AFTER title");
    }
    $columns = mysqli_query($conn, "SHOW COLUMNS FROM blog_posts LIKE 'content_en'");
    if (mysqli_num_rows($columns) == 0) {
        mysqli_query($conn, "ALTER TABLE blog_posts ADD COLUMN content_en LONGTEXT NULL AFTER content");
    }
} catch (Exception $e) {
    // Columns might already exist
}

$title = $content = $title_en = $content_en = $image_url = $category = $author = "";
$title_err = $content_err = $image_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Başlık kontrolü
    if(empty(trim($_POST["title"]))){
        $title_err = "Lütfen başlık giriniz.";
    } else {
        $title = trim($_POST["title"]);
    }
    
    // English title (optional)
    $title_en = !empty($_POST["title_en"]) ? trim($_POST["title_en"]) : "";
    
    // Kategori kontrolü
    $category = !empty($_POST["category"]) ? trim($_POST["category"]) : "Genel";
    
    // Yazar kontrolü
    $author = !empty($_POST["author"]) ? trim($_POST["author"]) : $_SESSION["username"];
    
    // İçerik kontrolü
    if(empty(trim($_POST["content"]))){
        $content_err = "Lütfen içerik giriniz.";
    } else {
        $content = trim($_POST["content"]);
    }
    
    // English content (optional)
    $content_en = !empty($_POST["content_en"]) ? trim($_POST["content_en"]) : "";
    
    // Resim URL kontrolü (opsiyonel)
    $image_url = !empty($_POST["image_url"]) ? trim($_POST["image_url"]) : "";
    
    // Görsel yükleme işlemi
    if (isset($_FILES["image_file"]) && $_FILES["image_file"]["error"] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["image_file"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Sadece belirli dosya türlerine izin ver
        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
                $image_url = $target_file; // Veritabanına kaydedilecek dosya yolu
            } else {
                $image_err = "Dosya yüklenirken bir hata oluştu.";
            }
        } else {
            $image_err = "Sadece JPG, JPEG, PNG ve GIF dosyalarına izin verilmektedir.";
        }
    }
    
    // Hata yoksa kaydet
    if(empty($title_err) && empty($content_err)){
        $sql = "INSERT INTO blog_posts (title, title_en, category, author, content, content_en, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            mysqli_stmt_bind_param($stmt, "sssssss", $title, $title_en, $category, $author, $content, $content_en, $image_url);
            
            if(mysqli_stmt_execute($stmt)){
                header("location: blog-yonetim.php?success=1");
                exit();
            } else{
                echo "Bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Blog Yazısı - ASEC Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: #fff;
            padding: 1rem;
        }
        
        .sidebar .nav-link:hover {
            color: #007bff;
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link.active {
            color: #007bff;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        main {
            padding-top: 48px;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="#">ASEC Admin</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="logout.php">Çıkış Yap</a>
            </li>
        </ul>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home"></i>
                                Ana Sayfa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="blog-yonetim.php">
                                <i class="fas fa-blog"></i>
                                Blog
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="uyeler-yonetim.php">
                                <i class="fas fa-users"></i>
                                Üyeler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="etkinlikler-yonetim.php">
                                <i class="fas fa-calendar-alt"></i>
                                Etkinlikler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="duyurular-yonetim.php">
                                <i class="fas fa-bullhorn"></i>
                                Duyurular
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Yeni Blog Yazısı</h1>
                </div>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <!-- Language Tabs -->
                    <ul class="nav nav-tabs mb-4" id="langTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="tr-tab" data-toggle="tab" href="#tr-content" role="tab" aria-controls="tr-content" aria-selected="true">
                                <i class="fas fa-flag"></i> Türkçe
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="en-tab" data-toggle="tab" href="#en-content" role="tab" aria-controls="en-content" aria-selected="false">
                                <i class="fas fa-flag"></i> English
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="langTabContent">
                        <!-- Turkish Tab -->
                        <div class="tab-pane fade show active" id="tr-content" role="tabpanel" aria-labelledby="tr-tab">
                            <div class="form-group">
                                <label>Başlık (Türkçe) *</label>
                                <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>" required>
                                <span class="invalid-feedback"><?php echo $title_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label>İçerik (Türkçe) *</label>
                                <textarea name="content" id="summernote" class="form-control <?php echo (!empty($content_err)) ? 'is-invalid' : ''; ?>" required><?php echo htmlspecialchars($content); ?></textarea>
                                <span class="invalid-feedback"><?php echo $content_err; ?></span>
                            </div>
                        </div>

                        <!-- English Tab -->
                        <div class="tab-pane fade" id="en-content" role="tabpanel" aria-labelledby="en-tab">
                            <div class="form-group">
                                <label>Title (English)</label>
                                <input type="text" name="title_en" class="form-control" value="<?php echo htmlspecialchars($title_en); ?>" placeholder="Optional: English title">
                            </div>
                            
                            <div class="form-group">
                                <label>Content (English)</label>
                                <textarea name="content_en" id="summernote-en" class="form-control" placeholder="Optional: English content"><?php echo htmlspecialchars($content_en); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <hr class="my-4">
                    <h5 class="mb-3">Genel Bilgiler</h5>
                    
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category" class="form-control">
                            <option value="Genel" <?php echo ($category == "Genel") ? "selected" : ""; ?>>Genel</option>
                            <option value="Teknoloji" <?php echo ($category == "Teknoloji") ? "selected" : ""; ?>>Teknoloji</option>
                            <option value="Yazılım" <?php echo ($category == "Yazılım") ? "selected" : ""; ?>>Yazılım</option>
                            <option value="Robotik" <?php echo ($category == "Robotik") ? "selected" : ""; ?>>Robotik</option>
                            <option value="Yapay Zeka" <?php echo ($category == "Yapay Zeka") ? "selected" : ""; ?>>Yapay Zeka</option>
                            <option value="Etkinlik" <?php echo ($category == "Etkinlik") ? "selected" : ""; ?>>Etkinlik</option>
                            <option value="Duyuru" <?php echo ($category == "Duyuru") ? "selected" : ""; ?>>Duyuru</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Yazar</label>
                        <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($author); ?>" placeholder="Varsayılan: Giriş yapan kullanıcı">
                    </div>
                    
                    <div class="form-group">
                        <label>Görsel URL (Opsiyonel)</label>
                        <input type="text" name="image_url" class="form-control" value="<?php echo htmlspecialchars($image_url); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Görsel Yükle (Opsiyonel)</label>
                        <input type="file" name="image_file" class="form-control-file">
                        <?php if (!empty($image_err)): ?>
                            <span class="text-danger"><?php echo $image_err; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Kaydet
                        </button>
                        <a href="blog-yonetim.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Geri
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Turkish editor
            $('#summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                lang: 'tr-TR'
            });
            
            // English editor
            $('#summernote-en').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ],
                lang: 'en-US'
            });
        });
    </script>
</body>
</html>
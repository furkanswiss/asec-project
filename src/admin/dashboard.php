<?php
require_once 'includes/config.php';

// Oturum kontrolü
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASEC Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
     <link rel="icon" href="../images/gallery/try.png" type="image/png">
    <link rel="shortcut icon" href="../images/gallery/try.png" type="image/png">
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
        
        .submenu {
            padding-left: 20px;
            background-color: rgba(0,0,0,0.2);
        }
        .submenu .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }
        .yonetim-chevron {
            margin-top: 3px;
        }
        .yonetim-submenu.show .yonetim-chevron {
            transform: rotate(180deg);
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
            <?php include 'sidebar.php'; ?>

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Kontrol Paneli</h1>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Blog Yazıları</h5>
                                <?php
                                $blog_count_sql = "SELECT COUNT(*) as count FROM blog_posts";
                                $blog_count_result = mysqli_query($conn, $blog_count_sql);
                                $blog_count = mysqli_fetch_assoc($blog_count_result)['count'];
                                ?>
                                <p class="card-text h2"><?php echo $blog_count; ?></p>
                                <a href="blog-yonetim.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Üyeler</h5>
                                <?php
                                $uye_count_sql = "SELECT COUNT(*) as count FROM users";
                                $uye_count_result = mysqli_query($conn, $uye_count_sql);
                                $uye_count = mysqli_fetch_assoc($uye_count_result)['count'];
                                ?>
                                <p class="card-text h2"><?php echo $uye_count; ?></p>
                                <a href="uyeler-yonetim.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Etkinlikler</h5>
                                <?php
                                $etkinlik_count_sql = "SELECT COUNT(*) as count FROM etkinlikler";
                                $etkinlik_count_result = mysqli_query($conn, $etkinlik_count_sql);
                                $etkinlik_count = mysqli_fetch_assoc($etkinlik_count_result)['count'];
                                ?>
                                <p class="card-text h2"><?php echo $etkinlik_count; ?></p>
                                <a href="etkinlikler-yonetim.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Duyurular</h5>
                                <?php
                                $duyuru_count_sql = "SELECT COUNT(*) as count FROM duyurular";
                                $duyuru_count_result = mysqli_query($conn, $duyuru_count_sql);
                                $duyuru_count = mysqli_fetch_assoc($duyuru_count_result)['count'];
                                ?>
                                <p class="card-text h2"><?php echo $duyuru_count; ?></p>
                                <a href="duyurular-yonetim.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-danger h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">İlanlar</h5>
                                <?php
                                $ilan_count_sql = "SELECT COUNT(*) as count FROM ilanlar";
                                $ilan_count_result = mysqli_query($conn, $ilan_count_sql);
                                $ilan_count = mysqli_fetch_assoc($ilan_count_result)['count'];
                                ?>
                                <p class="card-text h2"><?php echo $ilan_count; ?></p>
                                <a href="ilanlar-yonetim.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Kurumsal İstekler</h5>
                                <?php
                                // Use PDO for corporate_requests table
                                require_once '../db.php';
                                $corporate_requests_count = $pdo->query("SELECT COUNT(*) FROM corporate_requests WHERE status = 'pending'")->fetchColumn();
                                ?>
                                <p class="card-text h2"><?php echo $corporate_requests_count; ?></p>
                                <a href="kurumsal-istekler.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-secondary h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Bireysel İstekler</h5>
                                <?php
                                // Use PDO for individual_requests table
                                try {
                                    $individual_requests_count = $pdo->query("SELECT COUNT(*) FROM individual_requests WHERE status = 'pending'")->fetchColumn();
                                } catch (PDOException $e) {
                                    $individual_requests_count = 0;
                                }
                                ?>
                                <p class="card-text h2"><?php echo $individual_requests_count; ?></p>
                                <a href="bireysel-istekler.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white h-100" style="background-color: #6c757d;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">İlan İstekleri</h5>
                                <?php
                                // Use PDO for corporate_ilan_requests table
                                try {
                                    // Ensure table exists
                                    $pdo->exec('CREATE TABLE IF NOT EXISTS corporate_ilan_requests (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        corporate_user_id INT NOT NULL,
                                        baslik VARCHAR(255) NOT NULL,
                                        icerik TEXT NOT NULL,
                                        kategori VARCHAR(100) NOT NULL,
                                        tarih DATE NOT NULL,
                                        link VARCHAR(500),
                                        sirket VARCHAR(255),
                                        lokasyon VARCHAR(255),
                                        son_basvuru DATE,
                                        status ENUM("pending", "approved", "rejected") DEFAULT "pending",
                                        admin_notes TEXT,
                                        reviewed_by INT,
                                        reviewed_at TIMESTAMP NULL,
                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                        INDEX idx_corporate_user_id (corporate_user_id),
                                        INDEX idx_status (status),
                                        INDEX idx_kategori (kategori)
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                                    $ilan_requests_count = $pdo->query("SELECT COUNT(*) FROM corporate_ilan_requests WHERE status = 'pending'")->fetchColumn();
                                } catch (PDOException $e) {
                                    $ilan_requests_count = 0;
                                }
                                ?>
                                <p class="card-text h2"><?php echo $ilan_requests_count; ?></p>
                                <a href="ilan-istekleri.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white h-100" style="background-color: #9370db;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">Yönetim Kurulu</h5>
                                <?php
                                // Use PDO for board_members table
                                try {
                                    // Ensure table exists
                                    $pdo->exec('CREATE TABLE IF NOT EXISTS board_members (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        name VARCHAR(255) NOT NULL,
                                        position VARCHAR(255) NOT NULL,
                                        profileImage VARCHAR(500),
                                        linkedinUrl VARCHAR(500),
                                        githubUrl VARCHAR(500),
                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
                                    $board_members_count = $pdo->query("SELECT COUNT(*) FROM board_members")->fetchColumn();
                                } catch (PDOException $e) {
                                    $board_members_count = 0;
                                }
                                ?>
                                <p class="card-text h2"><?php echo $board_members_count; ?></p>
                                <a href="board-yonetim.php" class="text-white mt-auto">Görüntüle <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
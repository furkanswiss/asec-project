<?php
// Load messages helper for unread count
if (file_exists(__DIR__ . '/../includes/messages.php')) {
    require_once __DIR__ . '/../includes/messages.php';
}
// Initialize unread count for corporate users
$unread_count = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'corporate') {
    if (!isset($pdo)) {
        require_once __DIR__ . '/includes/config.php';
    }
    $unread_count = getUnreadMessageCount($pdo);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASEC Kurumsal Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: #f5f6fa;
            padding-top: 64px;
        }
        .navbar {
            z-index: 200;
            min-height: 64px;
        }
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
        }
        .sidebar {
            position: fixed;
            top: 64px;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 64px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #fff;
            padding: 1rem;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover {
            color: #9370db;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link.active {
            color: #9370db;
            background-color: rgba(147, 112, 219, 0.2);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .container-fluid, main.container-fluid {
            padding-left: 0;
            padding-right: 0;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
            min-height: calc(100vh - 64px);
            transition: margin-left 0.3s ease-in-out;
        }
        
        /* Center all page headers */
        .main-content h1,
        .main-content .h1,
        .main-content .h2,
        .main-content .h3 {
            text-align: center !important;
            width: 100%;
            margin: 0 auto 1.5rem auto;
        }
        .main-content .d-flex.justify-content-between,
        .main-content .d-flex.flex-wrap,
        .main-content .d-flex.align-items-center {
            justify-content: center !important;
        }
        .main-content .border-bottom {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }
        .main-content .border-bottom > * {
            margin: 0 auto;
        }
        
        /* Make navbar brand (main page button) more visible */
        .navbar-brand {
            padding: 0.5rem 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            background-color: rgba(147, 112, 219, 0.2);
            border-radius: 5px;
            transition: all 0.3s ease;
            color: #fff !important;
            border: 1px solid rgba(147, 112, 219, 0.5);
        }
        .navbar-brand:hover {
            background-color: rgba(147, 112, 219, 0.4);
            border-color: rgba(147, 112, 219, 0.8);
            transform: scale(1.05);
            color: #fff !important;
            text-decoration: none;
        }
        
        /* Make logout button more visible */
        .navbar .nav-link[href="logout.php"],
        .navbar .nav-link[href*="logout"] {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            border-radius: 5px;
            padding: 0.5rem 1rem !important;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #fff !important;
            margin-left: 0.5rem;
        }
        .navbar .nav-link[href="logout.php"]:hover,
        .navbar .nav-link[href*="logout"]:hover {
            background-color: rgba(220, 53, 69, 0.4);
            border-color: rgba(220, 53, 69, 0.8);
            transform: scale(1.05);
            color: #fff !important;
        }
        
        /* Mobile Styles */
        @media (max-width: 768px) {
            body {
                padding-top: 64px;
            }
            .navbar-brand {
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
            }
            .navbar-nav {
                flex-direction: row;
            }
            .navbar-nav .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
                min-height: 44px; /* Touch-friendly size */
                display: flex;
                align-items: center;
            }
            .navbar .nav-link[href="logout.php"],
            .navbar .nav-link[href*="logout"] {
                padding: 0.5rem 0.75rem !important;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .sidebar {
                position: fixed;
                top: 64px;
                left: 0;
                width: 260px;
                height: calc(100vh - 64px);
                transform: translateX(-100%);
                z-index: 150;
                box-shadow: 2px 0 10px rgba(0,0,0,0.3);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 64px;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 140;
                animation: fadeIn 0.3s ease;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .sidebar-overlay.show {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border: 1px solid #dee2e6;
                border-radius: 4px;
            }
            .card {
                margin-bottom: 15px;
                border-radius: 8px;
            }
            .btn-group {
                flex-wrap: wrap;
                width: 100%;
            }
            .btn-group .btn {
                margin-bottom: 5px;
                min-height: 44px; /* Touch-friendly */
            }
            .form-control-lg {
                font-size: 16px; /* Prevents zoom on iOS */
                min-height: 48px;
                padding: 12px 16px;
            }
            .btn {
                min-height: 44px; /* Touch-friendly buttons */
                padding: 10px 20px;
                font-size: 1rem;
            }
            .btn-sm {
                min-height: 36px;
                padding: 6px 12px;
            }
            .btn-lg {
                min-height: 48px;
                padding: 12px 24px;
            }
        }
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 0.8rem;
                padding: 0.35rem 0.6rem;
            }
            .navbar-nav .nav-link {
                font-size: 0.75rem;
                padding: 0.4rem 0.5rem;
                min-height: 44px;
            }
            .navbar .nav-link[href="logout.php"],
            .navbar .nav-link[href*="logout"] {
                padding: 0.4rem 0.6rem !important;
                font-size: 0.75rem;
            }
            .main-content {
                padding: 10px;
            }
            .h2, .h3 {
                font-size: 1.5rem;
            }
            .card-body {
                padding: 1rem;
            }
            .form-group {
                margin-bottom: 1rem;
            }
            .table {
                font-size: 0.875rem;
            }
            .table td, .table th {
                padding: 0.5rem;
            }
            .badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
            .alert {
                font-size: 0.875rem;
                padding: 12px;
            }
            .card-title {
                font-size: 1.1rem;
            }
        }
        
        /* Touch-friendly improvements */
        @media (max-width: 768px) {
            a, button, .btn {
                -webkit-tap-highlight-color: rgba(147, 112, 219, 0.3);
                touch-action: manipulation;
            }
            input, select, textarea {
                font-size: 16px !important; /* Prevents zoom on iOS */
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark fixed-top bg-dark flex-md-nowrap p-0 shadow">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-md-none border-0 mr-2" type="button" id="sidebarToggle" aria-label="Toggle navigation" style="padding: 0.25rem 0.5rem;">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand px-2 px-md-3" href="dashboard.php">
                <i class="fas fa-building d-md-none"></i>
                <span class="d-none d-sm-inline">ASEC</span>
                <span class="d-none d-md-inline">ASEC Kurumsal Panel</span>
            </a>
        </div>
        <ul class="navbar-nav px-2 px-md-3 d-flex flex-row align-items-center ml-auto">
            <li class="nav-item text-nowrap mr-2 mr-md-3 d-none d-md-block">
                <span class="nav-link text-white mb-0" style="padding: 0.5rem 0;">
                    <i class="fas fa-user-circle mr-1"></i>
                    <?php echo htmlspecialchars(mb_substr($_SESSION['user_name'] ?? 'Kurumsal Kullanıcı', 0, 20)); ?>
                </span>
            </li>
            <li class="nav-item text-nowrap mr-2">
                <a class="nav-link" href="../mailbox.php" title="Mesajlar" style="position: relative; background-color: rgba(40, 167, 69, 0.2); border: 1px solid rgba(40, 167, 69, 0.5); border-radius: 5px; padding: 0.5rem 1rem !important; font-weight: 600; transition: all 0.3s ease; color: #fff !important;" onmouseover="this.style.backgroundColor='rgba(40, 167, 69, 0.4)'; this.style.borderColor='rgba(40, 167, 69, 0.8)'; this.style.transform='scale(1.05)';" onmouseout="this.style.backgroundColor='rgba(40, 167, 69, 0.2)'; this.style.borderColor='rgba(40, 167, 69, 0.5)'; this.style.transform='scale(1)';">
                    <i class="fas fa-envelope mr-1"></i>
                    <span class="d-none d-md-inline">Mesajlar</span>
                    <span class="d-md-none">Mesaj</span>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px;"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item text-nowrap mr-2">
                <a class="nav-link" href="../index.php" title="Ana Siteye Git" style="background-color: rgba(147, 112, 219, 0.2); border: 1px solid rgba(147, 112, 219, 0.5); border-radius: 5px; padding: 0.5rem 1rem !important; font-weight: 600; transition: all 0.3s ease; color: #fff !important;" onmouseover="this.style.backgroundColor='rgba(147, 112, 219, 0.4)'; this.style.borderColor='rgba(147, 112, 219, 0.8)'; this.style.transform='scale(1.05)';" onmouseout="this.style.backgroundColor='rgba(147, 112, 219, 0.2)'; this.style.borderColor='rgba(147, 112, 219, 0.5)'; this.style.transform='scale(1)';">
                    <i class="fas fa-globe mr-1"></i>
                    <span class="d-none d-md-inline">Ana Siteye Git</span>
                    <span class="d-md-none">Site</span>
                </a>
            </li>
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="logout.php" title="Çıkış Yap">
                    <i class="fas fa-sign-out-alt d-md-none"></i>
                    <span class="d-none d-md-inline">Çıkış Yap</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <script>
        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebarMenu');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            function toggleSidebar() {
                sidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            }
            
            function closeSidebar() {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }
            
            // Close sidebar when clicking on a link (mobile)
            if (sidebar) {
                const sidebarLinks = sidebar.querySelectorAll('.nav-link');
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            closeSidebar();
                        }
                    });
                });
            }
            
            // Close sidebar on window resize if switching to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeSidebar();
                }
            });
        });
    </script>


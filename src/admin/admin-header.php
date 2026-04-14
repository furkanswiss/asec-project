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
        body {
            background: #f5f6fa;
        }
        .navbar {
            z-index: 200;
        }
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
        }
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
        .container, main {
            margin-left: 240px;
            padding-top: 70px;
        }
        @media (max-width: 768px) {
            .container, main {
                margin-left: 0;
                padding-top: 120px;
            }
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                padding: 0;
            }
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
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="dashboard.php">ASEC Admin</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse" data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="logout.php">Çıkış Yap</a>
            </li>
        </ul>
    </nav>

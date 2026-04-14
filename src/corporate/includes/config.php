<?php
// 1. DOCKER UYUMLU BAŞLANGIÇ
ob_start();

// 2. SESSION VE DB BAĞLANTISI
// 'corporate' klasöründe olduğumuz için bir üst dizindeki db.php'yi çağırıyoruz.
require_once '../db.php'; 

// 3. YETKİ KONTROLÜ
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['user_type'] !== 'corporate'){
    header("location: ../login.php");
    exit;
}
// Corporate config.php – Session management for corporate users
session_start();

// Check if user is logged in and is a corporate user
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'corporate'){
    header("location: ../login.php");
    exit;
}

// Ensure user_id is set
if(!isset($_SESSION['user_id'])){
    header("location: ../login.php");
    exit;
}

// Database connection using PDO
// require_once '../db.php';
?>
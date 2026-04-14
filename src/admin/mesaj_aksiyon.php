<?php
// mesaj_aksiyon.php: okundu, sil, yıldız işlemleri için
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Veritabanı bağlantısını db.php'den al
require_once '../db.php';

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0 || !$action) {
    echo 'Geçersiz istek.';
    exit;
}

if ($action === 'okundu') {
    $pdo->prepare('UPDATE mesajlar SET okundu=1 WHERE id=?')->execute([$id]);
    echo 'ok';
} elseif ($action === 'sil') {
    $pdo->prepare('DELETE FROM mesajlar WHERE id=?')->execute([$id]);
    echo 'silindi';
} elseif ($action === 'yildiz') {
    $pdo->prepare('UPDATE mesajlar SET yildiz=1-yildiz WHERE id=?')->execute([$id]);
    echo 'yildiz';
} else {
    echo 'Bilinmeyen aksiyon';
}

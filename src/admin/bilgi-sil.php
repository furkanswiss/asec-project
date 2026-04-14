<?php
// Bilgilendirme Tablosu Sil
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    // Delete rows first (in case foreign key doesn't exist)
    $pdo->prepare('DELETE FROM info_rows WHERE table_id=?')->execute([$id]);
    // Then delete the table
    $pdo->prepare('DELETE FROM info_tables WHERE id=?')->execute([$id]);
}
header('Location: bilgi-yonetim.php');
exit;
?>


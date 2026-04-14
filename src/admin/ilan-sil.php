<?php
// İlan Sil
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare('DELETE FROM ilanlar WHERE id=?')->execute([$id]);
}
header('Location: ilanlar-yonetim.php');
exit;
?>





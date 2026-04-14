<?php
// Duyuru Sil
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare('DELETE FROM duyurular WHERE id=?')->execute([$id]);
}
header('Location: duyurular-yonetim.php');
exit;
?>

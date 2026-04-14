<?php
// Önemli Bilgi Sil
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    // Get image filename before deleting
    $stmt = $pdo->prepare('SELECT resim FROM onemli_bilgiler WHERE id=?');
    $stmt->execute([$id]);
    $bilgi = $stmt->fetch();
    
    // Delete the record
    $pdo->prepare('DELETE FROM onemli_bilgiler WHERE id=?')->execute([$id]);
    
    // Delete image file if exists
    if ($bilgi && !empty($bilgi['resim'])) {
        $imagePath = '../uploads/onemli-bilgiler/' . $bilgi['resim'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}
header('Location: onemli-bilgiler-yonetim.php');
exit;
?>


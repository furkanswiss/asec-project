<?php
// Etkinlik fotoğrafı silme işlemi
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foto_id = intval($_POST['foto_id'] ?? 0);
    $etkinlik_id = intval($_POST['etkinlik_id'] ?? 0);
    if ($foto_id > 0 && $etkinlik_id > 0) {
        $stmt = $pdo->prepare('SELECT dosya_yolu FROM etkinlik_fotolar WHERE id=? AND etkinlik_id=?');
        $stmt->execute([$foto_id, $etkinlik_id]);
        $foto = $stmt->fetch();
        if ($foto) {
            $dosya = __DIR__ . '/../' . $foto['dosya_yolu'];
            if (file_exists($dosya)) {
                unlink($dosya);
            }
            $pdo->prepare('DELETE FROM etkinlik_fotolar WHERE id=?')->execute([$foto_id]);
        }
    }
    header('Location: etkinlik-duzenle.php?id=' . $etkinlik_id);
    exit;
}
?>

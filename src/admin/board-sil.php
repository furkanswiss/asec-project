<?php
// Board Member Sil
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
require_once '../db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    // Get member data to delete image
    $stmt = $pdo->prepare('SELECT * FROM board_members WHERE id=?');
    $stmt->execute([$id]);
    $member = $stmt->fetch();
    
    if ($member) {
        // Delete profile image if exists
        if (!empty($member['profileImage']) && file_exists(__DIR__ . '/../' . $member['profileImage'])) {
            @unlink(__DIR__ . '/../' . $member['profileImage']);
        }
        
        // Delete from database
        $pdo->prepare('DELETE FROM board_members WHERE id=?')->execute([$id]);
    }
}
header('Location: board-yonetim.php');
exit;
?>


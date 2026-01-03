<?php
require_once __DIR__ . "/config/db.php";
if (empty($_SESSION['user']) || $_SESSION['user']['vloga'] != 2) {
    echo "Dostop zavrnjen."; exit;
}
$id = (int)($_GET['id'] ?? 0);
$pdo->prepare("DELETE FROM objava WHERE objava_id=?")->execute([$id]);
header('Location: admin.php');
exit;

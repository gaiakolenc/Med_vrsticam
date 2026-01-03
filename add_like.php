<?php
session_start();
require_once __DIR__ . "/config/db.php";

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$objava_id = (int)($_GET['objava_id'] ?? 0);
if (!$objava_id) {
    header('Location: index.php');
    exit;
}

// preveri, ali je uporabnik že všečkal
$stmt = $pdo->prepare("SELECT * FROM vsecek WHERE uporabnik_id=? AND objava_id=?");
$stmt->execute([$_SESSION['user']['user_id'], $objava_id]);
$like = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$like) {
    // dodaj všeček
    $stmt = $pdo->prepare("INSERT INTO vsecek (datum_vsecka, uporabnik_id, objava_id) VALUES (CURDATE(), ?, ?)");
    $stmt->execute([$_SESSION['user']['user_id'], $objava_id]);
}

header('Location: post.php?id='.$objava_id);
exit;

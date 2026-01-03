<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['user']) && isset($_GET['id'])) {
    $objava_id = (int)$_GET['id'];
    $uporabnik_id = $_SESSION['user']['user_id'];

    $check = $pdo->prepare("SELECT 1 FROM vsecek WHERE uporabnik_id = ? AND objava_id = ?");
    $check->execute([$uporabnik_id, $objava_id]);

    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO vsecek (uporabnik_id, objava_id) VALUES (?, ?)");
        $stmt->execute([$uporabnik_id, $objava_id]);
    }
    header("Location: post.php?id=" . $objava_id);
} else {
    header("Location: index.php");
}
exit;
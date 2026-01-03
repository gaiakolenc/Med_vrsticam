<?php
session_start();
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user'])) {
    $ob_id = (int)$_POST['objava_id'];
    $u_id = $_SESSION['user']['user_id'];

    // Preveri, če je že všečkano
    $stmt = $pdo->prepare("SELECT * FROM vsecki WHERE objava_id = ? AND uporabnik_id = ?");
    $stmt->execute([$ob_id, $u_id]);

    if ($stmt->fetch()) {
        // Odstrani všeček
        $del = $pdo->prepare("DELETE FROM vsecki WHERE objava_id = ? AND uporabnik_id = ?");
        $del->execute([$ob_id, $u_id]);
    } else {
        // Dodaj všeček
        $ins = $pdo->prepare("INSERT INTO vsecki (objava_id, uporabnik_id) VALUES (?, ?)");
        $ins->execute([$ob_id, $u_id]);
    }
    header("Location: post.php?id=" . $ob_id);
}
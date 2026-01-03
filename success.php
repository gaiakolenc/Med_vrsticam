<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['user_id'];
    
    // Posodobimo v bazi
    $stmt = $pdo->prepare("UPDATE uporabnik SET is_premium = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Posodobimo še sejo, da sprememba velja takoj
    $_SESSION['user']['is_premium'] = 1;
    
    echo "<h1>Uspeh! Zdaj si Premium član.</h1>";
    echo "<a href='index.php'>Nazaj na zgodbe</a>";
}
?>
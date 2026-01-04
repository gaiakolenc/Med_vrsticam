<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['user_id'];

    // 1. Posodobitev v bazi
    $stmt = $pdo->prepare("UPDATE uporabnik SET is_premium = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // 2. Takojšnja osvežitev seje
    $_SESSION['user']['is_premium'] = 1;

    // 3. Preusmeritev nazaj na domov s sporočilom o uspehu
    header("Location: index.php?status=success");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
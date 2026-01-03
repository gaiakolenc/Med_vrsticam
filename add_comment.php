<?php
session_start(); // NUJNO: Brez tega $_SESSION ne deluje!
require_once __DIR__ . '/config/db.php';

// Preveri, če je uporabnik prijavljen
if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Preveri, če so podatki sploh prišli preko POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['vsebina']) && !empty($_POST['objava_id'])) {
    
    $vsebina = trim($_POST['vsebina']);
    $user_id = $_SESSION['user']['user_id'];
    $objava_id = (int)$_POST['objava_id'];

    try {
        // Uporabimo NOW(), da dobimo točen čas objave
        $stmt = $pdo->prepare("
            INSERT INTO komentar (vsebina, datum_komentarja, uporabnik_id, objava_id)
            VALUES (?, NOW(), ?, ?)
        ");
        
        $stmt->execute([$vsebina, $user_id, $objava_id]);
        
        // Uspeh: vrni se na objavo
        header("Location: post.php?id=" . $objava_id);
        exit;
        
    } catch (PDOException $e) {
        // V primeru napake izpiši, kaj je narobe (za razvijalca)
        die("Napaka pri shranjevanju: " . $e->getMessage());
    }

} else {
    // Če so podatki prazni, se samo vrni nazaj
    $objava_id = isset($_POST['objava_id']) ? (int)$_POST['objava_id'] : 0;
    header("Location: post.php?id=" . $objava_id);
    exit;
}
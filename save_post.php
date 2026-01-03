<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Preveri prijavo
if (empty($_SESSION['user'])) {
    die("Nimate pooblastil za to dejanje.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naslov = $_POST['naslov'] ?? '';
    $vsebina = $_POST['vsebina'] ?? '';
    $kategorija_id = (int)($_POST['kategorija_id'] ?? 8); // Privzeto 8 (Splošno)
    $user_id = $_SESSION['user']['user_id'];
    $ime_slike = null;

    // LOGIKA ZA SLIKO
    if (!empty($_FILES['slika']['name'])) {
        $target_dir = "uploads/";
        
        // Ustvari mapo, če ne obstaja
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ekstenzija = pathinfo($_FILES["slika"]["name"], PATHINFO_EXTENSION);
        $ime_slike = time() . "_" . uniqid() . "." . $ekstenzija;
        $target_file = $target_dir . $ime_slike;

        if (!move_uploaded_file($_FILES["slika"]["tmp_name"], $target_file)) {
            $ime_slike = null; // Če nalaganje ne uspe, ostane null
        }
    }

    try {
        $sql = "INSERT INTO objava (naslov, vsebina, kategorija_id, uporabnik_id, slika, datum_objave) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$naslov, $vsebina, $kategorija_id, $user_id, $ime_slike]);

        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        die("Napaka pri shranjevanju: " . $e->getMessage());
    }
}
<?php
session_start();
require_once __DIR__ . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['user'])) {
    $user_id = $_SESSION['user']['user_id'];
    $ime = $_POST['ime'] ?? '';
    $email = $_POST['email'] ?? '';
    $biografija = $_POST['biografija'] ?? '';
    
    // Pridobimo trenutne podatke
    $stmt = $pdo->prepare("SELECT slika FROM uporabnik WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();
    $ime_slike = $current_user['slika'];

    // Obdelava slike
    if (!empty($_FILES['slika']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true); // Ustvari mapo, če ne obstaja
        
        $ekstenzija = pathinfo($_FILES["slika"]["name"], PATHINFO_EXTENSION);
        $novo_ime = "avatar_" . $user_id . "_" . time() . "." . $ekstenzija;
        
        if (move_uploaded_file($_FILES["slika"]["tmp_name"], $target_dir . $novo_ime)) {
            $ime_slike = $novo_ime;
        }
    }

    try {
        $sql = "UPDATE uporabnik SET ime = ?, email = ?, biografija = ?, slika = ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$ime, $email, $biografija, $ime_slike, $user_id]);

        // OSVEŽIMO SEJO, da se spremembe takoj poznajo povsod
        $_SESSION['user']['ime'] = $ime;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['slika'] = $ime_slike;

        header("Location: profile.php?success=1");
        exit;
    } catch (PDOException $e) {
        die("Napaka: " . $e->getMessage());
    }
}
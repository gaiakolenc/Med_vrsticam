<?php
require_once __DIR__ . "/config/db.php";

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$naslov = trim($_POST['naslov'] ?? '');
$vsebina = trim($_POST['vsebina'] ?? '');
$kategorija_id = !empty($_POST['kategorija_id']) ? (int)$_POST['kategorija_id'] : null;
$uporabnik_id = $_SESSION['user']['user_id'];
$datum = date("Y-m-d H:i:s");
$slika_ime = NULL;

// 1. Obdelava slike
if (isset($_FILES['post_slika']) && $_FILES['post_slika']['error'] === UPLOAD_ERR_OK) {
    $mapa = __DIR__ . "/uploads/";
    $koncnica = strtolower(pathinfo($_FILES["post_slika"]["name"], PATHINFO_EXTENSION));
    $novo_ime = uniqid('post_') . '.' . $koncnica;
    $pot = $mapa . $novo_ime;

    $dovoljeni = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($koncnica, $dovoljeni) && $_FILES["post_slika"]["size"] < 2000000) {
        if (move_uploaded_file($_FILES["post_slika"]["tmp_name"], $pot)) {
            $slika_ime = $novo_ime;
        }
    }
}

// 2. Shranjevanje v bazo
if ($naslov && $vsebina) {
    try {
        $stmt = $pdo->prepare("INSERT INTO objava (naslov, vsebina, datum_objave, uporabnik_id, kategorija_id, slika) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$naslov, $vsebina, $datum, $uporabnik_id, $kategorija_id, $slika_ime]);
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        die("Napaka pri shranjevanju: " . $e->getMessage());
    }
}
?>
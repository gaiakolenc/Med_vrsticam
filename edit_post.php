<?php
require_once __DIR__ . "/config/db.php";

if (empty($_SESSION['user']) || $_SESSION['user']['vloga_id'] != 2) {
    die("Dostop zavrnjen.");
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM objava WHERE objava_id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) die("Objava ne obstaja.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naslov = $_POST['naslov'];
    $vsebina = $_POST['vsebina'];
    $kat = (int)$_POST['kategorija_id'];

    $pdo->prepare("UPDATE objava SET naslov=?, vsebina=?, kategorija_id=? WHERE objava_id=?")
        ->execute([$naslov, $vsebina, $kat, $id]);
    header("Location: admin.php");
    exit;
}

$cats = $pdo->query("SELECT * FROM kategorija")->fetchAll();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Uredi objavo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Uredi objavo</h2>
    <form method="post">
        <label>Naslov</label>
        <input type="text" name="naslov" value="<?= htmlspecialchars($post['naslov']) ?>" required>
        
        <label>Kategorija</label>
        <select name="kategorija_id">
            <?php foreach($cats as $c): ?>
                <option value="<?= $c['kategorija_id'] ?>" <?= $c['kategorija_id'] == $post['kategorija_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['naziv_kategorije']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Vsebina</label>
        <textarea name="vsebina" rows="10" required><?= htmlspecialchars($post['vsebina']) ?></textarea>
        
        <button type="submit">Shrani spremembe</button>
    </form>
    <a href="admin.php">Prekliči</a>
</div>
</body>
</html>
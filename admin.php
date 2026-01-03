<?php
require_once __DIR__ . "/config/db.php";

// Preveri, če je admin (vloga_id = 2)
if (empty($_SESSION['user']) || $_SESSION['user']['vloga_id'] != 2) {
    die("Dostop zavrnjen. Niste administrator.");
}

$posts = $pdo->query("SELECT o.objava_id, o.naslov, o.datum_objave, u.ime FROM objava o JOIN uporabnik u ON o.uporabnik_id = u.user_id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Admin Konzola</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width: 800px;">
    <h1>Admin nadzorna plošča</h1>
    <a href="index.php">← Domov</a>
    <hr>
    <table border="1" style="width:100%; border-collapse: collapse; margin-top: 20px;">
        <tr>
            <th>Naslov</th>
            <th>Avtor</th>
            <th>Akcije</th>
        </tr>
        <?php foreach ($posts as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['naslov']) ?></td>
            <td><?= htmlspecialchars($p['ime']) ?></td>
            <td>
                <a href="edit_post.php?id=<?= $p['objava_id'] ?>">Uredi</a> | 
                <a href="delete_post.php?id=<?= $p['objava_id'] ?>" onclick="return confirm('Izbrišem?')">Izbriši</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
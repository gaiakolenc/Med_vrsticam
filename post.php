<?php
session_start();
require_once __DIR__ . '/config/db.php';

// POPRAVEK: Pravilen dostop do $_GET parametra
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user']['user_id'] ?? null;

// 1. Podatki o objavi
$stmt = $pdo->prepare("SELECT o.*, u.ime AS avtor, k.naziv_kategorije,
                       (SELECT COUNT(*) FROM vsecki WHERE objava_id = o.objava_id) as st_vseckov
                       FROM objava o 
                       JOIN uporabnik u ON o.uporabnik_id = u.user_id 
                       JOIN kategorija k ON o.kategorija_id = k.kategorija_id 
                       WHERE o.objava_id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();

// Če objava ne obstaja, preusmeri na index
if (!$post) {
    header("Location: index.php");
    exit;
}

// 2. Preverjanje, če je uporabnik že všečkal
$ze_vseckal = false;
if ($user_id) {
    $check_v = $pdo->prepare("SELECT 1 FROM vsecki WHERE objava_id = ? AND uporabnik_id = ?");
    $check_v->execute([$id, $user_id]);
    $ze_vseckal = (bool)$check_v->fetch();
}

// 3. Pridobivanje komentarjev
$stmt_k = $pdo->prepare("SELECT k.*, u.ime AS avtor_komentarja 
                         FROM komentar k 
                         JOIN uporabnik u ON k.uporabnik_id = u.user_id 
                         WHERE k.objava_id = ? 
                         ORDER BY k.datum_komentarja DESC");
$stmt_k->execute([$id]);
$komentarji = $stmt_k->fetchAll();

// 4. QR koda za deljenje
$current_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($current_url);
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['naslov']) ?> | Med vrsticami</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:italic,wght@0,700;1,700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-coral: #FF6B6B;
            --dark-text: #2D3436;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #fcfcfc;
            color: var(--dark-text);
        }

        /* POSEBNA NAVIGACIJA: LOGO SREDI, PDF DESNO */
        .post-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 80px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 40px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .nav-left { position: absolute; left: 40px; }
        .nav-right { position: absolute; right: 40px; }

        .logo-center {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 4px;
            text-transform: uppercase;
            text-decoration: none;
            color: var(--dark-text);
        }
        .logo-center span { color: var(--primary-coral); }

        .post-container {
            background: white;
            padding: 60px;
            border-radius: 40px;
            margin: 120px auto 50px auto;
            box-shadow: 0 20px 50px rgba(0,0,0,0.03);
            max-width: 850px;
        }

        .interaction-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 40px 0;
            padding: 20px 30px;
            background: #fdfdfd;
            border: 1px solid #FFF9F2;
            border-radius: 30px;
        }

        .like-btn {
            background: <?= $ze_vseckal ? '#FF6B6B' : 'white' ?>;
            color: <?= $ze_vseckal ? 'white' : '#FF6B6B' ?>;
            border: 2px solid #FF6B6B;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .qr-card {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .comment-textarea {
            width: 100%;
            border: 2px solid #FFF9F2;
            background: #FFF9F2;
            border-radius: 25px;
            padding: 20px;
            font-family: inherit;
            resize: none;
            box-sizing: border-box;
        }

        .btn-aesthetic {
            padding: 12px 25px;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-block;
            text-decoration: none;
            border: none;
        }

        @media print {
            .no-print { display: none !important; }
            .post-container { box-shadow: none; padding: 0; margin-top: 0; width: 100%; }
            body { background: white; }
        }
    </style>
</head>
<body>

<nav class="post-nav no-print">
    <div class="nav-left">
        <a href="index.php" class="btn-aesthetic" style="background: #f5f5f5; color: #333;">← Nazaj</a>
    </div>

    <a href="index.php" class="logo-center">
        Med vrsticami<span>.</span>
    </a>

    <div class="nav-right">
        <button onclick="window.print()" class="btn-aesthetic" style="background: var(--dark-text); color: white;">
            Shrani PDF 📄
        </button>
    </div>
</nav>

<div class="container">
    <article class="post-container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span class="category-badge" style="background: #4ECDC4; color: white; padding: 5px 15px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                <?= htmlspecialchars($post['naziv_kategorije']) ?>
            </span>
            <h1 style="font-family: 'Playfair Display'; font-size: clamp(32px, 5vw, 52px); margin: 25px 0 15px 0; line-height: 1.1;">
                <?= htmlspecialchars($post['naslov']) ?>
            </h1>
            <div style="color: #A0A0A0; letter-spacing: 1px; text-transform: uppercase; font-size: 12px; font-weight: 600;">
                Zapisal/a <?= htmlspecialchars($post['avtor']) ?> • <?= date('d. M Y', strtotime($post['datum_objave'])) ?>
            </div>
        </div>

        <?php if (!empty($post['slika'])): ?>
            <img src="uploads/<?= htmlspecialchars($post['slika']) ?>" style="width: 100%; border-radius: 35px; margin-bottom: 50px; box-shadow: 0 15px 40px rgba(0,0,0,0.06);">
        <?php endif; ?>

        <div style="font-size: 20px; line-height: 1.9; color: #2D3436; font-family: 'Georgia', serif; max-width: 750px; margin: 0 auto;">
            <?= nl2br(htmlspecialchars($post['vsebina'])) ?>
        </div>

        <div class="interaction-bar no-print">
            <div style="display: flex; align-items: center; gap: 15px;">
                <form action="like_post.php" method="post" style="margin: 0;">
                    <input type="hidden" name="objava_id" value="<?= $id ?>">
                    <button type="submit" class="like-btn">
                        <?= $ze_vseckal ? '❤️' : '🤍' ?> <span><?= $post['st_vseckov'] ?></span>
                    </button>
                </form>
                <span style="font-size: 13px; color: #A0A0A0; font-weight: 500;">Osebam je to všeč</span>
            </div>

            <div class="qr-card">
                <div style="text-align: right;">
                    <p style="margin: 0; font-weight: 700; font-size: 11px; text-transform: uppercase;">Deli zgodbo</p>
                    <p style="margin: 0; font-size: 10px; color: #A0A0A0;">Skeniraj za mobilni dostop</p>
                </div>
                <img src="<?= $qr_api_url ?>" alt="QR" style="width: 50px; height: 50px; border-radius: 8px; border: 1px solid #eee;">
            </div>
        </div>

        <section class="no-print" style="max-width: 750px; margin: 80px auto 0 auto;">
            <h3 style="font-family: 'Playfair Display'; font-size: 28px; margin-bottom: 40px; display: flex; align-items: center; gap: 15px;">
                Mnenja bralcev <span style="font-size: 16px; background: #FFF9F2; padding: 5px 15px; border-radius: 50px; color: #FF6B6B;"><?= count($komentarji) ?></span>
            </h3>

            <?php if ($user_id): ?>
                <form action="add_comment.php" method="post" style="margin-bottom: 50px;">
                    <input type="hidden" name="objava_id" value="<?= $id ?>">
                    <textarea name="vsebina" class="comment-textarea" rows="4" placeholder="Zapišite svoje misli o zgodbi..." required></textarea>
                    <button type="submit" class="btn-aesthetic" style="background: #4ECDC4; color: white; width: 100%; margin-top: 15px; padding: 18px;">Objavi komentar</button>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #FFF9F2; border-radius: 30px; margin-bottom: 40px;">
                    <p style="margin-bottom: 20px; color: #666;">Želite sodelovati v debati?</p>
                    <a href="login.php" class="btn-aesthetic" style="background: #FF6B6B; color: white;">Prijavite se tukaj</a>
                </div>
            <?php endif; ?>

            <div class="comments-list">
                <?php foreach ($komentarji as $k): ?>
                    <div style="background: #fdfdfd; padding: 25px; border-radius: 25px; margin-bottom: 20px; border-left: 5px solid #FFD93D;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="font-weight: 700;"><?= htmlspecialchars($k['avtor_komentarja']) ?></span>
                            <span style="font-size: 11px; color: #A0A0A0;"><?= date('d. m. Y', strtotime($k['datum_komentarja'])) ?></span>
                        </div>
                        <p style="margin: 0; color: #444;"><?= nl2br(htmlspecialchars($k['vsebina'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </article>
</div>

</body>
</html>
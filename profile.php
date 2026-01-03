<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['user'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user']['user_id'];

// Pridobimo najnovejše podatke iz baze
$stmt = $pdo->prepare("SELECT * FROM uporabnik WHERE user_id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

// Pridobimo objave uporabnika
$sql_posts = "SELECT o.*, k.naziv_kategorije FROM objava o 
              JOIN kategorija k ON o.kategorija_id = k.kategorija_id 
              WHERE o.uporabnik_id = ? ORDER BY o.datum_objave DESC";
$stmt_posts = $pdo->prepare($sql_posts);
$stmt_posts->execute([$user_id]);
$my_posts = $stmt_posts->fetchAll();

$total_views = 0;
foreach($my_posts as $p) { if(isset($p['st_ogledov'])) $total_views += $p['st_ogledov']; }
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moj profil | Med vrsticami</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:italic,wght@0,700&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    
    <style>
        /* DEFINICIJA TEME (CSS Spremenljivke) */
        :root {
            --primary-coral: #FF6B6B;
            --accent-gold: #FFD93D;
            --bg-gradient: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 100%);
            --header-bg: rgba(255, 255, 255, 0.85);
            --card-bg: #ffffff;
            --text-main: #2D3436;
            --text-muted: #666666;
            --item-bg: rgba(255, 255, 255, 0.9);
        }

        /* DARK MODE BARVE */
        body.dark-mode {
            --bg-gradient: linear-gradient(135deg, #2D3436 0%, #000000 100%);
            --header-bg: rgba(30, 30, 30, 0.9);
            --card-bg: #1e1e1e;
            --text-main: #f0f0f0;
            --text-muted: #aaaaaa;
            --item-bg: rgba(45, 45, 45, 0.9);
        }

        body { 
            margin: 0; 
            font-family: 'Poppins', sans-serif; 
            background: var(--bg-gradient); 
            background-attachment: fixed; 
            min-height: 100vh; 
            padding-top: 100px;
            color: var(--text-main);
            transition: all 0.4s ease;
        }

        /* Centrirana navigacija */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: var(--header-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 20px 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-link {
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            letter-spacing: 5px;
            text-transform: uppercase;
            font-size: 20px;
        }
        .logo-link span { color: var(--primary-coral); }

        /* Gumb za preklop teme */
        #theme-toggle {
            position: absolute;
            right: 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            padding: 10px;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }
        #theme-toggle:hover { transform: scale(1.1); }

        /* Profilna kartica */
        .profile-hero { 
            background: var(--card-bg); 
            max-width: 600px; 
            margin: 20px auto 40px auto; 
            padding: 50px 40px; 
            border-radius: 60px; 
            text-align: center; 
            box-shadow: 0 30px 70px rgba(0,0,0,0.2); 
        }
        
        .avatar-container { width: 130px; height: 130px; margin: 0 auto 25px auto; }
        .avatar-img { 
            width: 100%; height: 100%; border-radius: 50%; 
            object-fit: cover; border: 6px solid var(--primary-coral);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .avatar-placeholder { 
            width: 100%; height: 100%; background: #e0e0e0; 
            border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; border: 6px solid var(--card-bg);
        }
        .avatar-placeholder svg { width: 70px; height: 70px; fill: #aaa; }
        
        .btn-settings { 
            display: inline-block; background: var(--primary-coral); 
            color: white; padding: 12px 35px; border-radius: 30px; 
            text-decoration: none; font-size: 13px; font-weight: 700; 
            margin-top: 25px; transition: 0.3s;
        }
        .btn-settings:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255, 107, 107, 0.4); }
        
        .story-list { max-width: 600px; margin: 0 auto 60px auto; padding: 0 20px; }
        .story-item { 
            background: var(--item-bg); margin-bottom: 15px; 
            padding: 25px 30px; border-radius: 30px; display: flex; 
            justify-content: space-between; align-items: center; 
            transition: 0.3s; text-decoration: none; color: inherit; 
        }
        .story-item:hover { transform: translateX(10px); background: var(--card-bg); }

        h1 { font-family: 'Playfair Display'; font-style: italic; font-size: 42px; margin: 0; }
        .bio { color: var(--text-muted); margin: 20px 0; line-height: 1.8; }
        .stats { display: flex; justify-content: center; gap: 60px; margin-top: 30px; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 30px; }
        .stat-val { display: block; color: var(--primary-coral); font-size: 26px; font-weight: 800; }
        .stat-lab { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: var(--text-muted); }
    </style>
</head>
<body>

<script>
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>

<header>
    <a href="index.php" class="logo-link">Med vrsticami<span>.</span></a>
    <button id="theme-toggle" title="Preklopi temo">🌙</button>
</header>

<div class="container">
    <div class="profile-hero">
        <div class="avatar-container">
            <?php if (!empty($u['slika'])): ?>
                <img src="uploads/<?= htmlspecialchars($u['slika']) ?>" class="avatar-img">
            <?php else: ?>
                <div class="avatar-placeholder">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
            <?php endif; ?>
        </div>

        <h1><?= htmlspecialchars($u['ime']) ?></h1>
        <div class="bio">
            <?= !empty($u['biografija']) ? nl2br(htmlspecialchars($u['biografija'])) : "Uredite svoj profil in dodajte biografijo." ?>
        </div>

        <a href="settings.php" class="btn-settings">UREDI PROFIL</a>

        <div class="stats">
            <div><span class="stat-val"><?= count($my_posts) ?></span><span class="stat-lab">Zgodb</span></div>
            <div><span class="stat-val"><?= number_format($total_views) ?></span><span class="stat-lab">Ogledov</span></div>
        </div>
    </div>

    <div class="story-list">
        <h2 style="color: white; font-family: 'Playfair Display'; font-style: italic; text-align: center; margin-bottom: 30px;">Vaše zgodbe</h2>
        <?php foreach ($my_posts as $p): ?>
            <a href="post.php?id=<?= $p['objava_id'] ?>" class="story-item">
                <div>
                    <h3 style="margin: 0; font-family: 'Playfair Display'; font-size: 22px;"><?= htmlspecialchars($p['naslov']) ?></h3>
                    <small style="color: var(--primary-coral); font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;"><?= htmlspecialchars($p['naziv_kategorije']) ?></small>
                </div>
                <span>→</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Posodobi ikono ob nalaganju
    if (body.classList.contains('dark-mode')) {
        themeToggle.textContent = '☀️';
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        const isDark = body.classList.contains('dark-mode');
        
        themeToggle.textContent = isDark ? '☀️' : '🌙';
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
</script>

</body>
</html>
<?php
session_start();
require_once __DIR__ . '/config/db.php';

// --- API KLIC ZA CITAT DNEVA ---
$citat_besedilo = "Vsaka zgodba si zasluži biti zapisana."; 
$citat_avtor = "Med vrsticami";

try {
    $api_url = "https://zenquotes.io/api/today";
    $response = @file_get_contents($api_url); 
    if ($response) {
        $citat_data = json_decode($response, true);
        if (isset($citat_data[0])) {
            $citat_besedilo = $citat_data[0]['q'];
            $citat_avtor = $citat_data[0]['a'];
        }
    }
} catch (Exception $e) {}

// 1. PRIDOBIVANJE PARAMETROV
$iskanje = trim($_GET['q'] ?? '');
$sortiranje = $_GET['sort'] ?? 'novejse';
$izbrana_kategorija = isset($_GET['kat']) ? (int)$_GET['kat'] : 0;

// 2. PRIDOBIVANJE VSEH KATEGORIJ
$stmt_kat = $pdo->query("SELECT * FROM kategorija ORDER BY naziv_kategorije ASC");
$vse_kategorije = $stmt_kat->fetchAll();

// 3. GRADNJA SQL POIZVEDBE
$sql = "SELECT o.*, u.ime AS avtor, k.naziv_kategorije, 
                (SELECT COUNT(*) FROM vsecki WHERE objava_id = o.objava_id) as st_vseckov 
        FROM objava o 
        JOIN uporabnik u ON o.uporabnik_id = u.user_id 
        JOIN kategorija k ON o.kategorija_id = k.kategorija_id WHERE 1=1";

$params = [];
if ($iskanje) {
    $sql .= " AND (o.naslov LIKE ? OR o.vsebina LIKE ? OR u.ime LIKE ?)";
    $params[] = "%$iskanje%"; $params[] = "%$iskanje%"; $params[] = "%$iskanje%";
}
if ($izbrana_kategorija > 0) {
    $sql .= " AND o.kategorija_id = ?";
    $params[] = $izbrana_kategorija;
}
switch ($sortiranje) {
    case 'starejse': $sql .= " ORDER BY o.datum_objave ASC"; break;
    case 'vsecki': $sql .= " ORDER BY st_vseckov DESC"; break;
    default: $sql .= " ORDER BY o.datum_objave DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$objave = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Med vrsticami | Domov</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:italic,wght@0,700;1,700&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-coral: #FF6B6B;
            --bg-body: #FFF9F2;
            --bg-card: #ffffff;
            --text-main: #2D3436;
            --text-muted: #636e72;
            --nav-bg: rgba(255, 255, 255, 0.85);
            --border-color: rgba(0,0,0,0.05);
        }
        body.dark-mode {
            --bg-body: #121212;
            --bg-card: #1E1E1E;
            --text-main: #E0E0E0;
            --text-muted: #A0A0A0;
            --nav-bg: rgba(18, 18, 18, 0.9);
            --border-color: rgba(255,255,255,0.1);
        }
        body { margin: 0; font-family: 'Poppins', sans-serif; background: var(--bg-body); color: var(--text-main); transition: background 0.3s, color 0.3s; }
        
        header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: var(--nav-bg); backdrop-filter: blur(15px); padding: 15px 0; border-bottom: 1px solid var(--border-color); }
        .header-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        
        .logo-full { font-size: 18px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--text-main); text-decoration: none; }
        .logo-full span { color: var(--primary-coral); }
        
        /* ŽIVAHEN HERO Z ANIMACIJO */
        .hero { 
            height: 65vh; 
            background: linear-gradient(-45deg, #FF6B6B, #FF9E7D, #4ECDC4, #FFD93D);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            text-align: center; 
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .hero-glass-box { 
            background: rgba(255, 255, 255, 0.15); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px);
            padding: 50px 70px; 
            border-radius: 50px; 
            border: 1px solid rgba(255, 255, 255, 0.3); 
            max-width: 800px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.1);
        }
        
        .hero h1 { 
            font-family: 'Playfair Display', serif; 
            font-size: clamp(40px, 8vw, 80px); 
            font-style: italic; 
            margin: 0;
            text-shadow: 2px 4px 10px rgba(0,0,0,0.1);
        }

        .daily-quote { margin-top: 20px; }
        .quote-text { font-size: 18px; font-weight: 300; font-style: italic; opacity: 0.9; line-height: 1.4; }

        .content-wrapper { max-width: 1200px; margin: -50px auto 0 auto; position: relative; z-index: 10; padding: 0 20px; }
        .filter-section { background: var(--bg-card); padding: 30px; border-radius: 30px; border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        .search-box { display: flex; background: rgba(0,0,0,0.05); padding: 5px 20px; border-radius: 30px; border: 1px solid var(--border-color); }
        .search-box input { border: none; background: transparent; padding: 10px; width: 100%; outline: none; color: var(--text-main); }
        
        .cat-link { text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 10px 22px; border-radius: 50px; color: var(--text-muted); background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); transition: 0.3s; }
        .cat-link.active { background: var(--primary-coral); color: white; border-color: var(--primary-coral); }
        
        .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; padding: 50px 0; }
        .post-card { background: var(--bg-card); border-radius: 30px; overflow: hidden; transition: 0.4s; border: 1px solid var(--border-color); text-decoration: none; color: inherit; display: flex; flex-direction: column; }
        .post-card:hover { transform: translateY(-10px); border-color: var(--primary-coral); box-shadow: 0 15px 35px rgba(255, 107, 107, 0.1); }
        .post-card-img { width: 100%; height: 240px; object-fit: cover; }
        
        .theme-toggle { background: var(--primary-coral); border: none; color: white; padding: 8px 12px; border-radius: 20px; cursor: pointer; font-size: 14px; font-weight: 600; }
        
        .dropdown-menu { display: none; position: absolute; right: 0; top: 55px; background: var(--bg-card); border-radius: 15px; width: 220px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .dropdown-menu.show { display: block; }
        .dropdown-menu a { display: block; padding: 12px 20px; text-decoration: none; color: var(--text-main); font-size: 14px; }
        .dropdown-menu a:hover { background: var(--primary-coral); color: white; }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <a href="index.php" class="logo-full">Med vrsticami<span>.</span></a>
        
        <div style="display: flex; align-items: center; gap: 15px;">
            <button class="theme-toggle" onclick="toggleDarkMode()" id="themeBtn">Nočni način</button>
            <div class="menu-container" style="position:relative;">
                <button onclick="toggleMenu()" class="hamburger-btn" style="background:none; border:none; font-size:28px; cursor:pointer; color: var(--text-main);">☰</button>
                <div id="myDropdown" class="dropdown-menu">
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="profile.php">Moj profil</a>
                        <a href="create.php">Nova zgodba</a>
                        
                        <?php 
                        $is_premium = $_SESSION['user']['is_premium'] ?? 0; 
                        if ($is_premium == 0): ?>
                            <a href="https://buy.stripe.com/test_00wbJ10tq5Ce26w9F1b3q00" style="background: #fff0f0; color: #FF6B6B; font-weight: bold;">💎 Postani Premium</a>
                        <?php else: ?>
                            <a href="#" style="color: #4CAF50; pointer-events: none;">✅ Premium aktiven</a>
                        <?php endif; ?>

                        <?php if (($_SESSION['user']['vloga_id'] ?? 1) == 2): ?>
                            <a href="admin.php" style="font-weight: bold; color: var(--primary-coral);">⚙️ Admin plošča</a>
                        <?php endif; ?>
                        
                        <a href="statistika.php">Analitika</a>
                        <hr style="margin: 0; border: 0; border-top: 1px solid var(--border-color);">
                        <a href="logout.php">Odjava</a>
                    <?php else: ?>
                        <a href="login.php">Prijava</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-glass-box">
        <h1>Med vrsticami<span>.</span></h1>
        <div class="daily-quote">
            <p class="quote-text">"<?= htmlspecialchars($citat_besedilo) ?>"</p>
            <span class="quote-author" style="font-weight: 600;">— <?= htmlspecialchars($citat_avtor) ?></span>
        </div>
    </div>
</section>

<div class="content-wrapper">
    <div class="filter-section">
        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 15px; margin-bottom: 20px; text-align: center; border: 1px solid #c3e6cb;">
                Čestitamo! Uspešno ste postali Premium član!🎉
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px;">
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="q" placeholder="Poišči zgodbo..." value="<?= htmlspecialchars($iskanje) ?>">
                <button type="submit" style="background:none; border:none; cursor:pointer;">🔍</button>
            </form>
            <div>
                <a href="index.php?kat=<?= $izbrana_kategorija ?>&sort=novejse" style="text-decoration:none; font-size:12px; color:var(--text-muted); padding:5px 10px; font-weight:600;">Novejše</a>
                <a href="index.php?kat=<?= $izbrana_kategorija ?>&sort=vsecki" style="text-decoration:none; font-size:12px; color:var(--text-muted); padding:5px 10px; font-weight:600;">Najboljše</a>
            </div>
        </div>

        <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none;">
            <a href="index.php" class="cat-link <?= $izbrana_kategorija == 0 ? 'active' : '' ?>">Vse</a>
            <?php foreach ($vse_kategorije as $kat): ?>
                <a href="index.php?kat=<?= $kat['kategorija_id'] ?>" class="cat-link <?= $izbrana_kategorija == $kat['kategorija_id'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($kat['naziv_kategorije']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <main class="posts-grid">
        <?php foreach ($objave as $o): ?>
            <a href="post.php?id=<?= $o['objava_id'] ?>" class="post-card">
                <?php if ($o['slika']): ?>
                    <img src="uploads/<?= htmlspecialchars($o['slika']) ?>" class="post-card-img">
                <?php else: ?>
                    <div style="height: 240px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">Brez slike</div>
                <?php endif; ?>
                <div style="padding: 25px; flex-grow: 1;">
                    <span style="font-size: 10px; font-weight: 800; color: var(--primary-coral); text-transform: uppercase; letter-spacing: 1px;">
                        <?= htmlspecialchars($o['naziv_kategorije']) ?>
                    </span>
                    <h2 style="font-family: 'Playfair Display'; font-size: 22px; margin: 5px 0 15px 0; color: var(--text-main); line-height: 1.3;"><?= htmlspecialchars($o['naslov']) ?></h2>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 15px;">
                        <span>✍️ <?= htmlspecialchars($o['avtor']) ?></span>
                        <span>❤️ <?= $o['st_vseckov'] ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </main>
</div>

<script>
    function toggleDarkMode() {
        const body = document.body;
        const btn = document.getElementById('themeBtn');
        body.classList.toggle('dark-mode');
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
            btn.innerHTML = "Dnevni način";
        } else {
            localStorage.setItem('theme', 'light');
            btn.innerHTML = "Nočni način";
        }
    }

    window.onload = function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            document.getElementById('themeBtn').innerHTML = "Dnevni način";
        }
    };

    function toggleMenu() { document.getElementById("myDropdown").classList.toggle("show"); }
    
    window.onclick = function(event) {
        if (!event.target.matches('.hamburger-btn')) {
            var dropdowns = document.getElementsByClassName("dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }
</script>
</body>
</html>
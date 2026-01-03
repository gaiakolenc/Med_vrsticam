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
        /* DEFINICIJA BARV ZA OBA NAČINA */
        :root {
            --primary-coral: #FF6B6B;
            --bg-body: #FFF9F2;
            --bg-card: #ffffff;
            --text-main: #2D3436;
            --text-muted: #636e72;
            --nav-bg: rgba(255, 255, 255, 0.85);
            --border-color: rgba(0,0,0,0.05);
        }

        /* DARK MODE BARVE */
        body.dark-mode {
            --bg-body: #121212;
            --bg-card: #1E1E1E;
            --text-main: #E0E0E0;
            --text-muted: #A0A0A0;
            --nav-bg: rgba(18, 18, 18, 0.9);
            --border-color: rgba(255,255,255,0.1);
        }

        body { 
            margin: 0; 
            font-family: 'Poppins', sans-serif; 
            background: var(--bg-body); 
            color: var(--text-main);
            transition: background 0.3s, color 0.3s;
        }

        header {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            background: var(--nav-bg); backdrop-filter: blur(15px);
            padding: 15px 0; border-bottom: 1px solid var(--border-color);
        }
        .header-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        
        .logo-full { font-size: 18px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; color: var(--text-main); text-decoration: none; }
        .logo-full span { color: var(--primary-coral); }

        /* GUMB ZA DARK MODE */
        .theme-toggle {
            background: var(--primary-coral);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .theme-toggle:active { transform: scale(0.9); }

        /* VREME */
        .weather-widget {
            font-size: 13px; font-weight: 600; background: rgba(0,0,0,0.03); 
            padding: 6px 15px; border-radius: 20px; display: flex; align-items: center; gap: 8px;
            color: var(--text-main);
        }
        body.dark-mode .weather-widget { background: rgba(255,255,255,0.05); }

        /* HERO */
        .hero {
            height: 60vh; background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=1500&q=80');
            background-size: cover; background-position: center; display: flex; align-items: center; justify-content: center; color: white; text-align: center;
        }
        .hero-glass-box { background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(10px); padding: 40px; border-radius: 40px; border: 1px solid rgba(255, 255, 255, 0.1); max-width: 800px; }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: clamp(35px, 7vw, 70px); font-style: italic; margin: 0; }

        /* CONTENT */
        .content-wrapper { max-width: 1200px; margin: -50px auto 0 auto; position: relative; z-index: 10; padding: 0 20px; }
        .filter-section { background: var(--bg-card); padding: 30px; border-radius: 30px; border: 1px solid var(--border-color); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        .search-box { display: flex; background: rgba(0,0,0,0.05); padding: 5px 20px; border-radius: 30px; border: 1px solid var(--border-color); }
        body.dark-mode .search-box { background: rgba(255,255,255,0.05); }
        .search-box input { border: none; background: transparent; padding: 10px; width: 100%; outline: none; color: var(--text-main); }
        
        .cat-link { text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 10px 22px; border-radius: 50px; color: var(--text-muted); background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); transition: 0.3s; }
        .cat-link.active { background: var(--primary-coral); color: white; border-color: var(--primary-coral); }

        /* GRID */
        .posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px; padding: 50px 0; }
        .post-card { background: var(--bg-card); border-radius: 30px; overflow: hidden; transition: 0.4s; border: 1px solid var(--border-color); text-decoration: none; color: inherit; }
        .post-card:hover { transform: translateY(-10px); border-color: var(--primary-coral); }
        .post-card-img { width: 100%; height: 240px; object-fit: cover; }

        .dropdown-menu { display: none; position: absolute; right: 0; top: 55px; background: var(--bg-card); border-radius: 15px; width: 200px; border: 1px solid var(--border-color); overflow: hidden; }
        .dropdown-menu.show { display: block; }
        .dropdown-menu a { display: block; padding: 12px 20px; text-decoration: none; color: var(--text-main); }
        .dropdown-menu a:hover { background: var(--primary-coral); color: white; }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <a href="index.php" class="logo-full">Med vrsticami<span>.</span></a>
        
        <div id="weather-box"></div>

        <div style="display: flex; align-items: center; gap: 15px;">
            <button class="theme-toggle" onclick="toggleDarkMode()" id="themeBtn">🌙 Nočni način</button>
            
            <div class="menu-container" style="position:relative;">
                <button onclick="toggleMenu()" class="hamburger-btn" id="hamBtn" style="background:none; border:none; font-size:28px; cursor:pointer; color: var(--text-main);">☰</button>
                <div id="myDropdown" class="dropdown-menu">
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="profile.php">👤 Moj profil</a>
                        <a href="create.php">✍️ Nova zgodba</a>
                        <a href="statistika.php">📊 Analitika</a>
                        <a href="logout.php" style="color: var(--primary-coral);">Odjava</a>
                    <?php else: ?>
                        <a href="login.php">🔑 Prijava</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-glass-box">
        <h1>Med vrsticami.</h1>
        <div class="daily-quote">
            <p class="quote-text">"<?= htmlspecialchars($citat_besedilo) ?>"</p>
            <span class="quote-author">— <?= htmlspecialchars($citat_avtor) ?></span>
        </div>
    </div>
</section>

<div class="content-wrapper">
    <div class="filter-section">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px;">
            <form action="index.php" method="GET" class="search-box">
                <input type="text" name="q" placeholder="Poišči zgodbo..." value="<?= htmlspecialchars($iskanje) ?>">
                <button type="submit" style="background:none; border:none; cursor:pointer;">🔍</button>
            </form>
            <div>
                <a href="index.php?kat=<?= $izbrana_kategorija ?>&sort=novejse" style="text-decoration:none; font-size:12px; color:var(--text-muted); padding:5px 10px;">Novejše</a>
                <a href="index.php?kat=<?= $izbrana_kategorija ?>&sort=vsecki" style="text-decoration:none; font-size:12px; color:var(--text-muted); padding:5px 10px;">Najboljše</a>
            </div>
        </div>

        <div style="display: flex; gap: 10px; overflow-x: auto;">
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
                    <div style="height: 240px; background: #eee; display: flex; align-items: center; justify-content: center; color: #999;">Brez slike</div>
                <?php endif; ?>
                <div style="padding: 25px;">
                    <h2 style="font-family: 'Playfair Display'; font-size: 22px; margin: 0 0 10px 0; color: var(--text-main);"><?= htmlspecialchars($o['naslov']) ?></h2>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted);">
                        <span>✍️ <?= htmlspecialchars($o['avtor']) ?></span>
                        <span>❤️ <?= $o['st_vseckov'] ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </main>
</div>

<script>
    // 1. LOGIKA ZA DARK MODE
    function toggleDarkMode() {
        const body = document.body;
        const btn = document.getElementById('themeBtn');
        
        body.classList.toggle('dark-mode');
        
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
            btn.innerHTML = "☀️ Dnevni način";
        } else {
            localStorage.setItem('theme', 'light');
            btn.innerHTML = "🌙 Nočni način";
        }
    }

    // Preveri shranjeno temo ob nalaganju
    window.onload = function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            document.getElementById('themeBtn').innerHTML = "☀️ Dnevni način";
        }
        fetchWeather();
    };

    // 2. OSTALE FUNKCIJE
    function toggleMenu() { document.getElementById("myDropdown").classList.toggle("show"); }
    
    async function fetchWeather() {
        try {
            const res = await fetch('https://api.openweathermap.org/data/2.5/weather?q=Ljubljana&units=metric&lang=sl&appid=48cc49a7a9a13b5e4063857321689230');
            const data = await res.json();
            if(data.main) {
                const temp = Math.round(data.main.temp);
                document.getElementById('weather-box').innerHTML = `<div class="weather-widget"><span>Ljubljana ${temp}°C</span></div>`;
            }
        } catch (e) {}
    }
</script>

</body>
</html>
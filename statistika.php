<?php
session_start();
require_once __DIR__ . '/config/db.php';

// 1. Skupni števci za vrh strani
$total_posts = $pdo->query("SELECT COUNT(*) FROM objava")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM uporabnik")->fetchColumn();

// 2. Podatki za grafikon (Kategorije)
$sql_kat = "SELECT k.naziv_kategorije, COUNT(o.objava_id) as stevilo 
            FROM kategorija k 
            LEFT JOIN objava o ON k.kategorija_id = o.kategorija_id 
            GROUP BY k.kategorija_id 
            ORDER BY stevilo DESC";
$stats_kat = $pdo->query($sql_kat)->fetchAll();

// 3. Podatki za najaktivnejše avtorje
$sql_avtorji = "SELECT u.ime, COUNT(o.objava_id) as st_objav 
                FROM uporabnik u 
                JOIN objava o ON u.user_id = o.uporabnik_id 
                GROUP BY u.user_id 
                ORDER BY st_objav DESC 
                LIMIT 5";
$top_avtorji = $pdo->query($sql_avtorji)->fetchAll();

// Priprava label in podatkov za JS
$labels = json_encode(array_column($stats_kat, 'naziv_kategorije'));
$values = json_encode(array_column($stats_kat, 'stevilo'));
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitika portala | Med vrsticami</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Specifični stili za statistiko, ki se prilagajajo temi */
        body { padding-top: 100px; }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-coral) 0%, var(--accent-gold) 100%);
            padding: 60px 20px;
            border-radius: 50px;
            text-align: center;
            margin-bottom: 40px;
            color: white;
            box-shadow: 0 20px 40px var(--shadow);
        }

        .stats-wrapper {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .chart-container {
            position: relative;
            width: 100%;
            height: 380px;
            margin: 0 auto;
        }

        .chart-inner-text {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .chart-number {
            display: block;
            font-size: 50px;
            font-weight: 800;
            color: var(--primary-coral);
            line-height: 1;
        }

        .author-rank {
            width: 32px;
            height: 32px;
            background: var(--bg-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: var(--accent-gold);
            margin-right: 15px;
        }

        .stat-card-small {
            background: var(--card-bg);
            text-align: center;
            padding: 40px;
            border-radius: 40px;
            box-shadow: 0 10px 30px var(--shadow);
            border-bottom: 4px solid var(--primary-coral);
        }

        @media (max-width: 900px) {
            .stats-wrapper { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <a href="index.php" class="logo-branding-mini">Med vrsticami<span>.</span></a>
        <div class="nav-actions">
            <button onclick="toggleTheme()" class="theme-toggle" id="themeBtn">🌙</button>
            <div class="menu-container">
                <button onclick="toggleMenu()" class="hamburger-btn" id="hamBtn">☰</button>
                <div id="myDropdown" class="dropdown-menu">
                    <a href="index.php">🏠 Domov</a>
                    <a href="profile.php">👤 Moj profil</a>
                    <a href="create.php">✍️ Nova zgodba</a>
                    <a href="logout.php" style="color: var(--primary-coral);">🚪 Odjava</a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container">
    <section class="hero-section">
        <p style="text-transform: uppercase; letter-spacing: 4px; font-size: 11px; font-weight: 700; margin-bottom: 10px; opacity: 0.9;">Statistični pregled</p>
        <h1 style="font-family: 'Playfair Display'; font-size: 42px; font-style: italic; margin: 0;">Analitika Portala</h1>
    </section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; margin-bottom: 30px;">
        <div class="stat-card-small">
            <p style="font-size: 11px; color: #A0A0A0; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">Vseh zgodb</p>
            <h2 style="font-size: 38px; margin: 10px 0; font-family: 'Playfair Display'; color: var(--text-main);"><?= $total_posts ?></h2>
        </div>
        <div class="stat-card-small" style="border-color: var(--accent-gold);">
            <p style="font-size: 11px; color: #A0A0A0; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">Aktivnih avtorjev</p>
            <h2 style="font-size: 38px; margin: 10px 0; font-family: 'Playfair Display'; color: var(--text-main);"><?= $total_users ?></h2>
        </div>
    </div>

    <div class="stats-wrapper">
        <div class="post-card visible" style="padding: 40px; border-radius: 40px;">
            <h3 style="font-family: 'Playfair Display'; font-size: 22px; font-style: italic; text-align: center; margin-bottom: 30px; color: var(--text-main);">Priljubljenost kategorij</h3>
            <div class="chart-container">
                <canvas id="katChart"></canvas>
                <div class="chart-inner-text">
                    <span class="chart-number"><?= $total_posts ?></span>
                    <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #A0A0A0; font-weight: 700;">Zgodb</span>
                </div>
            </div>
        </div>

        <div class="post-card visible" style="padding: 40px; border-radius: 40px;">
            <h3 style="font-family: 'Playfair Display'; font-size: 22px; font-style: italic; margin-bottom: 30px; color: var(--text-main);">Najaktivnejši pisci</h3>
            <?php foreach ($top_avtorji as $index => $avtor): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--bg-color);">
                    <div style="display: flex; align-items: center;">
                        <span class="author-rank"><?= $index + 1 ?></span>
                        <span style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($avtor['ime']) ?></span>
                    </div>
                    <span style="color: var(--primary-coral); font-weight: 800; font-size: 14px;"><?= $avtor['st_objav'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// --- FUNKCIJE ZA NAVIGACIJO IN TEMO ---
function toggleMenu() { document.getElementById("myDropdown").classList.toggle("show"); }

function toggleTheme() {
    const body = document.body;
    const btn = document.getElementById("themeBtn");
    body.classList.toggle("dark-mode");
    const isDark = body.classList.contains("dark-mode");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    btn.textContent = isDark ? "☀️" : "🌙";
    location.reload(); // Osvežimo za ponovni izris grafov s pravimi barvami
}

if(localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");
    document.getElementById("themeBtn").textContent = "☀️";
}

// --- IZRIS GRAFA ---
const isDark = document.body.classList.contains('dark-mode');
const txtColor = isDark ? '#F5F5F5' : '#2D3436';

const ctx = document.getElementById('katChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= $labels ?>,
        datasets: [{
            data: <?= $values ?>,
            backgroundColor: ['#FF6B6B', '#FFD93D', '#4ECDC4', '#6C5CE7', '#FAB1A0', '#74b9ff'],
            borderWidth: isDark ? 2 : 0,
            borderColor: '#1e1e1e',
            borderRadius: 10,
            spacing: 5
        }]
    },
    options: {
        cutout: '80%',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: txtColor,
                    padding: 20,
                    usePointStyle: true,
                    font: { family: 'Poppins', size: 11 }
                }
            }
        }
    }
});
</script>
</body>
</html>
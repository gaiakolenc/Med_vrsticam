<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (empty($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$kategorije = $pdo->query("SELECT * FROM kategorija ORDER BY naziv_kategorije ASC")->fetchAll();
$err = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $naslov = trim($_POST['naslov'] ?? '');
    $vsebina = trim($_POST['vsebina'] ?? '');
    $kategorija_id = $_POST['kategorija_id'] ?? '';
    $uporabnik_id = $_SESSION['user']['user_id'];
    
    $slika_ime = null;
    if (!empty($_FILES['slika']['name'])) {
        $target_dir = "uploads/";
        $slika_ime = time() . "_" . basename($_FILES["slika"]["name"]);
        move_uploaded_file($_FILES["slika"]["tmp_name"], $target_dir . $slika_ime);
    }

    if ($naslov && $vsebina && $kategorija_id) {
        $stmt = $pdo->prepare("INSERT INTO objava (naslov, vsebina, slika, uporabnik_id, kategorija_id, datum_objave) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$naslov, $vsebina, $slika_ime, $uporabnik_id, $kategorija_id])) {
            header("Location: index.php?success=1");
            exit;
        } else {
            $err = "Napaka pri shranjevanju.";
        }
    } else {
        $err = "Izpolnite vsa polja.";
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova zgodba | Med vrsticami</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:italic,wght@0,700;1,700&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-coral: #FF6B6B;
            --accent-gold: #FFD93D;
            --dark-text: #2D3436;
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-coral) 0%, var(--accent-gold) 100%);
            background-attachment: fixed;
            min-height: 100vh;
            padding-top: 100px; /* Prostor za fiksni meni */
        }

        /* GLASSMORPHISM NAVIGACIJA (Enaka kot na indexu) */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        .header-container {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo-branding-mini {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--dark-text);
            text-decoration: none;
        }
        .logo-branding-mini span { color: var(--primary-coral); }

        /* KARTICA ZA OBRAZEC */
        .create-card {
            background: white;
            max-width: 700px;
            margin: 0 auto 50px auto;
            padding: 50px;
            border-radius: 50px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.15);
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-style: italic;
            text-align: center;
            margin-bottom: 40px;
            color: var(--dark-text);
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #A0A0A0;
            margin: 20px 0 10px 5px;
        }

        input[type="text"], select, textarea {
            width: 100%;
            padding: 18px 25px;
            border-radius: 20px;
            border: 2px solid #FFF9F2;
            background: #FFF9F2;
            font-family: inherit;
            font-size: 15px;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-coral);
            background: white;
        }

        .btn-submit {
            width: 100%;
            background: var(--dark-text);
            color: white;
            padding: 20px;
            border-radius: 30px;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            margin-top: 30px;
            transition: 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            background: #000;
        }

        .show { display: block !important; }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <a href="index.php" class="logo-branding-mini">
            Med vrsticami<span>.</span>
        </a>
        
        <div class="menu-container">
            <button onclick="toggleMenu()" class="hamburger-btn" id="hamBtn">☰</button>
            <div id="myDropdown" class="dropdown-menu">
                <div style="padding: 15px 20px; font-size: 12px; color: #A0A0A0; border-bottom: 1px solid #FFF9F2;">
                    Živijo, <?= htmlspecialchars($_SESSION['user']['ime']) ?>!
                </div>
                <a href="profile.php">👤 Moj profil</a>
                <a href="index.php">🏠 Domov</a>
                <a href="statistika.php">📊 Analitika portala</a> 
                <a href="logout.php" style="color: var(--primary-coral);">🚪 Odjava</a>
            </div>
        </div>
    </div>
</header>

<div class="container">
    <div class="create-card">
        <h2>Nova zgodba.</h2>

        <?php if ($err): ?>
            <p style="color: var(--primary-coral); text-align: center;"><?= $err ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Naslov tvoje zgodbe</label>
            <input type="text" name="naslov" placeholder="Naslov, ki pritegne..." required>

            <label>Kategorija</label>
            <select name="kategorija_id" required>
                <option value="" disabled selected>Izberi kategorijo</option>
                <?php foreach ($kategorije as $kat): ?>
                    <option value="<?= $kat['kategorija_id'] ?>"><?= htmlspecialchars($kat['naziv_kategorije']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Naslovna slika</label>
            <input type="file" name="slika" accept="image/*" style="background: none; border: none; padding-left: 0;">

            <label>Vsebina</label>
            <textarea name="vsebina" style="min-height: 250px;" placeholder="Tukaj se začne tvoja zgodba..." required></textarea>

            <button type="submit" class="btn-submit">Objavi na portalu</button>
        </form>
    </div>
</div>

<script>
    function toggleMenu() {
        document.getElementById("myDropdown").classList.toggle("show");
    }

    window.onclick = function(event) {
        if (!event.target.matches('#hamBtn')) {
            var dropdowns = document.getElementsByClassName("dropdown-menu");
            for (var i = 0; i < dropdowns.length; i++) {
                if (dropdowns[i].classList.contains('show')) dropdowns[i].classList.remove('show');
            }
        }
    }
</script>

</body>
</html>
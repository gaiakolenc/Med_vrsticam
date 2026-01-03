<?php
session_start();
require_once __DIR__ . '/config/db.php';
if (empty($_SESSION['user'])) { header("Location: login.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM uporabnik WHERE user_id = ?");
$stmt->execute([$_SESSION['user']['user_id']]);
$u = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uredi profil | Med vrsticami</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:italic,wght@0,700;1,700&family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-coral: #FF6B6B;
            --accent-gold: #FFD93D;
            --dark-text: #2D3436;
            --glass-bg: rgba(255, 255, 255, 0.9);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary-coral) 0%, var(--accent-gold) 100%);
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Glavni kontejner z efektom stekla */
        .settings-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 60px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 480px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        /* Dekorativni krog v ozadju */
        .settings-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: var(--accent-gold);
            opacity: 0.1;
            border-radius: 50%;
            z-index: -1;
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 42px;
            font-style: italic;
            margin-bottom: 10px;
            color: var(--dark-text);
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #A0A0A0;
            font-size: 14px;
            margin-bottom: 40px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--dark-text);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding-left: 5px;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 16px 20px;
            border-radius: 20px;
            border: 2px solid transparent;
            background: white;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            color: var(--dark-text);
            box-sizing: border-box;
            box-shadow: 0 10px 20px rgba(0,0,0,0.02);
            transition: all 0.3s ease;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-coral);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.1);
        }

        /* Stil za file input */
        .file-input-wrapper {
            position: relative;
            margin-top: 10px;
        }

        input[type="file"] {
            font-size: 13px;
            color: #A0A0A0;
        }

        .btn-submit {
            width: 100%;
            background: var(--dark-text);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .btn-submit:hover {
            background: var(--primary-coral);
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(255, 107, 107, 0.3);
        }

        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #A0A0A0;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .cancel-link:hover {
            color: var(--primary-coral);
        }

        /* Animacija ob prihodu */
        .settings-card {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="settings-card">
    <h2>Uredi profil</h2>
    <p class="subtitle">Osveži svojo zgodbo</p>

    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Uporabniško ime</label>
            <input type="text" name="ime" value="<?= htmlspecialchars($u['ime']) ?>" placeholder="Tvoje ime..." required>
        </div>

        <div class="form-group">
            <label>Email naslov</label>
            <input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" placeholder="tvoj@email.com" required>
        </div>

        <div class="form-group">
            <label>Nekaj o meni</label>
            <textarea name="biografija" rows="4" placeholder="Kdo si, ko nihče ne gleda?"><?= htmlspecialchars($u['biografija'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Profilna slika</label>
            <div class="file-input-wrapper">
                <input type="file" name="slika" accept="image/*">
            </div>
        </div>

        <button type="submit" class="btn-submit">Shrani spremembe</button>
    </form>

    <a href="profile.php" class="cancel-link">Prekliči in se vrni nazaj</a>
</div>

</body>
</html>
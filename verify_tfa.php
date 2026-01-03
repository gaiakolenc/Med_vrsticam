<?php
session_start();
require_once __DIR__ . "/config/db.php";

// Če uporabnik ni prišel čez login.php, ga vržemo ven
if (!isset($_SESSION['tfa_user_id'])) {
    header("Location: login.php");
    exit;
}

$err = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $vnesena_koda = trim($_POST['koda'] ?? '');
    $user_id = $_SESSION['tfa_user_id'];

    // Preverimo, če koda v bazi ustreza vneseni
    $stmt = $pdo->prepare("SELECT * FROM uporabnik WHERE user_id = ? AND tfa_code = ?");
    $stmt->execute([$user_id, $vnesena_koda]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Koda je pravilna! Ustvarimo polno sejo
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'ime'     => $user['ime'],
            'email'   => $user['email'],
            // Uporabimo kratek IF, da preprečimo napako, če stolpec vloga_id ne obstaja
            'vloga_id'=> $user['vloga_id'] ?? 1 
        ];
        
        // Brišemo kodo iz baze, da se ne more uporabit dvakrat
        $update = $pdo->prepare("UPDATE uporabnik SET tfa_code = NULL WHERE user_id = ?");
        $update->execute([$user_id]);
        
        // Brišemo začasno sejo
        unset($_SESSION['tfa_user_id']);

        // Uspeh! Gremo na glavno stran
        header("Location: index.php");
        exit;
    } else {
        $err = "Varnostna koda ni pravilna. Preverite e-pošto in poskusite znova.";
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preverjanje | Med vrsticami</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body.auth-wrapper { 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            background: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 100%); 
            margin: 0; 
            padding: 20px; 
            font-family: 'Poppins', sans-serif; 
        }
        .auth-card { 
            background: white; 
            padding: 55px 45px; 
            border-radius: 50px; 
            width: 100%; 
            max-width: 440px; 
            text-align: center; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.15); 
        }
        .logo-branding { 
            font-size: 13px; 
            font-weight: 800; 
            letter-spacing: 7px; 
            text-transform: uppercase; 
            color: #2D3436; 
            text-decoration: none; 
            display: inline-block; 
            margin-bottom: 35px; 
        }
        .logo-branding span { color: #FF6B6B; }
        h2 { font-size: 32px; color: #2D3436; margin-bottom: 15px; }
        .subtitle { font-size: 14px; color: #888; margin-bottom: 40px; }
        .input-2fa { 
            width: 100%; 
            background: #FFF9F2; 
            border: 2px solid transparent; 
            padding: 20px; 
            border-radius: 20px; 
            font-size: 28px; 
            letter-spacing: 12px; 
            text-align: center; 
            font-weight: 700; 
            box-sizing: border-box; 
            margin-bottom: 25px;
        }
        .input-2fa:focus { outline: none; border-color: #FF6B6B; background: white; }
        .btn-aesthetic { 
            width: 100%; 
            background: #2D3436; 
            color: white; 
            padding: 18px; 
            border-radius: 30px; 
            border: none; 
            cursor: pointer; 
            text-transform: uppercase; 
            font-weight: bold;
        }
        .error-box { background: #FFF5F5; color: #FF6B6B; padding: 15px; border-radius: 20px; margin-bottom: 25px; font-size: 13px; }
        .cancel-link { display: block; margin-top: 30px; font-size: 13px; color: #A0A0A0; text-decoration: none; }
    </style>
</head>
<body class="auth-wrapper">
    <div class="auth-card">
        <a href="index.php" class="logo-branding">Med vrsticami<span>.</span></a>
        <h2>Varnostna koda.</h2>
        <p class="subtitle">Vnesite 6-mestno kodo, ki ste jo prejeli na e-naslov.</p>

        <?php if ($err): ?>
            <div class="error-box"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="koda" class="input-2fa" placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
            <button type="submit" class="btn-aesthetic">Potrdi in vstopi</button>
        </form>

        <a href="login.php" class="cancel-link">Prekliči in se vrni na prijavo</a>
    </div>
</body>
</html>
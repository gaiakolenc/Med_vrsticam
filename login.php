<?php
session_start();
require_once __DIR__ . "/config/db.php";

// Vključi PHPMailer razrede
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Pot do tvojega composer autoload-a
require __DIR__ . '/phpmailer/vendor/autoload.php';

$err = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $geslo = $_POST['geslo'] ?? '';

    if ($email && $geslo) {
        $stmt = $pdo->prepare("SELECT * FROM uporabnik WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($geslo, $user['geslo'])) {
            // 1. Generiranje 6-mestne kode
            $koda = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // 2. Shranjevanje kode v bazo
            $update = $pdo->prepare("UPDATE uporabnik SET tfa_code = ? WHERE user_id = ?");
            $update->execute([$koda, $user['user_id']]);

            // 3. Pošiljanje e-pošte s PHPMailer
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'gaia.kolenc@gmail.com'; 
                $mail->Password   = 'jkgkqlledcwrojbi'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail->setFrom('gaia.kolenc@gmail.com', 'Med vrsticami');
                $mail->addAddress($user['email'], $user['ime']);

                $mail->isHTML(true);
                $mail->Subject = 'Varnostna koda za prijavo - Med vrsticami';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; padding: 30px; border: 1px solid #eee; border-radius: 20px; max-width: 500px;'>
                        <h2 style='color: #FF6B6B; font-style: italic;'>Pozdravljeni, {$user['ime']}!</h2>
                        <p style='color: #555;'>Vaša varnostna koda za vstop v portal <b>Med vrsticami</b> je:</p>
                        <div style='background: #FFF9F2; padding: 20px; text-align: center; border-radius: 15px; margin: 20px 0;'>
                            <h1 style='letter-spacing: 10px; color: #2D3436; margin: 0;'>{$koda}</h1>
                        </div>
                        <p style='color: #888; font-size: 12px;'>Če te prijave niste zahtevali vi, prosimo prezrite to sporočilo.</p>
                    </div>";
                $mail->AltBody = "Vaša koda za prijavo je: " . $koda;

                $mail->send();

                $_SESSION['tfa_user_id'] = $user['user_id'];
                header("Location: verify_tfa.php");
                exit;

            } catch (Exception $e) {
                $err = "Napaka pri pošiljanju kode: " . $mail->ErrorInfo;
            }
        } else {
            $err = "Neveljaven e-poštni naslov ali geslo.";
        }
    } else {
        $err = "Prosimo, izpolnite vsa polja.";
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prijava | Med vrsticami</title>
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
        h2 { font-size: 38px; color: #2D3436; margin-bottom: 15px; }
        .subtitle { font-size: 14px; color: #888; margin-bottom: 40px; }
        .form-group { text-align: left; margin-bottom: 20px; }
        .form-control-aesthetic {
            width: 100%;
            background: #FFF9F2;
            border: 2px solid transparent;
            padding: 18px 25px;
            border-radius: 30px;
            font-size: 15px;
            box-sizing: border-box;
            transition: 0.3s;
        }
        .form-control-aesthetic:focus {
            outline: none;
            border-color: #FFD93D;
        }
        .btn-aesthetic {
            width: 100%;
            background: #2D3436;
            color: white;
            padding: 18px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            font-weight: 600;
            transition: 0.3s;
            display: block;
            text-decoration: none;
        }
        .btn-aesthetic:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .error-box {
            background: #FFF5F5;
            color: #FF6B6B;
            padding: 15px;
            border-radius: 20px;
            margin-bottom: 25px;
            font-size: 13px;
        }
        .auth-footer {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        .footer-text {
            font-size: 13px;
            color: #888;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="auth-wrapper">

    <div class="auth-card">
        <a href="index.php" class="logo-branding">Med vrsticami<span>.</span></a>
        <h2>Pozdravljeni.</h2>
        <p class="subtitle">Vnesite podatke za prejem varnostne kode.</p>

        <?php if ($err): ?>
            <div class="error-box"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <form method="post" id="loginForm">
            <div class="form-group">
                <input type="email" name="email" id="loginEmail" class="form-control-aesthetic" placeholder="Vaš e-naslov" required>
            </div>
            <div class="form-group">
                <input type="password" name="geslo" class="form-control-aesthetic" placeholder="Vaše geslo" required>
            </div>
            <button type="submit" class="btn-aesthetic">Pošlji kodo</button>
        </form>

        <div class="auth-footer">
            <p class="footer-text">Še nimate računa?</p>
            <a href="register.php" class="btn-aesthetic" style="background: #FF6B6B;">Ustvari račun</a>
        </div>
    </div>

    <script>
        // Ob nalaganju strani preveri LocalStorage
        window.addEventListener('DOMContentLoaded', () => {
            const savedEmail = localStorage.getItem('user_email_draft');
            if (savedEmail) {
                document.getElementById('loginEmail').value = savedEmail;
            }
        });

        // Ob oddaji obrazca shrani email v LocalStorage
        document.getElementById('loginForm').addEventListener('submit', () => {
            const email = document.getElementById('loginEmail').value;
            localStorage.setItem('user_email_draft', email);
        });
    </script>

</body>
</html>
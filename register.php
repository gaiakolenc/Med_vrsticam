<?php
session_start();
require_once __DIR__ . "/config/db.php";

// Vključi PHPMailer razrede
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/vendor/autoload.php';

$napake = [];
$uspeh = "";

// Ob oddaji obrazca
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $ime = trim($_POST["ime"] ?? "");
    $priimek = trim($_POST["priimek"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $geslo = $_POST["geslo"] ?? "";
    $geslo2 = $_POST["geslo2"] ?? "";

    // ✅ VALIDACIJE
    if ($ime === "") { $napake[] = "Ime je obvezno."; }
    if ($priimek === "") { $napake[] = "Priimek je obvezen."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $napake[] = "Vnesi veljaven e-poštni naslov."; }
    if (strlen($geslo) < 6) { $napake[] = "Geslo mora imeti vsaj 6 znakov."; }
    if ($geslo !== $geslo2) { $napake[] = "Gesli se ne ujemata."; }

    // Preveri, ali email že obstaja
    if (empty($napake)) {
        $stmt = $pdo->prepare("SELECT user_id FROM uporabnik WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $napake[] = "Ta e-poštni naslov je že registriran.";
        }
    }

    // Če NI napak → shrani uporabnika in pošlji mail
    if (empty($napake)) {
        $hash = password_hash($geslo, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO uporabnik 
            (ime, priimek, email, geslo, datum_registracije, vloga_id)
            VALUES (?, ?, ?, ?, CURDATE(), 1)
        ");

        if ($stmt->execute([$ime, $priimek, $email, $hash])) {
            
            // --- POŠILJANJE E-POŠTE ---
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'gaia.kolenc@gmail.com'; 
                $mail->Password   = 'jkgkqlledcwrojbi'; // Tvoje preverjeno geslo
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                // Popravek za XAMPP
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail->setFrom('gaia.kolenc@gmail.com', 'Med vrsticami');
                $mail->addAddress($email, $ime . ' ' . $priimek);

                $mail->isHTML(true);
                $mail->Subject = 'Dobrodošli v portalu Med vrsticami!';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; padding: 30px; border: 1px solid #eee; border-radius: 20px; max-width: 500px;'>
                        <h2 style='color: #FF6B6B; font-style: italic;'>Pozdravljeni, $ime!</h2>
                        <p style='color: #555;'>Vaša registracija je bila uspešna. Veseli smo, da ste se nam pridružili v portalu <b>Med vrsticami</b>.</p>
                        <p style='color: #555;'>Zdaj se lahko prijavite s svojim e-naslovom: <b>$email</b></p>
                        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #888;'>To je samodejno generirano sporočilo.</p>
                    </div>";

                $mail->send();
                $uspeh = "Registracija je uspela! Poslali smo ti pozdravni e-mail.";
            } catch (Exception $e) {
                // Če mail ne gre skozi, uporabnika vseeno obvestimo, da je registriran
                $uspeh = "Registracija je uspela, vendar nismo mogli poslati maila.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Registracija | Med vrsticami</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Dodal sem malo tvojega stila, da bo usklajeno z loginom */
        body { background: linear-gradient(135deg, #FF6B6B 0%, #FFD93D 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Poppins', sans-serif; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 40px; width: 100%; max-width: 450px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; }
        h1 { font-style: italic; color: #2D3436; margin-bottom: 10px; }
        input { width: 100%; padding: 12px; margin: 8px 0; border-radius: 15px; border: 1px solid #eee; background: #FFF9F2; box-sizing: border-box; }
        label { display: block; text-align: left; font-size: 12px; font-weight: bold; color: #FF6B6B; margin-top: 10px; margin-left: 5px; }
        .btn-primary { width: 100%; background: #2D3436; color: white; padding: 15px; border-radius: 25px; border: none; cursor: pointer; margin-top: 20px; font-weight: bold; }
        .error-box { background: #FFF5F5; color: #FF6B6B; padding: 10px; border-radius: 15px; margin-bottom: 20px; font-size: 13px; text-align: left; }
        .success-box { background: #F0FFF4; color: #2F855A; padding: 15px; border-radius: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Med vrsticami<span>.</span></h1>
    <p style="color: #888; font-size: 14px;">Ustvarite svoj račun in začnite pisati.</p>

    <?php if (!empty($napake)): ?>
        <div class="error-box">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($napake as $n): ?>
                    <li><?= htmlspecialchars($n) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($uspeh): ?>
        <div class="success-box">
            <?= htmlspecialchars($uspeh) ?><br><br>
            <a href="login.php" style="color: #2D3436; font-weight: bold;">➡ Pojdi na prijavo</a>
        </div>
    <?php else: ?>
        <form method="post">
            <label>Ime *</label>
            <input type="text" name="ime" value="<?= htmlspecialchars($_POST['ime'] ?? '') ?>" placeholder="Gaia" required>

            <label>Priimek *</label>
            <input type="text" name="priimek" value="<?= htmlspecialchars($_POST['priimek'] ?? '') ?>" placeholder="Kolenc" required>

            <label>E-pošta *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="gaia@primer.com" required>

            <label>Geslo *</label>
            <input type="password" name="geslo" placeholder="Vsaj 6 znakov" required>

            <label>Ponovi geslo *</label>
            <input type="password" name="geslo2" placeholder="Ponovno vpiši geslo" required>

            <button type="submit" class="btn-primary">Ustvari račun</button>
        </form>
    <?php endif; ?>

    <p style="margin-top: 20px; font-size: 13px; color: #888;">Že imaš račun? <a href="login.php" style="color: #FF6B6B; font-weight: bold; text-decoration: none;">Prijava</a></p>
</div>

</body>
</html>
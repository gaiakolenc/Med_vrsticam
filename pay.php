<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }

// Stripe Secret Key (Vstavi svojega tukaj)
$stripe_secret_key = 'sk_test_TVOJA_SKRIVNA_KODA';

if (isset($_POST['checkout'])) {
    require_once 'vendor/autoload.php'; // Če uporabljaš Composer
    // Alternativa: Če nimaš Composerja, uporabi Stripe Payment Links v nadzorni plošči
    
    // Za najenostavnejšo šolsko implementacijo brez knjižnic:
    header("Location: https://buy.stripe.com/test_tvoj_link_ustvarjen_v_nadzorni_plošči");
    exit();
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Nadgradnja | Med vrsticami</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pay-container { max-width: 500px; margin: 100px auto; background: white; padding: 50px; border-radius: 40px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .price-tag { font-size: 48px; font-weight: 800; color: #FF6B6B; margin: 20px 0; }
        .stripe-button { background: #635bff; color: white; padding: 18px 35px; border-radius: 30px; border: none; font-weight: 600; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .stripe-button:hover { background: #5444d1; transform: translateY(-3px); }
    </style>
</head>
<body style="background: #FFF9F2;">
    <div class="pay-container">
        <div style="font-size: 50px;">💎</div>
        <h1>Postani Premium</h1>
        <p>Pridobi neomejen dostop do vseh zgodb in podpri slovenske ustvarjalce.</p>
        <div class="price-tag">4.99€</div>
        <form action="" method="POST">
            <a href="https://buy.stripe.com/test_tvoj_link" class="stripe-button" style="text-decoration:none; display:inline-block;">Plačaj varno s Stripe</a>
        </form>
        <p style="margin-top: 20px; font-size: 12px; color: #999;">Plačilo bo izvedeno v Stripe testnem okolju.</p>
    </div>
</body>
</html>
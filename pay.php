<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Postani Premium | Med vrsticami</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pay-card { background: white; padding: 40px; border-radius: 20px; text-align: center; max-width: 400px; margin: 100px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .price { font-size: 40px; font-weight: 800; color: #FF6B6B; margin: 20px 0; }
        .btn-stripe { background: #635bff; color: white; padding: 15px 30px; border-radius: 10px; text-decoration: none; display: inline-block; font-weight: 600; }
    </style>
</head>
<body style="background: #FFF9F2;">
    <div class="pay-card">
        <h1>Premium članstvo</h1>
        <p>Pridobi dostop do ekskluzivnih zgodb in podpri avtorje.</p>
        <div class="price">4.99€ <small>/ mesec</small></div>
        
        <a href="success.php" class="btn-stripe">Plačaj s kartico</a>
        <p style="font-size: 10px; margin-top: 15px; color: #999;">(Trenutno v testnem načinu - klikni za simulacijo plačila)</p>
    </div>
</body>
</html>
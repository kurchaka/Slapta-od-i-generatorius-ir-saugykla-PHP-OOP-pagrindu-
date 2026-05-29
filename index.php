<?php
session_start();
require_once 'classes.php';

// Atsijungimo apdorojimas
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Jei jau prisijungęs – einam į dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = new User();
    
    if (isset($_POST['register'])) {
        if ($u->register($_POST['user'], $_POST['pass'])) {
            $msg = "<span style='color:green;'>Registracija sėkminga! Dabar galite prisijungti.</span>";
        } else {
            $msg = "<span style='color:red;'>Klaida: Vartotojo vardas jau užimtas.</span>";
        }
    } 
    
    if (isset($_POST['login'])) {
        if ($u->login($_POST['user'], $_POST['pass'])) {
            header("Location: dashboard.php");
            exit;
        } else {
            $msg = "<span style='color:red;'>Neteisingas vardas arba slaptažodis.</span>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Prisijungimas / Registracija</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
</head>
<body>
    <h1 style="text-align: center;">Slaptažodžių Generavimo ir Saugojimo Sistema (OOP)</h1>
    <div style="text-align: center; margin-bottom: 20px;"><?= $msg ?></div>

    <div style="display: flex; justify-content: space-around; gap: 20px;">
        <form method="POST" style="flex: 1; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
            <h2>Prisijungimas</h2>
            <label>Vartotojo vardas:</label>
            <input type="text" name="user" required>
            <label>Slaptažodis:</label>
            <input type="password" name="pass" required>
            <button type="submit" name="login" style="width: 100%;">Prisijungti</button>
        </form>

        <form method="POST" style="flex: 1; padding: 20px; border: 1px solid #ccc; border-radius: 8px;">
            <h2>Registracija</h2>
            <label>Naujas vartotojo vardas:</label>
            <input type="text" name="user" required>
            <label>Slaptažodis:</label>
            <input type="password" name="pass" required>
            <button type="submit" name="register" style="width: 100%; background-color: #2196F3;">Registruotis</button>
        </form>
    </div>
</body>
</html>
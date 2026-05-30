<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'classes.php';
$db = DB::connect();
$msg = '';
$generated = '';

// 1. GENERAVIMO VEIKSMAS
if (isset($_POST['generate'])) {
    try {
        $gen = new PasswordGenerator();
        $generated = $gen->generate($_POST['len'], $_POST['low'], $_POST['up'], $_POST['num'], $_POST['spec']);
    } catch (Exception $e) {
        $msg = "<span style='color:red;'>".$e->getMessage()."</span>";
    }
}

// 2. SAUGOJIMO VEIKSMAS
if (isset($_POST['save'])) {
    // Šifruojame slaptažodį su unikaliu vartotojo raktu iš sesijos
    $encPass = Encryptor::encrypt($_POST['pass_val'], $_SESSION['user_key']);
    
    $stmt = $db->prepare("INSERT INTO passwords (user_id, title, encrypted_password) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['title'], $encPass]);
    $msg = "<span style='color:green;'>Slaptažodis sėkmingai išsaugotas!</span>";
}

// 3. SLAPTAŽODŽIO KEITIMO VEIKSMAS
if (isset($_POST['change_pass'])) {
    $u = new User();
    if ($u->updatePassword($_SESSION['user_id'], $_POST['old'], $_POST['new'])) {
        $msg = "<span style='color:green;'>Sistemos slaptažodis pakeistas, raktas saugiai perkoduotas!</span>";
    } else {
        $msg = "<span style='color:red;'>Klaida: Neteisingas dabartinis slaptažodis.</span>";
    }
}

// Gauname vartotojo slaptažodžių sąrašą
$stmt = $db->prepare("SELECT * FROM passwords WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$myPasswords = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Valdymo Skydas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Sveiki, <u><?= htmlspecialchars($_SESSION['username']) ?></u></h2>
        <a href="index.php?action=logout"><button style="background-color: #f44336;">Atsijungti</button></a>
    </div>

    <div style="margin: 15px 0; font-get: bold;"><?= $msg ?></div>
    <hr>

    <h3>1. Slaptažodžių generatorius</h3>
    <form method="POST">
        <label>Bendras ilgis:</label>
        <input type="number" name="len" value="9" min="1" required>
        <div style="display: flex; gap: 10px;">
            <label>Mažosios: <input type="number" name="low" value="2" min="0" required></label>
            <label>Didžiosios: <input type="number" name="up" value="3" min="0" required></label>
            <label>Skaičiai: <input type="number" name="num" value="2" min="0" required></label>
            <label>Specialūs: <input type="number" name="spec" value="2" min="0" required></label>
        </div>
        <button type="submit" name="generate">Sugeneruoti slaptažodį</button>
    </form>

    <?php if ($generated): ?>
        <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 15px;">
            <p>Sugeneruotas rezultatas: <code style="font-size: 1.3em; color: #0d47a1;"><?= htmlspecialchars($generated) ?></code></p>
            
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="pass_val" value="<?= htmlspecialchars($generated) ?>">
                <label>Kur bus naudojamas slaptažodis (Svetainės/Programos pavadinimas):</label>
                <input type="text" name="title" placeholder="Pvz. Gmail, Facebook" required>
                <button type="submit" name="save" style="background-color: #4CAF50;">Išsaugoti šį įrašą į DB</button>
            </form>
        </div>
    <?php endif; ?>

    <hr>

    <h3>2. Mano saugomi slaptažodžiai</h3>
    <table>
        <thead>
            <tr>
                <th>Pavadinimas / Svetainė</th>
                <th>Slaptažodis (Dešifruotas iš DB)</th>
                <th>Įrašo data/laikas</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($myPasswords) === 0): ?>
                <tr><td colspan="3" style="text-align: center;">Sąrašas tuščias.</td></tr>
            <?php else: ?>
                <?php foreach ($myPasswords as $p): 
                    // Dešifruojame tiesiogiai atvaizdavimui naudojant sesijoje esantį vartotojo raktą
                    $decrypted = Encryptor::decrypt($p['encrypted_password'], $_SESSION['user_key']);
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                        <td><code><?= htmlspecialchars($decrypted) ?></code></td>
                        <td><small><?= $p['created_at'] ?></small></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <hr>

    <h3>3. Saugumo nustatymai</h3>
    <form method="POST">
        <label>Dabartinis prisijungimo slaptažodis:</label>
        <input type="password" name="old" required>
        <label>Naujas prisijungimo slaptažodis:</label>
        <input type="password" name="new" required>
        <button type="submit" name="change_pass" style="background-color: #ff9800;">Pakeisti slaptažodį ir perkoduoti RAKTĄ</button>
    </form>
</body>
</html>
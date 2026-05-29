<?php
// 1. DUOMENŲ BAZĖS VALDYMAS
class DB {
    public static function connect() {
        $host = 'localhost';
        $db   = 'password_manager';
        $user = 'root';
        $pass = ''; // Jei naudojate MAMP/Mac, įrašykite 'root'
        
        try {
            return new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("DB Klaida: " . $e->getMessage());
        }
    }
}

// 2. KRIPTOGRAFIJA (AES-256-CBC)
class Encryptor {
    public static function encrypt($data, $secret) {
        $key = hash('sha256', $secret, true);
        $iv = random_bytes(16);
        $cipherText = openssl_encrypt($data, "aes-256-cbc", $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipherText);
    }

    public static function decrypt($cipherText, $secret) {
        $key = hash('sha256', $secret, true);
        $c = base64_decode($cipherText);
        $iv = substr($c, 0, 16);
        $rawCipher = substr($c, 16);
        return openssl_decrypt($rawCipher, "aes-256-cbc", $key, OPENSSL_RAW_DATA, $iv);
    }
}

// 3. VARTOTOJO LOGIKA
class User {
    private $db;

    public function __construct() {
        $this->db = DB::connect();
    }

    public function register($username, $plainPassword) {
        // Patikrinam ar nėra dublikatų
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) return false;

        // Maišom prisijungimo slaptažodį
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        
        // Generuojam unikalų sistemos RAKTĄ šiam vartotojui
        $userKey = bin2hex(random_bytes(16)); 
        
        // Šifruojam RAKTĄ su vartotojo PLAIN slaptažodžiu
        $encryptedKey = Encryptor::encrypt($userKey, $plainPassword);

        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, encrypted_key) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $hash, $encryptedKey]);
    }

    public function login($username, $plainPassword) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $u = $stmt->fetch();

        if ($u && password_verify($plainPassword, $u['password_hash'])) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['username'] = $u['username'];
            // Iššifruojam RAKTĄ prisijungimo metu ir padedam į sesiją saugiam naudojimui
            $_SESSION['user_key'] = Encryptor::decrypt($u['encrypted_key'], $plainPassword);
            return true;
        }
        return false;
    }

    public function updatePassword($userId, $oldPass, $newPass) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch();

        if ($u && password_verify($oldPass, $u['password_hash'])) {
            // Dekoduojam esamą raktą su senu slaptažodžiu
            $currentKey = Encryptor::decrypt($u['encrypted_key'], $oldPass);
            // Užkoduojam TĄ PATĮ raktą su NAUJU slaptažodžiu
            $newEncKey = Encryptor::encrypt($currentKey, $newPass);
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);

            $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, encrypted_key = ? WHERE id = ?");
            if ($stmt->execute([$newHash, $newEncKey, $userId])) {
                $_SESSION['user_key'] = $currentKey; // Atnaujinam sesiją
                return true;
            }
        }
        return false;
    }
}

// 4. SLAPTAŽODŽIŲ GENERATORIUS
class PasswordGenerator {
    public function generate($length, $low, $up, $num, $spec) {
        if (($low + $up + $num + $spec) != $length) {
            throw new Exception("Dalių suma turi būti lygi bendram slaptažodžio ilgiui!");
        }

        $pool = [];
        // Sugeneruojam tikslius kiekius pagal nurodytus vienetus
        for ($i = 0; $i < $low; $i++)  $pool[] = chr(rand(97, 122)); // a-z
        for ($i = 0; $i < $up; $i++)   $pool[] = chr(rand(65, 90));  // A-Z
        for ($i = 0; $i < $num; $i++)  $pool[] = chr(rand(48, 57));  // 0-9
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        for ($i = 0; $i < $spec; $i++) $pool[] = $symbols[rand(0, strlen($symbols) - 1)];

        shuffle($pool); // Sumaišom tvarką
        return implode('', $pool);
    }
}
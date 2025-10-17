<?php
// Configuration de la base de données
define('DB_HOST', 'we01io.myd.infomaniak.com');
define('DB_PORT', '3306');
define('DB_NAME', 'we01io_pizza');
define('DB_USER', 'we01io_tfeAdmin');
define('DB_PASS', 'RBM91210chat!');

// Configuration CORS pour permettre les requêtes depuis le frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Classe de connexion à la base de données
class Database {
    private $conn;
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur de connexion à la base de données']);
            exit();
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function close() {
        $this->conn = null;
    }
}

// Fonction pour envoyer une réponse JSON
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Fonction pour envoyer une erreur
function sendError($message, $statusCode = 400) {
    sendResponse(['error' => $message], $statusCode);
}

// Fonction pour valider les données JSON
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Données JSON invalides', 400);
    }
    
    return $data;
}

// Fonction pour hasher les mots de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fonction pour vérifier les mots de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Fonction pour générer un token JWT simple (pour la session)
function generateToken($userId) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'exp' => time() + (24 * 60 * 60) // 24 heures
    ]));
    $signature = base64_encode(hash_hmac('sha256', $header . '.' . $payload, 'pizza_secret_key', true));
    
    return $header . '.' . $payload . '.' . $signature;
}

// Fonction pour vérifier un token
function verifyToken($token) {
    if (!$token) return false;
    
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    
    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload || $payload['exp'] < time()) return false;
    
    $expectedSignature = base64_encode(hash_hmac('sha256', $parts[0] . '.' . $parts[1], 'pizza_secret_key', true));
    if ($parts[2] !== $expectedSignature) return false;
    
    return $payload['user_id'];
}

// Fonction pour obtenir l'utilisateur connecté
function getCurrentUser() {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$token) return null;
    
    $userId = verifyToken($token);
    if (!$userId) return null;
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    } catch (Exception $e) {
        return null;
    }
}

// Générer un ID unique pour les utilisateurs
function generateUserId() {
    return 'USER' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}
?>

<?php
require_once 'config.php';

// Récupérer la méthode HTTP et l'action
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$action = end($pathParts);

try {
    switch ($method) {
        case 'POST':
            handlePostRequest($action);
            break;
        case 'GET':
            handleGetRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        default:
            sendError('Méthode non autorisée', 405);
    }
} catch (Exception $e) {
    error_log("Erreur API Auth: " . $e->getMessage());
    sendError('Erreur interne du serveur', 500);
}

function handlePostRequest($action) {
    switch ($action) {
        case 'login':
            login();
            break;
        case 'register':
            register();
            break;
        case 'logout':
            logout();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handleGetRequest($action) {
    switch ($action) {
        case 'profile':
            getProfile();
            break;
        case 'verify':
            verifySession();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handlePutRequest($action) {
    switch ($action) {
        case 'profile':
            updateProfile();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function login() {
    $data = getJsonInput();
    
    if (!isset($data['identifier']) || !isset($data['password'])) {
        sendError('Identifiant et mot de passe requis');
    }
    
    $identifier = trim($data['identifier']);
    $password = $data['password'];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Chercher l'utilisateur par email ou ID
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR id = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            sendError('Identifiants incorrects', 401);
        }
        
        // Mettre à jour la dernière connexion
        $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // Générer le token
        $token = generateToken($user['id']);
        
        // Préparer les données utilisateur (sans le hash du mot de passe)
        unset($user['password_hash']);
        $user['token'] = $token;
        
        sendResponse([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => $user
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur login: " . $e->getMessage());
        sendError('Erreur lors de la connexion');
    }
}

function register() {
    $data = getJsonInput();
    
    // Validation des champs obligatoires
    $required = ['nom', 'prenom', 'email', 'discord', 'password'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendError("Le champ $field est obligatoire");
        }
    }
    
    $email = trim($data['email']);
    $password = $data['password'];
    
    // Validation email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Format d\'email invalide');
    }
    
    // Validation mot de passe
    if (strlen($password) < 6) {
        sendError('Le mot de passe doit contenir au moins 6 caractères');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendError('Un compte avec cet email existe déjà');
        }
        
        // Générer un ID unique
        do {
            $userId = generateUserId();
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
        } while ($stmt->fetch());
        
        // Hasher le mot de passe
        $passwordHash = hashPassword($password);
        
        // Insérer le nouvel utilisateur
        $stmt = $conn->prepare("
            INSERT INTO users (id, nom, prenom, email, discord, phone, address, password_hash, newsletter, member_since)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, YEAR(CURDATE()))
        ");
        
        $stmt->execute([
            $userId,
            trim($data['nom']),
            trim($data['prenom']),
            $email,
            trim($data['discord']),
            isset($data['phone']) ? trim($data['phone']) : null,
            isset($data['address']) ? trim($data['address']) : null,
            $passwordHash,
            isset($data['newsletter']) ? (bool)$data['newsletter'] : false
        ]);
        
        // Récupérer l'utilisateur créé
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Générer le token
        $token = generateToken($user['id']);
        
        // Préparer les données utilisateur
        unset($user['password_hash']);
        $user['token'] = $token;
        
        sendResponse([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'user' => $user
        ], 201);
        
    } catch (Exception $e) {
        error_log("Erreur register: " . $e->getMessage());
        sendError('Erreur lors de la création du compte');
    }
}

function getProfile() {
    $user = getCurrentUser();
    
    if (!$user) {
        sendError('Non authentifié', 401);
    }
    
    // Récupérer les statistiques de l'utilisateur
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Compter les contacts de l'utilisateur
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contacts WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $contactsCount = $stmt->fetch()['count'];
        
        // Compter les réservations de l'utilisateur
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $reservationsCount = $stmt->fetch()['count'];
        
        $user['stats'] = [
            'contacts' => $contactsCount,
            'reservations' => $reservationsCount
        ];
        
        unset($user['password_hash']);
        
        sendResponse([
            'success' => true,
            'user' => $user
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getProfile: " . $e->getMessage());
        sendError('Erreur lors de la récupération du profil');
    }
}

function updateProfile() {
    $user = getCurrentUser();
    
    if (!$user) {
        sendError('Non authentifié', 401);
    }
    
    $data = getJsonInput();
    
    // Vérification du mot de passe actuel (obligatoire pour toute modification)
    if (!isset($data['current_password']) || !verifyPassword($data['current_password'], $user['password_hash'])) {
        sendError('Mot de passe actuel incorrect');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Préparer les champs à mettre à jour
        $updates = [];
        $params = [];
        
        $allowedFields = ['nom', 'prenom', 'email', 'discord', 'phone', 'address', 'newsletter'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'email') {
                    // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$data[$field], $user['id']]);
                    if ($stmt->fetch()) {
                        sendError('Cet email est déjà utilisé par un autre compte');
                    }
                    
                    if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        sendError('Format d\'email invalide');
                    }
                }
                
                $updates[] = "$field = ?";
                $params[] = $field === 'newsletter' ? (bool)$data[$field] : trim($data[$field]);
            }
        }
        
        // Gestion du changement de mot de passe
        if (isset($data['new_password']) && !empty($data['new_password'])) {
            if (strlen($data['new_password']) < 6) {
                sendError('Le nouveau mot de passe doit contenir au moins 6 caractères');
            }
            
            if ($data['new_password'] !== $data['confirm_new_password']) {
                sendError('Les nouveaux mots de passe ne correspondent pas');
            }
            
            $updates[] = "password_hash = ?";
            $params[] = hashPassword($data['new_password']);
        }
        
        if (empty($updates)) {
            sendError('Aucune modification à effectuer');
        }
        
        // Mettre à jour l'utilisateur
        $params[] = $user['id'];
        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Récupérer l'utilisateur mis à jour
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $updatedUser = $stmt->fetch();
        
        unset($updatedUser['password_hash']);
        
        sendResponse([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'user' => $updatedUser
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur updateProfile: " . $e->getMessage());
        sendError('Erreur lors de la mise à jour du profil');
    }
}

function verifySession() {
    $user = getCurrentUser();
    
    if (!$user) {
        sendResponse(['valid' => false], 401);
    }
    
    unset($user['password_hash']);
    
    sendResponse([
        'valid' => true,
        'user' => $user
    ]);
}

function logout() {
    // Pour une déconnexion côté serveur, on pourrait invalider le token
    // Ici on retourne simplement une confirmation
    sendResponse([
        'success' => true,
        'message' => 'Déconnexion réussie'
    ]);
}
?>

<?php
require_once 'config.php';

// Récupérer la méthode HTTP et l'action
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$action = end($pathParts);

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        case 'DELETE':
            handleDeleteRequest($action);
            break;
        default:
            sendError('Méthode non autorisée', 405);
    }
} catch (Exception $e) {
    error_log("Erreur API Admin: " . $e->getMessage());
    sendError('Erreur interne du serveur', 500);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'stats':
            getStats();
            break;
        case 'users':
            getUsers();
            break;
        case 'dashboard':
            getDashboard();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handlePostRequest($action) {
    switch ($action) {
        case 'users':
            createUser();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handlePutRequest($action) {
    switch ($action) {
        case 'user':
            updateUser();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handleDeleteRequest($action) {
    switch ($action) {
        case 'user':
            deleteUser();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

// Vérifier les permissions admin
function requireAdmin() {
    $user = getCurrentUser();
    
    if (!$user || $user['role'] !== 'admin') {
        sendError('Accès non autorisé - Droits administrateur requis', 403);
    }
    
    return $user;
}

function getStats() {
    requireAdmin();
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Statistiques générales
        $stats = [];
        
        // Nombre total d'utilisateurs
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Nouveaux utilisateurs cette semaine
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['new_users_week'] = $stmt->fetch()['count'];
        
        // Nombre total de contacts
        $stmt = $conn->query("SELECT COUNT(*) as count FROM contacts");
        $stats['total_contacts'] = $stmt->fetch()['count'];
        
        // Nouveaux contacts aujourd'hui
        $stmt = $conn->query("SELECT COUNT(*) as count FROM contacts WHERE DATE(created_at) = CURDATE()");
        $stats['new_contacts_today'] = $stmt->fetch()['count'];
        
        // Nombre total de réservations
        $stmt = $conn->query("SELECT COUNT(*) as count FROM reservations");
        $stats['total_reservations'] = $stmt->fetch()['count'];
        
        // Réservations en attente
        $stmt = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'en_attente'");
        $stats['pending_reservations'] = $stmt->fetch()['count'];
        
        // Nombre total d'articles
        $stmt = $conn->query("SELECT COUNT(*) as count FROM articles");
        $stats['total_articles'] = $stmt->fetch()['count'];
        
        // Articles publiés
        $stmt = $conn->query("SELECT COUNT(*) as count FROM articles WHERE status = 'published'");
        $stats['published_articles'] = $stmt->fetch()['count'];
        
        // Statistiques par statut des contacts
        $stmt = $conn->query("
            SELECT status, COUNT(*) as count 
            FROM contacts 
            GROUP BY status
        ");
        $contactsByStatus = [];
        while ($row = $stmt->fetch()) {
            $contactsByStatus[$row['status']] = $row['count'];
        }
        $stats['contacts_by_status'] = $contactsByStatus;
        
        // Statistiques par statut des réservations
        $stmt = $conn->query("
            SELECT status, COUNT(*) as count 
            FROM reservations 
            GROUP BY status
        ");
        $reservationsByStatus = [];
        while ($row = $stmt->fetch()) {
            $reservationsByStatus[$row['status']] = $row['count'];
        }
        $stats['reservations_by_status'] = $reservationsByStatus;
        
        // Activité des 7 derniers jours
        $stmt = $conn->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as users
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $dailyUsers = $stmt->fetchAll();
        
        $stmt = $conn->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as contacts
            FROM contacts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $dailyContacts = $stmt->fetchAll();
        
        $stmt = $conn->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as reservations
            FROM reservations 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $dailyReservations = $stmt->fetchAll();
        
        $stats['daily_activity'] = [
            'users' => $dailyUsers,
            'contacts' => $dailyContacts,
            'reservations' => $dailyReservations
        ];
        
        sendResponse([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getStats: " . $e->getMessage());
        sendError('Erreur lors de la récupération des statistiques');
    }
}

function getUsers() {
    requireAdmin();
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->query("
            SELECT 
                id, nom, prenom, email, discord, phone, role, 
                newsletter, member_since, orders_count, total_spent, 
                loyalty_points, created_at, last_login
            FROM users 
            ORDER BY created_at DESC
        ");
        
        $users = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'users' => $users
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getUsers: " . $e->getMessage());
        sendError('Erreur lors de la récupération des utilisateurs');
    }
}

function getDashboard() {
    requireAdmin();
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Activité récente - derniers contacts
        $stmt = $conn->query("
            SELECT c.*, u.email as user_email 
            FROM contacts c 
            LEFT JOIN users u ON c.user_id = u.id 
            ORDER BY c.created_at DESC 
            LIMIT 5
        ");
        $recentContacts = $stmt->fetchAll();
        
        // Activité récente - dernières réservations
        $stmt = $conn->query("
            SELECT r.*, u.email as user_email 
            FROM reservations r 
            LEFT JOIN users u ON r.user_id = u.id 
            ORDER BY r.created_at DESC 
            LIMIT 5
        ");
        $recentReservations = $stmt->fetchAll();
        
        // Derniers utilisateurs inscrits
        $stmt = $conn->query("
            SELECT id, nom, prenom, email, role, created_at 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $recentUsers = $stmt->fetchAll();
        
        // Réservations du jour
        $stmt = $conn->query("
            SELECT r.*, u.email as user_email 
            FROM reservations r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE DATE(r.reservation_date) = CURDATE()
            ORDER BY r.reservation_time ASC
        ");
        $todayReservations = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'dashboard' => [
                'recent_contacts' => $recentContacts,
                'recent_reservations' => $recentReservations,
                'recent_users' => $recentUsers,
                'today_reservations' => $todayReservations
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getDashboard: " . $e->getMessage());
        sendError('Erreur lors de la récupération du tableau de bord');
    }
}

function createUser() {
    requireAdmin();
    
    $data = getJsonInput();
    
    // Validation des champs obligatoires
    $required = ['nom', 'prenom', 'email', 'password'];
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
    
    // Validation du rôle
    $role = isset($data['role']) ? $data['role'] : 'client';
    if (!in_array($role, ['client', 'admin'])) {
        sendError('Rôle invalide');
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
            INSERT INTO users (id, nom, prenom, email, discord, phone, address, password_hash, role, newsletter, member_since)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, YEAR(CURDATE()))
        ");
        
        $stmt->execute([
            $userId,
            trim($data['nom']),
            trim($data['prenom']),
            $email,
            isset($data['discord']) ? trim($data['discord']) : null,
            isset($data['phone']) ? trim($data['phone']) : null,
            isset($data['address']) ? trim($data['address']) : null,
            $passwordHash,
            $role,
            isset($data['newsletter']) ? (bool)$data['newsletter'] : false
        ]);
        
        // Récupérer l'utilisateur créé
        $stmt = $conn->prepare("
            SELECT id, nom, prenom, email, discord, phone, role, newsletter, member_since, created_at 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'user' => $user
        ], 201);
        
    } catch (Exception $e) {
        error_log("Erreur createUser: " . $e->getMessage());
        sendError('Erreur lors de la création de l\'utilisateur');
    }
}

function updateUser() {
    requireAdmin();
    
    $data = getJsonInput();
    
    if (!isset($data['id'])) {
        sendError('ID utilisateur requis');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier que l'utilisateur existe
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('Utilisateur non trouvé', 404);
        }
        
        // Préparer les champs à mettre à jour
        $updates = [];
        $params = [];
        
        $allowedFields = ['nom', 'prenom', 'email', 'discord', 'phone', 'address', 'role', 'newsletter', 'orders_count', 'total_spent', 'loyalty_points'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'email') {
                    // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$data[$field], $data['id']]);
                    if ($stmt->fetch()) {
                        sendError('Cet email est déjà utilisé par un autre compte');
                    }
                    
                    if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        sendError('Format d\'email invalide');
                    }
                }
                
                if ($field === 'role' && !in_array($data[$field], ['client', 'admin'])) {
                    sendError('Rôle invalide');
                }
                
                $updates[] = "$field = ?";
                if ($field === 'newsletter') {
                    $params[] = (bool)$data[$field];
                } elseif (in_array($field, ['orders_count', 'total_spent', 'loyalty_points'])) {
                    $params[] = (float)$data[$field];
                } else {
                    $params[] = trim($data[$field]);
                }
            }
        }
        
        // Gestion du changement de mot de passe
        if (isset($data['new_password']) && !empty($data['new_password'])) {
            if (strlen($data['new_password']) < 6) {
                sendError('Le nouveau mot de passe doit contenir au moins 6 caractères');
            }
            
            $updates[] = "password_hash = ?";
            $params[] = hashPassword($data['new_password']);
        }
        
        if (empty($updates)) {
            sendError('Aucune modification à effectuer');
        }
        
        // Mettre à jour l'utilisateur
        $params[] = $data['id'];
        $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Récupérer l'utilisateur mis à jour
        $stmt = $conn->prepare("
            SELECT id, nom, prenom, email, discord, phone, role, newsletter, 
                   member_since, orders_count, total_spent, loyalty_points, 
                   created_at, updated_at, last_login
            FROM users WHERE id = ?
        ");
        $stmt->execute([$data['id']]);
        $updatedUser = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $updatedUser
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur updateUser: " . $e->getMessage());
        sendError('Erreur lors de la mise à jour de l\'utilisateur');
    }
}

function deleteUser() {
    requireAdmin();
    
    $data = getJsonInput();
    
    if (!isset($data['id'])) {
        sendError('ID utilisateur requis');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier que l'utilisateur existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        if (!$stmt->fetch()) {
            sendError('Utilisateur non trouvé', 404);
        }
        
        // Supprimer l'utilisateur (les contraintes FK mettront les user_id à NULL dans les autres tables)
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        sendResponse([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur deleteUser: " . $e->getMessage());
        sendError('Erreur lors de la suppression de l\'utilisateur');
    }
}
?>

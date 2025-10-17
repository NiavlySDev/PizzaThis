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
    error_log("Erreur API Contact: " . $e->getMessage());
    sendError('Erreur interne du serveur', 500);
}

function handlePostRequest($action) {
    switch ($action) {
        case 'contact':
            submitContact();
            break;
        case 'reservation':
            submitReservation();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handleGetRequest($action) {
    switch ($action) {
        case 'contacts':
            getContacts();
            break;
        case 'reservations':
            getReservations();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handlePutRequest($action) {
    switch ($action) {
        case 'contact':
            updateContact();
            break;
        case 'reservation':
            updateReservation();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function submitContact() {
    $data = getJsonInput();
    
    // Validation des champs obligatoires
    $required = ['nom', 'discord', 'sujet', 'message'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendError("Le champ $field est obligatoire");
        }
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Obtenir l'utilisateur connecté (optionnel)
        $user = getCurrentUser();
        $userId = $user ? $user['id'] : null;
        
        // Insérer le contact
        $stmt = $conn->prepare("
            INSERT INTO contacts (user_id, nom, discord, subject, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            trim($data['nom']),
            trim($data['discord']),
            trim($data['sujet']),
            trim($data['message'])
        ]);
        
        $contactId = $conn->lastInsertId();
        
        // Envoyer vers Discord
        sendDiscordNotification('contact', [
            'id' => $contactId,
            'nom' => trim($data['nom']),
            'discord' => trim($data['discord']),
            'subject' => trim($data['sujet']),
            'message' => trim($data['message'])
        ]);
        
        sendResponse([
            'success' => true,
            'message' => 'Votre message a été envoyé avec succès !',
            'id' => $contactId
        ], 201);
        
    } catch (Exception $e) {
        error_log("Erreur submitContact: " . $e->getMessage());
        sendError('Erreur lors de l\'envoi du message');
    }
}

function submitReservation() {
    $data = getJsonInput();
    
    // Validation des champs obligatoires
    $required = ['nom', 'discord', 'personnes', 'jour', 'heure'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendError("Le champ $field est obligatoire");
        }
    }
    
    // Validation de la date et heure
    $reservationDate = $data['jour'];
    $reservationTime = $data['heure'];
    
    if (!strtotime($reservationDate) || $reservationDate < date('Y-m-d')) {
        sendError('Date de réservation invalide');
    }
    
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $reservationTime)) {
        sendError('Heure de réservation invalide');
    }
    
    $peopleCount = intval($data['personnes']);
    if ($peopleCount < 1 || $peopleCount > 20) {
        sendError('Nombre de personnes invalide (1-20)');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Obtenir l'utilisateur connecté (optionnel)
        $user = getCurrentUser();
        $userId = $user ? $user['id'] : null;
        
        // Insérer la réservation
        $stmt = $conn->prepare("
            INSERT INTO reservations (user_id, nom, discord, people_count, reservation_date, reservation_time, message)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            trim($data['nom']),
            trim($data['discord']),
            $peopleCount,
            $reservationDate,
            $reservationTime,
            isset($data['message']) ? trim($data['message']) : null
        ]);
        
        $reservationId = $conn->lastInsertId();
        
        // Envoyer vers Discord
        sendDiscordNotification('reservation', [
            'id' => $reservationId,
            'nom' => trim($data['nom']),
            'discord' => trim($data['discord']),
            'people' => $peopleCount,
            'date' => $reservationDate,
            'time' => $reservationTime,
            'message' => isset($data['message']) ? trim($data['message']) : ''
        ]);
        
        sendResponse([
            'success' => true,
            'message' => 'Votre réservation a été enregistrée avec succès !',
            'id' => $reservationId
        ], 201);
        
    } catch (Exception $e) {
        error_log("Erreur submitReservation: " . $e->getMessage());
        sendError('Erreur lors de l\'enregistrement de la réservation');
    }
}

function getContacts() {
    $user = getCurrentUser();
    
    if (!$user) {
        sendError('Non authentifié', 401);
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($user['role'] === 'admin') {
            // Admin peut voir tous les contacts
            $stmt = $conn->prepare("
                SELECT c.*, u.email as user_email 
                FROM contacts c 
                LEFT JOIN users u ON c.user_id = u.id 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute();
        } else {
            // Utilisateur normal ne voit que ses contacts
            $stmt = $conn->prepare("
                SELECT * FROM contacts 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user['id']]);
        }
        
        $contacts = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'contacts' => $contacts
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getContacts: " . $e->getMessage());
        sendError('Erreur lors de la récupération des contacts');
    }
}

function getReservations() {
    $user = getCurrentUser();
    
    if (!$user) {
        sendError('Non authentifié', 401);
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($user['role'] === 'admin') {
            // Admin peut voir toutes les réservations
            $stmt = $conn->prepare("
                SELECT r.*, u.email as user_email 
                FROM reservations r 
                LEFT JOIN users u ON r.user_id = u.id 
                ORDER BY r.reservation_date DESC, r.reservation_time DESC
            ");
            $stmt->execute();
        } else {
            // Utilisateur normal ne voit que ses réservations
            $stmt = $conn->prepare("
                SELECT * FROM reservations 
                WHERE user_id = ? 
                ORDER BY reservation_date DESC, reservation_time DESC
            ");
            $stmt->execute([$user['id']]);
        }
        
        $reservations = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'reservations' => $reservations
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getReservations: " . $e->getMessage());
        sendError('Erreur lors de la récupération des réservations');
    }
}

function updateContact() {
    $user = getCurrentUser();
    
    if (!$user || $user['role'] !== 'admin') {
        sendError('Accès non autorisé', 403);
    }
    
    $data = getJsonInput();
    
    if (!isset($data['id']) || !isset($data['status'])) {
        sendError('ID et statut requis');
    }
    
    $allowedStatuses = ['nouveau', 'en_cours', 'resolu', 'ferme'];
    if (!in_array($data['status'], $allowedStatuses)) {
        sendError('Statut invalide');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            UPDATE contacts 
            SET status = ?, admin_response = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['status'],
            isset($data['admin_response']) ? trim($data['admin_response']) : null,
            $data['id']
        ]);
        
        if ($stmt->rowCount() === 0) {
            sendError('Contact non trouvé', 404);
        }
        
        sendResponse([
            'success' => true,
            'message' => 'Contact mis à jour avec succès'
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur updateContact: " . $e->getMessage());
        sendError('Erreur lors de la mise à jour du contact');
    }
}

function updateReservation() {
    $user = getCurrentUser();
    
    if (!$user || $user['role'] !== 'admin') {
        sendError('Accès non autorisé', 403);
    }
    
    $data = getJsonInput();
    
    if (!isset($data['id']) || !isset($data['status'])) {
        sendError('ID et statut requis');
    }
    
    $allowedStatuses = ['en_attente', 'confirmee', 'annulee', 'terminee'];
    if (!in_array($data['status'], $allowedStatuses)) {
        sendError('Statut invalide');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            UPDATE reservations 
            SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['status'],
            isset($data['admin_notes']) ? trim($data['admin_notes']) : null,
            $data['id']
        ]);
        
        if ($stmt->rowCount() === 0) {
            sendError('Réservation non trouvée', 404);
        }
        
        sendResponse([
            'success' => true,
            'message' => 'Réservation mise à jour avec succès'
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur updateReservation: " . $e->getMessage());
        sendError('Erreur lors de la mise à jour de la réservation');
    }
}

function sendDiscordNotification($type, $data) {
    $webhookUrl = 'https://discord.com/api/webhooks/1319344399207067749/JGWwPAyt16oCRwYBIHz-feJ1fLLmaDhGe3Z5LKdqOP0cMBP5N2vjNMkXiY8rpZqKzAlh';
    
    try {
        if ($type === 'contact') {
            $embed = [
                'title' => '📩 Nouveau Message de Contact',
                'description' => "**Sujet:** " . ($data['subject'] ?? 'Aucun sujet'),
                'color' => 3447003, // Bleu
                'fields' => [
                    [
                        'name' => '👤 Nom',
                        'value' => $data['nom'] ?? 'Non spécifié',
                        'inline' => true
                    ],
                    [
                        'name' => '🎮 Discord',
                        'value' => $data['discord'] ?? 'Non spécifié',
                        'inline' => true
                    ],
                    [
                        'name' => '📝 Message',
                        'value' => isset($data['message']) ? (strlen($data['message']) > 1000 ? substr($data['message'], 0, 1000) . '...' : $data['message']) : 'Aucun message',
                        'inline' => false
                    ],
                    [
                        'name' => '🆔 ID Contact',
                        'value' => '#' . ($data['id'] ?? 'N/A'),
                        'inline' => true
                    ]
                ],
                'timestamp' => date('c'),
                'footer' => [
                    'text' => 'Pizza This - Système de Contact'
                ]
            ];
            
            $payload = [
                'content' => '<@&1428738967053795479> Nouveau message de contact reçu !',
                'embeds' => [$embed]
            ];
        } else if ($type === 'reservation') {
            $reservationDate = $data['date'] ?? $data['jour'] ?? date('Y-m-d');
            $reservationTime = $data['time'] ?? $data['heure'] ?? '12:00';
            
            $embed = [
                'title' => '🍕 Nouvelle Réservation',
                'description' => "**Demande de réservation pour le " . date('d/m/Y', strtotime($reservationDate)) . "**",
                'color' => 15844367, // Orange
                'fields' => [
                    [
                        'name' => '👤 Nom',
                        'value' => $data['nom'] ?? 'Non spécifié',
                        'inline' => true
                    ],
                    [
                        'name' => '🎮 Discord',
                        'value' => $data['discord'] ?? 'Non spécifié',
                        'inline' => true
                    ],
                    [
                        'name' => '👥 Nombre de personnes',
                        'value' => ($data['people'] ?? $data['personnes'] ?? 1) . ' personne(s)',
                        'inline' => true
                    ],
                    [
                        'name' => '📅 Date',
                        'value' => date('d/m/Y', strtotime($reservationDate)),
                        'inline' => true
                    ],
                    [
                        'name' => '🕐 Heure',
                        'value' => $reservationTime,
                        'inline' => true
                    ],
                    [
                        'name' => '🆔 ID Réservation',
                        'value' => '#' . ($data['id'] ?? 'N/A'),
                        'inline' => true
                    ]
                ],
                'timestamp' => date('c'),
                'footer' => [
                    'text' => 'Pizza This - Système de Réservation'
                ]
            ];
            
            if (!empty($data['message'])) {
                $embed['fields'][] = [
                    'name' => '📝 Message supplémentaire',
                    'value' => strlen($data['message']) > 1000 ? substr($data['message'], 0, 1000) . '...' : $data['message'],
                    'inline' => false
                ];
            }
            
            $payload = [
                'content' => '<@&1428738967053795479> Nouvelle réservation reçue !',
                'embeds' => [$embed]
            ];
        } else {
            error_log("Type de notification Discord non supporté: $type");
            return false;
        }
        
        // Envoyer vers Discord avec gestion d'erreurs
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($payload),
                'timeout' => 10,
                'ignore_errors' => false
            ]
        ]);
        
        $result = file_get_contents($webhookUrl, false, $context);
        
        if ($result === false) {
            $error = error_get_last();
            error_log("Erreur envoi Discord webhook: " . ($error['message'] ?? 'Erreur inconnue'));
            return false;
        }
        
        error_log("Notification Discord envoyée avec succès pour $type #" . ($data['id'] ?? 'N/A'));
        return true;
        
    } catch (Exception $e) {
        error_log("Exception lors de l'envoi Discord: " . $e->getMessage());
        return false;
    }
}
?>

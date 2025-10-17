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
    error_log("Erreur API Articles: " . $e->getMessage());
    sendError('Erreur interne du serveur', 500);
}

function handleGetRequest($action) {
    if ($action === 'articles' || is_numeric($action)) {
        if (is_numeric($action)) {
            getArticleById($action);
        } else {
            getArticles();
        }
    } else {
        sendError('Action non trouvée', 404);
    }
}

function handlePostRequest($action) {
    switch ($action) {
        case 'articles':
            createArticle();
            break;
        default:
            sendError('Action non trouvée', 404);
    }
}

function handlePutRequest($action) {
    if (is_numeric($action)) {
        updateArticle($action);
    } else {
        sendError('Action non trouvée', 404);
    }
}

function handleDeleteRequest($action) {
    if (is_numeric($action)) {
        deleteArticle($action);
    } else {
        sendError('Action non trouvée', 404);
    }
}

function getArticles() {
    $user = getCurrentUser();
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Les utilisateurs non-admin ne voient que les articles publiés
        if (!$user || $user['role'] !== 'admin') {
            $stmt = $conn->prepare("
                SELECT id, title, excerpt, image_url, author, published_date, views_count, created_at
                FROM articles 
                WHERE status = 'published' 
                ORDER BY published_date DESC, created_at DESC
            ");
            $stmt->execute();
        } else {
            // Admin voit tous les articles
            $stmt = $conn->prepare("
                SELECT * FROM articles 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
        }
        
        $articles = $stmt->fetchAll();
        
        sendResponse([
            'success' => true,
            'articles' => $articles
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getArticles: " . $e->getMessage());
        sendError('Erreur lors de la récupération des articles');
    }
}

function getArticleById($id) {
    $user = getCurrentUser();
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Récupérer l'article
        $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        if (!$article) {
            sendError('Article non trouvé', 404);
        }
        
        // Vérifier les permissions
        if ($article['status'] !== 'published' && (!$user || $user['role'] !== 'admin')) {
            sendError('Article non accessible', 403);
        }
        
        // Incrémenter le compteur de vues (seulement pour les articles publiés)
        if ($article['status'] === 'published') {
            $updateStmt = $conn->prepare("UPDATE articles SET views_count = views_count + 1 WHERE id = ?");
            $updateStmt->execute([$id]);
            $article['views_count']++;
        }
        
        sendResponse([
            'success' => true,
            'article' => $article
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur getArticleById: " . $e->getMessage());
        sendError('Erreur lors de la récupération de l\'article');
    }
}

function createArticle() {
    $user = getCurrentUser();
    
    if (!$user || $user['role'] !== 'admin') {
        sendError('Accès non autorisé', 403);
    }
    
    $data = getJsonInput();
    
    // Validation des champs obligatoires
    $required = ['title', 'excerpt', 'content', 'author'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendError("Le champ $field est obligatoire");
        }
    }
    
    // Validation du statut
    $allowedStatuses = ['draft', 'published', 'archived'];
    $status = isset($data['status']) ? $data['status'] : 'draft';
    if (!in_array($status, $allowedStatuses)) {
        sendError('Statut invalide');
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Insérer l'article
        $stmt = $conn->prepare("
            INSERT INTO articles (title, excerpt, content, image_url, author, published_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $publishedDate = $status === 'published' ? date('Y-m-d') : (isset($data['published_date']) ? $data['published_date'] : null);
        
        $stmt->execute([
            trim($data['title']),
            trim($data['excerpt']),
            trim($data['content']),
            isset($data['image_url']) ? trim($data['image_url']) : null,
            trim($data['author']),
            $publishedDate,
            $status
        ]);
        
        $articleId = $conn->lastInsertId();
        
        // Récupérer l'article créé
        $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        $article = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'message' => 'Article créé avec succès',
            'article' => $article
        ], 201);
        
    } catch (Exception $e) {
        error_log("Erreur createArticle: " . $e->getMessage());
        sendError('Erreur lors de la création de l\'article');
    }
}

function updateArticle($id) {
    $user = getCurrentUser();
    
    if (!$user || $user['role'] !== 'admin') {
        sendError('Accès non autorisé', 403);
    }
    
    $data = getJsonInput();
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier que l'article existe
        $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();
        
        if (!$article) {
            sendError('Article non trouvé', 404);
        }
        
        // Préparer les champs à mettre à jour
        $updates = [];
        $params = [];
        
        $allowedFields = ['title', 'excerpt', 'content', 'image_url', 'author', 'published_date', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'status') {
                    $allowedStatuses = ['draft', 'published', 'archived'];
                    if (!in_array($data[$field], $allowedStatuses)) {
                        sendError('Statut invalide');
                    }
                    
                    // Si on publie l'article et qu'il n'a pas de date de publication, l'ajouter
                    if ($data[$field] === 'published' && !$article['published_date']) {
                        $updates[] = "published_date = ?";
                        $params[] = date('Y-m-d');
                    }
                }
                
                $updates[] = "$field = ?";
                $params[] = trim($data[$field]);
            }
        }
        
        if (empty($updates)) {
            sendError('Aucune modification à effectuer');
        }
        
        // Mettre à jour l'article
        $params[] = $id;
        $sql = "UPDATE articles SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Récupérer l'article mis à jour
        $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $updatedArticle = $stmt->fetch();
        
        sendResponse([
            'success' => true,
            'message' => 'Article mis à jour avec succès',
            'article' => $updatedArticle
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur updateArticle: " . $e->getMessage());
        sendError('Erreur lors de la mise à jour de l\'article');
    }
}

function deleteArticle($id) {
    $user = getCurrentUser();
    
    if (!$user || $user['role'] !== 'admin') {
        sendError('Accès non autorisé', 403);
    }
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier que l'article existe
        $stmt = $conn->prepare("SELECT id FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        
        if (!$stmt->fetch()) {
            sendError('Article non trouvé', 404);
        }
        
        // Supprimer l'article
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        
        sendResponse([
            'success' => true,
            'message' => 'Article supprimé avec succès'
        ]);
        
    } catch (Exception $e) {
        error_log("Erreur deleteArticle: " . $e->getMessage());
        sendError('Erreur lors de la suppression de l\'article');
    }
}
?>

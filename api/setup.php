<?php
// Script de configuration et initialisation de la base de données
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizza This - Configuration Base de Données</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #0c5460;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover {
            background-color: #218838;
        }
        .danger {
            background-color: #dc3545;
        }
        .danger:hover {
            background-color: #c82333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍕 Pizza This - Configuration Base de Données</h1>
        
        <?php
        $action = $_GET['action'] ?? '';
        $message = '';
        $messageType = '';

        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            echo '<div class="success">✅ Connexion à la base de données réussie</div>';
            
            if ($action === 'init') {
                echo '<h2>Initialisation de la base de données...</h2>';
                
                // Lire le fichier SQL
                $sqlFile = __DIR__ . '/database.sql';
                if (!file_exists($sqlFile)) {
                    throw new Exception('Fichier database.sql non trouvé');
                }
                
                $sql = file_get_contents($sqlFile);
                
                // Exécuter le script SQL
                $statements = explode(';', $sql);
                $executed = 0;
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $conn->exec($statement);
                            $executed++;
                        } catch (PDOException $e) {
                            // Ignorer les erreurs de table déjà existante
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                echo '<div class="error">Erreur SQL: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                        }
                    }
                }
                
                echo '<div class="success">✅ Base de données initialisée avec succès! (' . $executed . ' requêtes exécutées)</div>';
                
                // Vérifier que les comptes test existent
                $stmt = $conn->prepare("SELECT email, role FROM users WHERE id IN ('ADMIN001', 'CLIENT001')");
                $stmt->execute();
                $testAccounts = $stmt->fetchAll();
                
                if (count($testAccounts) > 0) {
                    echo '<div class="info">
                        <strong>Comptes de test disponibles:</strong><br>
                        <ul>';
                    foreach ($testAccounts as $account) {
                        echo '<li>' . htmlspecialchars($account['email']) . ' (mot de passe: ' . ($account['role'] === 'admin' ? 'admin123' : 'client123') . ')</li>';
                    }
                    echo '</ul>
                    </div>';
                }
            }
            
            if ($action === 'test') {
                echo '<h2>Test des fonctionnalités...</h2>';
                
                // Test insertion utilisateur
                $testEmail = 'test_' . time() . '@example.com';
                $stmt = $conn->prepare("
                    INSERT INTO users (id, nom, prenom, email, password_hash, role) 
                    VALUES (?, 'Test', 'User', ?, ?, 'client')
                ");
                $testId = 'TEST' . time();
                $stmt->execute([$testId, $testEmail, password_hash('test123', PASSWORD_DEFAULT)]);
                
                echo '<div class="success">✅ Insertion utilisateur test réussie (ID: ' . $testId . ')</div>';
                
                // Test insertion article
                $stmt = $conn->prepare("
                    INSERT INTO articles (title, excerpt, content, author, published_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    'Article Test',
                    'Ceci est un test',
                    'Contenu de test pour vérifier le fonctionnement',
                    'Système',
                    date('Y-m-d'),
                    'published'
                ]);
                
                echo '<div class="success">✅ Insertion article test réussie</div>';
                
                // Nettoyer les données test
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$testId]);
                
                $stmt = $conn->prepare("DELETE FROM articles WHERE title = 'Article Test'");
                $stmt->execute();
                
                echo '<div class="info">🧹 Données de test nettoyées</div>';
            }
            
            // Afficher les informations sur les tables
            echo '<h2>État des tables</h2>';
            
            $tables = ['users', 'contacts', 'reservations', 'articles', 'site_stats'];
            echo '<table>';
            echo '<tr><th>Table</th><th>Nombre d\'enregistrements</th><th>Dernière modification</th></tr>';
            
            foreach ($tables as $table) {
                try {
                    $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                    $count = $stmt->fetch()['count'];
                    
                    $stmt = $conn->query("SELECT MAX(updated_at) as last_update FROM $table WHERE updated_at IS NOT NULL");
                    $lastUpdate = $stmt->fetch()['last_update'] ?? 'N/A';
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($table) . '</td>';
                    echo '<td>' . $count . '</td>';
                    echo '<td>' . htmlspecialchars($lastUpdate) . '</td>';
                    echo '</tr>';
                } catch (Exception $e) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($table) . '</td>';
                    echo '<td colspan="2" style="color: red;">Erreur: ' . htmlspecialchars($e->getMessage()) . '</td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <h2>Actions disponibles</h2>
        
        <div style="margin: 20px 0;">
            <a href="?action=init">
                <button>🔧 Initialiser la base de données</button>
            </a>
            
            <a href="?action=test">
                <button>🧪 Tester les fonctionnalités</button>
            </a>
            
            <a href="?">
                <button>🔄 Actualiser l'état</button>
            </a>
        </div>
        
        <div class="info">
            <h3>Configuration actuelle</h3>
            <ul>
                <li><strong>Serveur:</strong> <?php echo DB_HOST . ':' . DB_PORT; ?></li>
                <li><strong>Base de données:</strong> <?php echo DB_NAME; ?></li>
                <li><strong>Utilisateur:</strong> <?php echo DB_USER; ?></li>
                <li><strong>Fichier SQL:</strong> <?php echo file_exists(__DIR__ . '/database.sql') ? '✅ Présent' : '❌ Manquant'; ?></li>
            </ul>
        </div>
        
        <div class="info">
            <h3>Étapes suivantes</h3>
            <ol>
                <li>Cliquez sur "Initialiser la base de données" pour créer les tables</li>
                <li>Testez les fonctionnalités avec le bouton "Tester"</li>
                <li>Modifiez <code>index.html</code> pour utiliser <code>app_with_api.js</code> au lieu de <code>app.js</code></li>
                <li>Testez l'application avec les comptes de test</li>
            </ol>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666;">
            <p>🍕 Pizza This - Système de gestion intégré avec base de données</p>
            <p>Pour supprimer ce fichier après configuration : <code>rm <?php echo __FILE__; ?></code></p>
        </div>
    </div>
</body>
</html>

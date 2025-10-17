<?php
// Script de mise à jour pour ajouter la colonne rp_id
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Mise à jour de la base de données - Ajout de rp_id</h2>";
    
    // Vérifier si la colonne rp_id existe déjà
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'rp_id'");
    $rpIdExists = $stmt->rowCount() > 0;
    
    if (!$rpIdExists) {
        echo "<p>Ajout de la colonne rp_id...</p>";
        
        // Ajouter la colonne rp_id
        $conn->exec("ALTER TABLE users ADD COLUMN rp_id VARCHAR(10) UNIQUE AFTER email");
        
        // Créer l'index
        $conn->exec("CREATE INDEX idx_users_rp_id ON users(rp_id)");
        
        // Mettre à jour les comptes existants avec des ID RP
        $conn->exec("UPDATE users SET rp_id = '99999' WHERE id = 'ADMIN001'");
        $conn->exec("UPDATE users SET rp_id = '12345' WHERE id = 'CLIENT001'");
        
        echo "<p style='color: green;'>✅ Colonne rp_id ajoutée avec succès !</p>";
        echo "<p>Comptes mis à jour :</p>";
        echo "<ul>";
        echo "<li>Admin (ID RP: 99999) - Connexion avec: admin@pizzathis.fr, ADMIN001 ou 99999</li>";
        echo "<li>Client (ID RP: 12345) - Connexion avec: client@test.fr, CLIENT001 ou 12345</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ La colonne rp_id existe déjà.</p>";
    }
    
    // Afficher les utilisateurs avec leurs ID RP
    echo "<h3>Utilisateurs avec ID RP :</h3>";
    $stmt = $conn->query("SELECT id, nom, prenom, email, rp_id, role FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>ID RP</th><th>Rôle</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['nom']) . "</td>";
            echo "<td>" . htmlspecialchars($user['prenom']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . ($user['rp_id'] ? htmlspecialchars($user['rp_id']) : '<em>Non défini</em>') . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucun utilisateur trouvé.</p>";
    }
    
    echo "<p><a href='setup.php'>← Retour au panneau de configuration</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

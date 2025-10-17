<?php
// Script de migration pour supprimer email et address, modifier phone
require_once 'config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "🔄 Début de la migration de la base de données...\n\n";
    
    // 1. Vérifier si la colonne email existe
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($result->rowCount() > 0) {
        echo "📧 Suppression de la colonne 'email'...\n";
        $conn->exec("ALTER TABLE users DROP COLUMN email");
        echo "✅ Colonne 'email' supprimée\n\n";
    } else {
        echo "ℹ️  Colonne 'email' déjà supprimée\n\n";
    }
    
    // 2. Vérifier si la colonne address existe
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'address'");
    if ($result->rowCount() > 0) {
        echo "🏠 Suppression de la colonne 'address'...\n";
        $conn->exec("ALTER TABLE users DROP COLUMN address");
        echo "✅ Colonne 'address' supprimée\n\n";
    } else {
        echo "ℹ️  Colonne 'address' déjà supprimée\n\n";
    }
    
    // 3. Modifier la colonne phone pour le format XXXXX-XXXXX
    echo "📞 Modification de la colonne 'phone' pour le format XXXXX-XXXXX...\n";
    $conn->exec("ALTER TABLE users MODIFY COLUMN phone VARCHAR(12) COMMENT 'Format: XXXXX-XXXXX'");
    echo "✅ Colonne 'phone' modifiée\n\n";
    
    // 4. Supprimer l'index sur email s'il existe
    try {
        $conn->exec("DROP INDEX idx_users_email ON users");
        echo "✅ Index 'idx_users_email' supprimé\n\n";
    } catch (Exception $e) {
        echo "ℹ️  Index 'idx_users_email' déjà supprimé ou n'existe pas\n\n";
    }
    
    // 5. Créer l'index sur phone s'il n'existe pas
    try {
        $conn->exec("CREATE INDEX idx_users_phone ON users(phone)");
        echo "✅ Index 'idx_users_phone' créé\n\n";
    } catch (Exception $e) {
        echo "ℹ️  Index 'idx_users_phone' existe déjà\n\n";
    }
    
    // 6. Mettre à jour les numéros de téléphone existants au format XXXXX-XXXXX
    echo "📞 Formatage des numéros de téléphone existants...\n";
    $stmt = $conn->query("SELECT id, phone FROM users WHERE phone IS NOT NULL AND phone != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $phone = preg_replace('/[^0-9]/', '', $user['phone']); // Supprimer tout sauf les chiffres
        if (strlen($phone) >= 10) {
            // Prendre les 10 premiers chiffres et formater XXXXX-XXXXX
            $formatted = substr($phone, 0, 5) . '-' . substr($phone, 5, 5);
            $updateStmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
            $updateStmt->execute([$formatted, $user['id']]);
            echo "  📱 {$user['id']}: {$user['phone']} → $formatted\n";
        }
    }
    
    echo "\n🎉 Migration terminée avec succès !\n\n";
    
    // 7. Afficher la nouvelle structure
    echo "📋 Nouvelle structure de la table 'users':\n";
    $result = $conn->query("SHOW COLUMNS FROM users");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']}: {$row['Type']}" . 
             ($row['Null'] === 'NO' ? ' NOT NULL' : '') . 
             ($row['Key'] ? " ({$row['Key']})" : '') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>

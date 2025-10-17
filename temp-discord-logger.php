<?php
// Version temporaire avec logs au lieu du webhook Discord

function sendDiscordNotificationTemp($type, $data) {
    // Log les données au lieu de les envoyer vers Discord
    $logMessage = "=== NOTIFICATION DISCORD ($type) ===\n";
    $logMessage .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $logMessage .= "Type: $type\n";
    $logMessage .= "Données:\n";
    
    if ($type === 'contact') {
        $logMessage .= "- Nom: " . ($data['nom'] ?? 'N/A') . "\n";
        $logMessage .= "- Discord: " . ($data['discord'] ?? 'N/A') . "\n";
        $logMessage .= "- Sujet: " . ($data['subject'] ?? 'N/A') . "\n";
        $logMessage .= "- Message: " . ($data['message'] ?? 'N/A') . "\n";
        $logMessage .= "- ID: " . ($data['id'] ?? 'N/A') . "\n";
    } else if ($type === 'reservation') {
        $logMessage .= "- Nom: " . ($data['nom'] ?? 'N/A') . "\n";
        $logMessage .= "- Discord: " . ($data['discord'] ?? 'N/A') . "\n";
        $logMessage .= "- Personnes: " . ($data['people'] ?? $data['personnes'] ?? 'N/A') . "\n";
        $logMessage .= "- Date: " . ($data['date'] ?? $data['jour'] ?? 'N/A') . "\n";
        $logMessage .= "- Heure: " . ($data['time'] ?? $data['heure'] ?? 'N/A') . "\n";
        $logMessage .= "- Message: " . ($data['message'] ?? 'N/A') . "\n";
        $logMessage .= "- ID: " . ($data['id'] ?? 'N/A') . "\n";
    }
    
    $logMessage .= "================================\n\n";
    
    // Log dans le fichier système et dans un fichier dédié
    error_log($logMessage);
    file_put_contents('discord-notifications.log', $logMessage, FILE_APPEND);
    
    return true;
}

// Pour utiliser cette version temporaire, remplacez l'appel dans contact.php :
// sendDiscordNotification('contact', $data);
// par :
// sendDiscordNotificationTemp('contact', $data);

echo "Version temporaire créée - Les notifications seront loggées au lieu d'être envoyées vers Discord\n";
?>

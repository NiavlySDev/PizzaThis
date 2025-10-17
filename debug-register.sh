#!/bin/bash

# Test de debug pour l'inscription
echo "🔍 Test de debug pour l'inscription"

# Test 1: Créer un utilisateur de test
echo "📋 Test 1: Données envoyées à l'API..."

curl -X POST "https://pizza.tfe91.fr/api/auth.php/register" \
     -H "Content-Type: application/json" \
     -d '{
       "nom": "TestUser",
       "prenom": "Test",
       "rp_id": "99999",
       "discord": "testuser",
       "password": "testpass123",
       "terms": true
     }' \
     -w "\nCode de réponse HTTP: %{http_code}\n" \
     -v

echo
echo "🔍 Vérification de la syntaxe PHP..."
php -l api/auth.php

echo
echo "✅ Test terminé"

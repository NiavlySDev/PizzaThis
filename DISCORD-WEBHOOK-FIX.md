# 🚨 Problème identifié avec le Webhook Discord

## 🔍 Diagnostic
L'erreur `HTTP/1.1 404 Not Found` indique que le webhook Discord n'existe plus ou a été désactivé.

**URL actuelle du webhook:**
```
https://discord.com/api/webhooks/1319344399207067749/JGWwPAyt16oCRwYBIHz-feJ1fLLmaDhGe3Z5LKdqOP0cMBP5N2vjNMkXiY8rpZqKzAlh
```

## 🛠️ Solutions possibles

### 1. Vérifier le webhook sur Discord
1. Aller dans les paramètres du serveur Discord
2. Onglet "Intégrations" → "Webhooks"
3. Vérifier si le webhook "Pizza This" existe toujours
4. Si supprimé, en créer un nouveau

### 2. Créer un nouveau webhook
Si le webhook a été supprimé :

1. **Dans Discord** :
   - Aller dans le canal where vous voulez recevoir les notifications
   - Paramètres du canal → Intégrations → Webhooks
   - Créer un webhook
   - Copier l'URL

2. **Mettre à jour le code** :
   - Remplacer l'URL dans `api/contact.php` ligne 386

### 3. Test temporaire sans webhook
En attendant la correction, vous pouvez temporairement désactiver les notifications Discord en commentant l'appel dans le code.

## 📝 Code à modifier

Dans `api/contact.php`, ligne 386 :
```php
// Remplacer cette ligne
$webhookUrl = 'https://discord.com/api/webhooks/ANCIEN_WEBHOOK';

// Par la nouvelle URL du webhook
$webhookUrl = 'https://discord.com/api/webhooks/NOUVEAU_WEBHOOK';
```

## 🧪 Test après correction
Après avoir mis à jour l'URL, relancer :
```bash
php test-discord.php
```

---

**📋 État actuel** : ❌ Webhook Discord non fonctionnel (404)  
**🎯 Action requise** : Créer un nouveau webhook Discord et mettre à jour l'URL

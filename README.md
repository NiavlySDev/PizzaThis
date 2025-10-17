# 🍕 Pizza This - Site Web avec Base de Données

Site web moderne pour Pizza This avec système de gestion intégré utilisant une base de données MySQL.

## 📋 Fonctionnalités

### 🌟 Frontend
- **SPA (Single Page Application)** avec navigation fluide
- **Page d'actualités** avec articles dynamiques et modals
- **Système de contact** et **réservations** avec validation
- **Authentification complète** (connexion/inscription/profil)
- **Panel d'administration** pour les comptes admin
- **Design responsive** optimisé mobile

### 🔧 Backend API
- **API RESTful** en PHP avec base de données MySQL
- **Système d'authentification** avec tokens JWT
- **Gestion des utilisateurs** (clients et administrateurs)
- **Gestion des contacts** et **réservations**
- **Gestion des articles** (CRUD complet)
- **Statistiques et tableau de bord** admin
- **Intégration Discord** pour notifications

### 🗄️ Base de Données
- **Serveur**: `we01io.myd.infomaniak.com:3306`
- **Base**: `we01io_pizza`
- **Tables**: users, contacts, reservations, articles, site_stats
- **Comptes de test** pré-configurés

## 🚀 Installation

### 1. Configuration de la base de données

1. Accédez à `/api/setup.php` dans votre navigateur
2. Cliquez sur "Initialiser la base de données"
3. Vérifiez que toutes les tables sont créées correctement
4. Testez les fonctionnalités avec le bouton "Tester"

### 2. Comptes de test

Après l'initialisation, deux comptes sont disponibles :

**Administrateur :**
- Email: `admin@pizzathis.fr`
- Mot de passe: `admin123`
- Accès: Panel d'administration complet

**Client :**
- Email: `client@test.fr`
- Mot de passe: `client123`
- Accès: Fonctionnalités client standard

### 3. Configuration du serveur web

Assurez-vous que :
- PHP 7.4+ est installé avec l'extension PDO MySQL
- Le fichier `.htaccess` est actif (mod_rewrite)
- Les permissions d'écriture sont configurées si nécessaire

## 📁 Structure des fichiers

```
PizzaThis/
├── index.html                 # Page principale (utilise app_with_api.js)
├── app_with_api.js           # Application JavaScript avec intégration API
├── app.js                    # Ancien fichier (localStorage) - sauvegarde
├── styles.css                # Styles complets avec authentification
├── script.js                 # Scripts d'animation existants
├── .htaccess                 # Configuration Apache pour API
│
├── api/                      # Backend API PHP
│   ├── config.php           # Configuration base de données et utilitaires
│   ├── auth.php             # Endpoints d'authentification
│   ├── contact.php          # Endpoints contact/réservation
│   ├── articles.php         # Endpoints gestion articles
│   ├── admin.php            # Endpoints administration
│   ├── database.sql         # Script de création des tables
│   └── setup.php           # Interface de configuration
│
├── components/               # Composants réutilisables
│   ├── header.html          # En-tête avec navigation
│   └── footer.html          # Pied de page
│
├── pages/                   # Pages de l'application
│   ├── accueil.html         # Page d'accueil
│   ├── actualites.html      # Page actualités (données dynamiques)
│   ├── contact.html         # Formulaires contact/réservation
│   └── connexion.html       # Système d'authentification
│
└── images/                  # Images du site
    ├── peperoni.avif
    ├── POULET.webp
    └── ...
```

## 🔗 API Endpoints

### Authentification
- `POST /api/auth.php/login` - Connexion
- `POST /api/auth.php/register` - Inscription
- `GET /api/auth.php/profile` - Récupérer profil
- `PUT /api/auth.php/profile` - Modifier profil
- `GET /api/auth.php/verify` - Vérifier session

### Contact & Réservations
- `POST /api/contact.php/contact` - Envoyer un message
- `POST /api/contact.php/reservation` - Faire une réservation
- `GET /api/contact.php/contacts` - Lister contacts (admin/user)
- `GET /api/contact.php/reservations` - Lister réservations (admin/user)

### Articles
- `GET /api/articles.php/articles` - Lister articles
- `GET /api/articles.php/{id}` - Récupérer un article
- `POST /api/articles.php/articles` - Créer article (admin)
- `PUT /api/articles.php/{id}` - Modifier article (admin)
- `DELETE /api/articles.php/{id}` - Supprimer article (admin)

### Administration
- `GET /api/admin.php/stats` - Statistiques générales
- `GET /api/admin.php/users` - Lister utilisateurs
- `GET /api/admin.php/dashboard` - Tableau de bord
- `POST /api/admin.php/users` - Créer utilisateur
- `PUT /api/admin.php/user` - Modifier utilisateur
- `DELETE /api/admin.php/user` - Supprimer utilisateur

## 🔐 Authentification

Le système utilise des tokens JWT stockés dans localStorage :
- **Durée de vie** : 24 heures
- **Renouvellement** : Automatique à la connexion
- **Permissions** : Role-based (client/admin)

## 📊 Intégration Discord

Les formulaires de contact et réservation envoient automatiquement des notifications Discord :
- **Webhook URL** : Configurée dans `contact.php`
- **Mentions** : Role `@1428738967053795479` notifié
- **Format** : Embeds colorés avec toutes les informations

## 🛠️ Fonctionnalités Avancées

### Panel d'Administration
- **Statistiques en temps réel** (utilisateurs, contacts, réservations)
- **Gestion des utilisateurs** (création, modification, suppression)
- **Gestion des articles** (publication, modification, archivage)
- **Tableau de bord** avec activité récente

### Gestion des Articles
- **Statuts** : Brouillon, Publié, Archivé
- **Compteur de vues** automatique
- **Gestion des images** et métadonnées
- **Interface d'édition** complète (admin)

### Système de Réservation
- **Validation des dates** (pas dans le passé)
- **Statuts** : En attente, Confirmée, Annulée, Terminée
- **Notifications Discord** automatiques
- **Gestion admin** des réservations

## 🔧 Maintenance

### Sauvegarde
```bash
# Sauvegarde de la base de données
mysqldump -h we01io.myd.infomaniak.com -P 3306 -u we01io_tfeAdmin -p we01io_pizza > backup.sql
```

### Statistiques automatiques
Une procédure stockée `UpdateDailyStats()` peut être exécutée quotidiennement via cron pour maintenir les statistiques à jour.

### Logs
Les erreurs sont enregistrées via `error_log()` PHP. Consultez les logs du serveur pour le debugging.

## 🚦 Statut de Migration

✅ **Terminé :**
- Configuration base de données
- API complète (auth, contact, articles, admin)
- Frontend avec intégration API
- Système d'authentification
- Panel d'administration
- Intégration Discord

🎯 **Prêt pour utilisation :**
Le système est entièrement fonctionnel et prêt pour la production. Tous les données sont maintenant stockées en base de données au lieu du localStorage.

---

**🍕 Pizza This - Système de gestion complet avec base de données**  
*Développé avec PHP, MySQL, JavaScript vanilla et amour pour la pizza !*

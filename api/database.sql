-- Script de création des tables pour Pizza This
-- Base de données: we01io_pizza

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(20) PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    rp_id VARCHAR(10) UNIQUE,
    discord VARCHAR(100),
    phone VARCHAR(12), -- Format XXXXX-XXXXX
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'admin') DEFAULT 'client',
    newsletter BOOLEAN DEFAULT FALSE,
    member_since YEAR DEFAULT (YEAR(CURDATE())),
    orders_count INT DEFAULT 0,
    total_spent DECIMAL(10,2) DEFAULT 0.00,
    loyalty_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Table des contacts
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20),
    nom VARCHAR(100) NOT NULL,
    discord VARCHAR(100),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('nouveau', 'en_cours', 'resolu', 'ferme') DEFAULT 'nouveau',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20),
    nom VARCHAR(100) NOT NULL,
    discord VARCHAR(100),
    people_count INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    message TEXT,
    status ENUM('en_attente', 'confirmee', 'annulee', 'terminee') DEFAULT 'en_attente',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des articles/actualités
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    excerpt TEXT NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(255),
    author VARCHAR(100) NOT NULL,
    published_date DATE NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des sessions utilisateur (optionnel, pour une gestion plus avancée)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(20) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token_hash),
    INDEX idx_expires (expires_at)
);

-- Table des statistiques du site
CREATE TABLE IF NOT EXISTS site_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    total_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_contacts INT DEFAULT 0,
    new_contacts INT DEFAULT 0,
    total_reservations INT DEFAULT 0,
    new_reservations INT DEFAULT 0,
    page_views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Insertion d'articles de test (reprenant ceux du frontend)
INSERT INTO articles (title, excerpt, content, image_url, author, published_date, status) VALUES 
('Nouvelle gamme de pizzas artisanales', 'Découvrez nos nouvelles créations inspirées des saveurs du monde...', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n\nSed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.', 'images/peperoni.avif', 'Chef Mario', '2024-03-15', 'published'),
('Ouverture de notre nouveau restaurant', 'Pizza This s\'agrandit ! Découvrez notre nouvelle adresse dans le centre-ville...', 'Nous sommes ravis d\'annoncer l\'ouverture de notre deuxième restaurant Pizza This dans le centre-ville ! Cette nouvelle adresse vous proposera la même qualité de pizzas artisanales dans un cadre moderne et convivial.\n\nNotre équipe de chefs passionnés vous accueillera du mardi au dimanche pour vous faire découvrir nos spécialités. Vous retrouverez bien sûr nos pizzas signature ainsi que de nouvelles créations exclusives à cette adresse.\n\nPour célébrer cette ouverture, nous offrons 20% de réduction sur toutes les commandes durant la première semaine !', 'images/POULET.webp', 'Direction', '2024-03-10', 'published'),
('Événement spécial : Soirée dégustation', 'Rejoignez-nous le 25 mars pour une soirée dégustation exceptionnelle...', 'Ne manquez pas notre soirée dégustation spéciale le 25 mars prochain ! Au programme :\n\n• Dégustation de nos 5 nouvelles pizzas\n• Accord mets et boissons avec notre sommelier\n• Rencontre avec nos chefs\n• Démonstration de préparation de pâte\n• Surprises et cadeaux à gagner\n\nLa soirée commence à 19h00 et les places sont limitées. Réservation obligatoire au 01 23 45 67 89 ou via notre site web. Tarif : 35€ par personne (boissons incluses).', 'images/TIRAMISU FRAISE.jpg', 'Équipe événementiel', '2024-03-08', 'published'),
('Nos engagements pour l\'environnement', 'Pizza This s\'engage pour un avenir plus vert...', 'Chez Pizza This, nous prenons nos responsabilités environnementales au sérieux. C\'est pourquoi nous avons mis en place plusieurs initiatives écologiques :\n\n**Emballages éco-responsables :**\n• Boîtes en carton 100% recyclable\n• Couverts en bois ou matières compostables\n• Sacs de livraison réutilisables\n\n**Sourcing local :**\n• Légumes de producteurs locaux dans un rayon de 50km\n• Fromages de nos partenaires régionaux\n• Farine bio certifiée\n\n**Réduction du gaspillage :**\n• Portions ajustables selon l\'appétit\n• Récupération des invendus pour associations caritatives\n• Compostage des déchets organiques', 'images/clara.png', 'Responsable RSE', '2024-03-05', 'published'),
('Nouveau service de livraison', 'Commandez vos pizzas préférées en ligne et recevez-les en 30 minutes !', 'Nous sommes fiers de lancer notre nouveau service de livraison ultra-rapide ! Désormais, vous pouvez :\n\n**Commander facilement :**\n• Site web optimisé mobile\n• Application mobile dédiée (bientôt disponible)\n• Commande par téléphone\n\n**Livraison express :**\n• 30 minutes maximum\n• Zone de livraison étendue\n• Suivi en temps réel de votre commande\n• Livreurs équipés de sacs isothermes\n\n**Paiement sécurisé :**\n• Carte bancaire en ligne\n• Espèces à la livraison\n• Tickets restaurant acceptés\n\nFrais de livraison : 2,50€ (gratuit dès 25€ d\'achat)', 'images/KEBAB.jpg', 'Service client', '2024-03-01', 'published'),
('Recette du mois : Pizza Margherita revisitée', 'Découvrez les secrets de notre pizza Margherita signature...', 'Ce mois-ci, nous partageons avec vous les secrets de notre pizza Margherita revisitée, l\'une des préférées de nos clients !\n\n**Ingrédients (pour 4 personnes) :**\n• Pâte artisanale (fermentation 48h)\n• Sauce tomate San Marzano\n• Mozzarella di Bufala AOP\n• Basilic frais du jardin\n• Huile d\'olive extra vierge\n• Fleur de sel de Guérande\n\n**Préparation :**\n1. Étaler la pâte finement\n2. Napper de sauce tomate avec parcimonie\n3. Ajouter la mozzarella en petits morceaux\n4. Enfourner 3 minutes à 450°C\n5. Ajouter basilic et huile d\'olive à la sortie\n\n**Le secret :** Notre four à bois chauffe à 450°C pour une cuisson parfaite en 90 secondes !', 'images/mcfluxy.png', 'Chef Luigi', '2024-02-28', 'published')
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- Index pour améliorer les performances
CREATE INDEX idx_users_rp_id ON users(rp_id);
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_contacts_status ON contacts(status);
CREATE INDEX idx_reservations_date ON reservations(reservation_date);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_articles_status ON articles(status);
CREATE INDEX idx_articles_published_date ON articles(published_date);

-- Mise à jour des statistiques (à exécuter quotidiennement via cron)
DELIMITER $$
CREATE PROCEDURE UpdateDailyStats()
BEGIN
    INSERT INTO site_stats (stat_date, total_users, new_users, total_contacts, new_contacts, total_reservations, new_reservations)
    VALUES (
        CURDATE(),
        (SELECT COUNT(*) FROM users),
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()),
        (SELECT COUNT(*) FROM contacts),
        (SELECT COUNT(*) FROM contacts WHERE DATE(created_at) = CURDATE()),
        (SELECT COUNT(*) FROM reservations),
        (SELECT COUNT(*) FROM reservations WHERE DATE(created_at) = CURDATE())
    )
    ON DUPLICATE KEY UPDATE
        total_users = VALUES(total_users),
        new_users = VALUES(new_users),
        total_contacts = VALUES(total_contacts),
        new_contacts = VALUES(new_contacts),
        total_reservations = VALUES(total_reservations),
        new_reservations = VALUES(new_reservations),
        updated_at = CURRENT_TIMESTAMP;
END$$
DELIMITER ;

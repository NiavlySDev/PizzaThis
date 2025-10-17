// ===== PIZZA THIS SPA APPLICATION =====

class PizzaThisApp {
    constructor() {
        this.currentPage = 'accueil';
        this.init();
    }

    async init() {
        // Charger le header et le footer
        await this.loadHeader();
        await this.loadFooter();
        
        // Charger la page d'accueil par défaut
        await this.loadPage('accueil');
        
        // Configurer les event listeners
        this.setupEventListeners();
        
        // Initialiser le script existant
        this.initializeExistingScript();
        
        // Attacher les événements spécifiques
        this.attachPageSpecificEvents();
    }

    async loadComponent(componentName, containerId) {
        try {
            const response = await fetch(`components/${componentName}.html`);
            if (!response.ok) throw new Error(`Erreur lors du chargement de ${componentName}`);
            
            const html = await response.text();
            document.getElementById(containerId).innerHTML = html;
        } catch (error) {
            console.error(`Erreur de chargement du composant ${componentName}:`, error);
        }
    }

    async loadHeader() {
        await this.loadComponent('header', 'header-container');
    }

    async loadFooter() {
        await this.loadComponent('footer', 'footer-container');
    }

    async loadPage(pageName) {
        try {
            // Afficher le loader
            this.showLoading();
            
            const response = await fetch(`pages/${pageName}.html`);
            if (!response.ok) throw new Error(`Page ${pageName} non trouvée`);
            
            const html = await response.text();
            document.getElementById('main-content').innerHTML = html;
            
            // Mettre à jour la navigation active
            this.updateActiveNavigation(pageName);
            
            // Mettre à jour l'URL sans recharger la page
            window.history.pushState({page: pageName}, '', `#${pageName}`);
            
            // Mettre à jour le titre de la page
            this.updatePageTitle(pageName);
            
            this.currentPage = pageName;
            
            // Masquer le loader
            this.hideLoading();
            
            // Scroll en haut de la page
            window.scrollTo(0, 0);
            
            // Réattacher les événements spécifiques à la page
            this.attachPageSpecificEvents();
            
        } catch (error) {
            console.error(`Erreur de chargement de la page ${pageName}:`, error);
            this.hideLoading();
        }
    }

    updatePageTitle(pageName) {
        const titles = {
            'accueil': 'Pizza This - La meilleure pizzeria en ville',
            'menu': 'Menu - Pizza This',
            'apropos': 'À propos - Pizza This',
            'events': 'Events - Pizza This',
            'objectifs': 'Objectifs - Pizza This',
            'actualites': 'Actualités - Pizza This',
            'contact': 'Contact - Pizza This'
        };
        
        document.title = titles[pageName] || 'Pizza This';
    }

    updateActiveNavigation(pageName) {
        // Attendre que le header soit chargé
        setTimeout(() => {
            // Enlever la classe active de tous les liens
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Ajouter la classe active au lien correspondant
            document.querySelectorAll(`[data-page="${pageName}"]`).forEach(link => {
                link.classList.add('active');
            });
        }, 100);
    }

    setupEventListeners() {
        // Déléguer les événements sur le document pour les liens de navigation
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-page]');
            if (target) {
                e.preventDefault();
                const pageName = target.getAttribute('data-page');
                this.loadPage(pageName);
            }
        });

        // Gérer le bouton retour du navigateur
        window.addEventListener('popstate', (e) => {
            const page = e.state?.page || this.getPageFromHash();
            this.loadPage(page);
        });

        // Gérer le menu hamburger
        document.addEventListener('click', (e) => {
            if (e.target.closest('.hamburger')) {
                this.toggleMobileMenu();
            }
        });

        // Gérer les boutons "Lire plus" des articles
        document.addEventListener('click', (e) => {
            const readMoreBtn = e.target.closest('.read-more-btn');
            if (readMoreBtn) {
                e.preventDefault();
                const articleId = readMoreBtn.getAttribute('data-article');
                this.openArticleModal(articleId);
            }
        });

        // Gérer la fermeture de la modal
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || 
                e.target.classList.contains('modal-backdrop')) {
                this.closeArticleModal();
            }
        });

        // Fermer la modal avec la touche Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeArticleModal();
            }
        });
    }

    getPageFromHash() {
        const hash = window.location.hash.substring(1);
        const validPages = ['accueil', 'menu', 'apropos', 'events', 'objectifs', 'actualites', 'contact'];
        return validPages.includes(hash) ? hash : 'accueil';
    }

    toggleMobileMenu() {
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        
        if (hamburger && navMenu) {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        }
    }

    showLoading() {
        const loader = document.getElementById('loading');
        if (loader) {
            loader.style.display = 'flex';
        }
    }

    hideLoading() {
        const loader = document.getElementById('loading');
        if (loader) {
            loader.style.display = 'none';
        }
    }

    openArticleModal(articleId) {
        const articles = this.getArticlesData();
        const article = articles[articleId];
        
        if (!article) {
            console.error(`Article ${articleId} non trouvé`);
            return;
        }

        const modal = document.getElementById('article-modal');
        if (!modal) return;

        // Remplir le contenu de la modal
        document.getElementById('modal-title').textContent = article.title;
        document.getElementById('modal-date').textContent = article.date;
        document.getElementById('modal-category').textContent = article.category;
        
        // Gérer l'image
        const modalImage = document.getElementById('modal-image');
        if (article.image) {
            modalImage.innerHTML = `<img src="${article.image}" alt="${article.title}">`;
        } else {
            modalImage.innerHTML = `<div class="news-placeholder">${article.placeholder}</div>`;
        }
        
        // Contenu de l'article
        document.getElementById('modal-content-text').innerHTML = article.content;
        
        // Afficher la modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    closeArticleModal() {
        const modal = document.getElementById('article-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    getArticlesData() {
        return {
            'pepperoni': {
                title: 'Nouvelle Pizza Pepperoni Artisanale',
                date: '15 octobre 2025',
                category: 'Menu',
                image: 'images/peperoni.avif',
                content: `
                    <p><strong>Une nouvelle création qui va révolutionner vos papilles !</strong></p>
                    <p>Après plusieurs mois de recherche et de tests, notre équipe de chefs pizzaïolos est fière de vous présenter notre nouvelle pizza pepperoni artisanale. Cette création unique combine tradition italienne et innovation culinaire.</p>
                    
                    <h3>Les ingrédients d'exception</h3>
                    <ul>
                        <li><strong>Pepperoni artisanal italien :</strong> Importé directement de Calabre, notre pepperoni est affiné pendant 30 jours selon les méthodes traditionnelles</li>
                        <li><strong>Mozzarella di Bufala :</strong> Une mozzarella de bufflonne fraîche, crémeuse à souhait</li>
                        <li><strong>Sauce tomate San Marzano :</strong> Tomates cultivées sur les pentes du Vésuve, récoltées à la main</li>
                        <li><strong>Pâte au levain naturel :</strong> 72h de fermentation lente pour une pâte digeste et savoureuse</li>
                    </ul>
                    
                    <h3>Le secret de la cuisson</h3>
                    <p>Notre four à bois traditionnel, chauffé à 450°C, permet une cuisson parfaite en seulement 90 secondes. Cette technique ancestrale garantit une pâte croustillante à l'extérieur et moelleuse à l'intérieur, while les saveurs se développent harmonieusement.</p>
                    
                    <p><strong>Disponible dès maintenant au prix de 16,90€</strong></p>
                    <p>Venez la déguster dans notre restaurant ou commandez-la en livraison. Une expérience gustative inoubliable vous attend !</p>
                `
            },
            'tiramisu': {
                title: 'Tiramisu aux Fraises',
                date: '12 octobre 2025',
                category: 'Desserts',
                image: 'images/TIRAMISU FRAISE.jpg',
                content: `
                    <p><strong>La dolce vita s'invite à votre table !</strong></p>
                    <p>Notre chef pâtissier, formé dans les meilleures institutions italiennes, vous présente sa dernière création : un tiramisu aux fraises qui réinvente le classique avec une touche de fraîcheur printanière.</p>
                    
                    <h3>Une recette revisitée</h3>
                    <p>Contrairement au tiramisu traditionnel au café, notre version aux fraises apporte une légèreté et une acidité qui équilibrent parfaitement la richesse du mascarpone. Chaque couche raconte une histoire de saveurs.</p>
                    
                    <h3>Les étapes de création</h3>
                    <ul>
                        <li><strong>Biscuits à la cuillère :</strong> Imbibés d'un sirop au jus de citron et à l'eau de fleur d'oranger</li>
                        <li><strong>Crème au mascarpone :</strong> Battue avec des œufs frais de nos poules élevées au grain</li>
                        <li><strong>Fraises de saison :</strong> Sélectionnées chez nos producteurs locaux, macérées dans le sucre vanillé</li>
                        <li><strong>Finition :</strong> Copeaux de chocolat blanc et quelques feuilles de menthe fraîche</li>
                    </ul>
                    
                    <h3>Un dessert qui fait sensation</h3>
                    <p>Depuis son lancement, ce tiramisu rencontre un succès fou ! Les retours de nos clients sont unanimes : "c'est une explosion de fraîcheur", "on redécouvre le tiramisu", "impossible de résister".</p>
                    
                    <p><strong>Prix : 7,50€ - Disponible à la part ou en format familial (6 personnes) : 35€</strong></p>
                `
            },
            'event-poulet': {
                title: 'Soirée Dégustation Pizza Poulet',
                date: '8 octobre 2025',
                category: 'Événement',
                image: 'images/POULET.webp',
                content: `
                    <p><strong>Une soirée exceptionnelle vous attend le 20 octobre !</strong></p>
                    <p>Pizza This organise une soirée dégustation dédiée à nos pizzas au poulet. L'occasion parfaite de découvrir toutes nos créations à base de poulet dans une ambiance conviviale et festive.</p>
                    
                    <h3>Au programme de la soirée</h3>
                    <ul>
                        <li><strong>18h30 - 19h30 :</strong> Accueil avec un apéritif offert (spritz ou soft au choix)</li>
                        <li><strong>19h30 - 21h :</strong> Dégustation de 5 pizzas au poulet différentes en mini format</li>
                        <li><strong>21h - 22h :</strong> Vote pour votre pizza préférée et annonce du gagnant</li>
                        <li><strong>Toute la soirée :</strong> Rencontre avec nos chefs et présentation des techniques de préparation</li>
                    </ul>
                    
                    <h3>Les pizzas à l'honneur</h3>
                    <ol>
                        <li><strong>Poulet BBQ :</strong> Sauce barbecue maison, poulet mariné, oignons rouges</li>
                        <li><strong>Poulet Curry :</strong> Sauce curry douce, poulet épicé, courgettes</li>
                        <li><strong>Poulet Ranch :</strong> Sauce ranch, poulet grillé, champignons, poivrons</li>
                        <li><strong>Poulet Pesto :</strong> Pesto de basilic, poulet à l'ail, tomates cerises</li>
                        <li><strong>Poulet Indien :</strong> Sauce tikka masala, poulet tandoori, oignons caramélisés</li>
                    </ol>
                    
                    <h3>Informations pratiques</h3>
                    <p><strong>Date :</strong> Samedi 20 octobre 2025</p>
                    <p><strong>Horaires :</strong> 18h30 - 22h</p>
                    <p><strong>Prix :</strong> 25€ par personne (apéritif + dégustation inclus)</p>
                    <p><strong>Places limitées :</strong> 40 personnes maximum</p>
                    <p><strong>Réservation obligatoire :</strong> 01 23 45 67 89 ou en restaurant</p>
                    
                    <p>Venez nombreux partager ce moment gourmand avec nous !</p>
                `
            },
            'app-mobile': {
                title: 'Application Mobile Pizza This',
                date: '5 octobre 2025',
                category: 'Service',
                placeholder: '📱',
                content: `
                    <p><strong>La révolution digitale arrive chez Pizza This !</strong></p>
                    <p>Nous sommes heureux de vous annoncer que notre application mobile sera bientôt disponible sur iOS et Android. Commander vos pizzas préférées n'aura jamais été aussi simple !</p>
                    
                    <h3>Les fonctionnalités de l'app</h3>
                    <ul>
                        <li><strong>Commande express :</strong> Retrouvez vos pizzas préférées et commandez en 3 clics</li>
                        <li><strong>Personnalisation totale :</strong> Créez votre pizza sur mesure avec tous nos ingrédients</li>
                        <li><strong>Suivi en temps réel :</strong> Suivez la préparation et la livraison de votre commande</li>
                        <li><strong>Programme de fidélité :</strong> Cumulez des points à chaque commande</li>
                        <li><strong>Promotions exclusives :</strong> Accédez à des offres réservées aux utilisateurs de l'app</li>
                        <li><strong>Géolocalisation :</strong> Trouvez le restaurant le plus proche de vous</li>
                        <li><strong>Paiement sécurisé :</strong> PayPal, carte bancaire, Apple Pay, Google Pay</li>
                    </ul>
                    
                    <h3>Développement en cours</h3>
                    <p>Notre équipe de développeurs travaille d'arrache-pied pour vous offrir la meilleure expérience possible. L'application est actuellement en phase de tests internes et nous peaufinons les derniers détails.</p>
                    
                    <h3>Bêta testeurs recherchés !</h3>
                    <p>Vous souhaitez être parmi les premiers à tester notre application ? Inscrivez-vous à notre liste de bêta testeurs en envoyant un email à <strong>beta@pizzathis.fr</strong> avec :</p>
                    <ul>
                        <li>Votre nom et prénom</li>
                        <li>Votre modèle de smartphone</li>
                        <li>Votre fréquence de commande chez Pizza This</li>
                    </ul>
                    
                    <h3>Lancement prévu</h3>
                    <p><strong>Date de sortie :</strong> Novembre 2025</p>
                    <p><strong>Plateformes :</strong> iOS (App Store) et Android (Google Play)</p>
                    <p><strong>Prix :</strong> Gratuit avec achats intégrés optionnels</p>
                    
                    <p>Restez connectés sur nos réseaux sociaux pour ne rien manquer des actualités de développement !</p>
                `
            },
            'biscotti': {
                title: 'Biscotti Faits Maison',
                date: '2 octobre 2025',
                category: 'Desserts',
                image: 'images/BISCOTTI.jpg',
                content: `
                    <p><strong>L'authenticité italienne dans chaque bouchée !</strong></p>
                    <p>Nous sommes fiers d'ajouter à notre carte les biscotti traditionnels italiens, entièrement faits maison selon une recette familiale transmise de génération en génération.</p>
                    
                    <h3>L'histoire des biscotti</h3>
                    <p>Originaires de Toscane, les biscotti (littéralement "deux fois cuits") datent de l'époque romaine. Ces biscuits étaient appréciés des légionnaires pour leur longue conservation. Aujourd'hui, ils accompagnent parfaitement le café ou le vin de dessert.</p>
                    
                    <h3>Notre recette authentique</h3>
                    <p>Nos biscotti sont préparés chaque matin selon la méthode traditionnelle :</p>
                    <ol>
                        <li><strong>Première cuisson :</strong> La pâte est façonnée en boudins et cuite une première fois</li>
                        <li><strong>Découpe :</strong> Encore chauds, les boudins sont tranchés en biais</li>
                        <li><strong>Seconde cuisson :</strong> Les tranches retournent au four pour obtenir leur croustillant parfait</li>
                    </ol>
                    
                    <h3>Nos variétés disponibles</h3>
                    <ul>
                        <li><strong>Classique aux amandes :</strong> La recette originale avec des amandes entières</li>
                        <li><strong>Chocolat-noisettes :</strong> Pépites de chocolat noir et noisettes grillées</li>
                        <li><strong>Citron-pistaches :</strong> Zestes de citron de Sicile et pistaches</li>
                        <li><strong>Figues-noix :</strong> Figues séchées et cerneaux de noix</li>
                    </ul>
                    
                    <h3>Comment les déguster</h3>
                    <p><strong>Avec le café :</strong> Trempez délicatement votre biscotti dans votre expresso pour qu'il s'imprègne des arômes</p>
                    <p><strong>Avec le vin :</strong> Accompagnez vos biscotti d'un verre de Vin Santo ou de Moscato</p>
                    <p><strong>En dessert :</strong> Parfaits pour terminer un repas sur une note sucrée et légère</p>
                    
                    <h3>Disponibilité et prix</h3>
                    <p><strong>Prix :</strong> 2,50€ les 3 biscotti</p>
                    <p><strong>Coffret cadeau :</strong> Assortiment de 12 biscotti (4 variétés) pour 9,90€</p>
                    <p><strong>Sur commande :</strong> Boîtes personnalisées pour vos événements</p>
                    
                    <p>Venez redécouvrir l'art de vivre italien avec nos biscotti authentiques !</p>
                `
            },
            'promo-etudiants': {
                title: 'Promotion Étudiants',
                date: '28 septembre 2025',
                category: 'Promotion',
                placeholder: '🎉',
                content: `
                    <p><strong>Pizza This soutient les étudiants !</strong></p>
                    <p>Parce que nous savons que les études, ça creuse (et que le budget étudiant est serré), nous lançons notre nouvelle promotion dédiée aux étudiants de la région.</p>
                    
                    <h3>L'offre en détail</h3>
                    <p><strong>-20% sur toutes nos pizzas tous les mardis !</strong></p>
                    <p>Que vous soyez seul ou entre amis, profitez de cette réduction exceptionnelle sur l'intégralité de notre carte pizzas :</p>
                    <ul>
                        <li>Pizzas classiques : de 8€ au lieu de 10€</li>
                        <li>Pizzas premium : de 12€ au lieu de 15€</li>
                        <li>Pizzas XL : de 16€ au lieu de 20€</li>
                    </ul>
                    
                    <h3>Comment en profiter ?</h3>
                    <p><strong>Conditions simples :</strong></p>
                    <ol>
                        <li>Présenter votre carte d'étudiant en cours de validité</li>
                        <li>Commande sur place ou à emporter (pas de livraison pour cette promo)</li>
                        <li>Valable tous les mardis de 11h30 à 22h30</li>
                        <li>Non cumulable avec d'autres offres</li>
                    </ol>
                    
                    <h3>Établissements partenaires</h3>
                    <p>Cette promotion est valable pour les étudiants de :</p>
                    <ul>
                        <li>Université Paris-Sorbonne</li>
                        <li>Sciences Po Paris</li>
                        <li>École Normale Supérieure</li>
                        <li>HEC Paris</li>
                        <li>ESCP Business School</li>
                        <li>École 42</li>
                        <li>Et tous les autres établissements d'enseignement supérieur</li>
                    </ul>
                    
                    <h3>Pourquoi cette initiative ?</h3>
                    <p>Chez Pizza This, nous croyons que bien manger ne doit pas être un luxe. Les étudiants sont l'avenir de notre société et méritent de pouvoir se régaler sans exploser leur budget courses.</p>
                    
                    <h3>Témoignages d'étudiants</h3>
                    <p><em>"Génial ! Maintenant je peux me faire plaisir avec mes amis tous les mardis soir après les cours."</em> - Marie, étudiante en droit</p>
                    <p><em>"La qualité est au rendez-vous et le prix devient accessible. Merci Pizza This !"</em> - Antoine, étudiant en informatique</p>
                    
                    <p><strong>Promo valable à partir du 1er octobre 2025</strong></p>
                    <p>Rendez-vous tous les mardis chez Pizza This et régalez-vous sans vous ruiner !</p>
                `
            }
        };
    }

    // Initialiser le script existant pour les fonctionnalités comme le smooth scroll
    initializeExistingScript() {
        // Smooth scrolling pour les liens internes
        document.addEventListener('click', (e) => {
            const target = e.target.closest('a[href^="#"]');
            if (target && target.getAttribute('href') !== '#') {
                const targetId = target.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });

        // Fermer le menu mobile quand on clique sur un lien
        document.addEventListener('click', (e) => {
            const target = e.target.closest('.nav-link');
            if (target && window.innerWidth <= 768) {
                this.toggleMobileMenu();
            }
        });

        // Gérer les onglets des formulaires
        document.addEventListener('click', (e) => {
            const tabButton = e.target.closest('.tab-button');
            if (tabButton) {
                this.switchTab(tabButton.getAttribute('data-tab'));
            }
        });

        // Gérer les formulaires de contact et réservation
        document.addEventListener('submit', (e) => {
            // Contact form
            if (e.target.id === 'contact-form' || e.target.classList.contains('contact-form')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Contact form submitted');
                this.handleContactForm(e.target);
                return false;
            }
            
            // Reservation form
            if (e.target.id === 'reservation-form' || e.target.classList.contains('reservation-form')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Reservation form submitted');
                this.handleReservationForm(e.target);
                return false;
            }
        });

        // Gérer le formulaire de newsletter
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('newsletter-form')) {
                e.preventDefault();
                alert('Merci pour votre abonnement à notre newsletter !');
                e.target.reset();
            }
        });
    }

    // Gestion des onglets de formulaires
    switchTab(tabName) {
        // Désactiver tous les onglets et contenus
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.form-container').forEach(container => {
            container.classList.remove('active');
        });

        // Activer l'onglet et le contenu sélectionnés
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
        document.getElementById(`${tabName}-tab`).classList.add('active');
    }

    // Gestion du formulaire de contact
    async handleContactForm(form) {
        console.log('handleContactForm called');
        
        const formData = new FormData(form);
        const data = {
            nom: formData.get('nom'),
            prenom: formData.get('prenom'),
            id: formData.get('id'),
            discord: formData.get('discord'),
            sujet: formData.get('sujet'),
            message: formData.get('message')
        };

        console.log('Form data:', data);

        // Validation basique
        if (!data.nom || !data.prenom || !data.id || !data.discord || !data.sujet || !data.message) {
            this.showFormMessage(form, 'Veuillez remplir tous les champs obligatoires.', 'error');
            return;
        }

        await this.sendToDiscord(data, 'contact');
    }

    // Gestion du formulaire de réservation
    async handleReservationForm(form) {
        console.log('handleReservationForm called');
        
        const formData = new FormData(form);
        const data = {
            nom: formData.get('nom'),
            prenom: formData.get('prenom'),
            id: formData.get('id'),
            discord: formData.get('discord'),
            personnes: formData.get('personnes'),
            jour: formData.get('jour'),
            heure: formData.get('heure'),
            message: formData.get('message') || 'Aucune information complémentaire'
        };

        console.log('Reservation data:', data);

        // Validation basique
        if (!data.nom || !data.prenom || !data.id || !data.discord || !data.personnes || !data.jour || !data.heure) {
            this.showFormMessage(form, 'Veuillez remplir tous les champs obligatoires.', 'error');
            return;
        }

        // Validation de la date (pas dans le passé)
        const selectedDate = new Date(data.jour + 'T' + data.heure);
        const now = new Date();
        if (selectedDate <= now) {
            this.showFormMessage(form, 'La date et l\'heure de réservation doivent être dans le futur.', 'error');
            return;
        }

        await this.sendToDiscord(data, 'reservation');
    }

    // Envoi vers Discord
    async sendToDiscord(data, type) {
        const webhookUrl = 'https://discord.com/api/webhooks/1428732094556672140/580DzKFp-mqNOgYahwfZ2f8oVm5abKNAnqvChASQhK1CQtQySJH917hC3qNXF2uKnjY6';
        
        let embed;
        let form;

        if (type === 'contact') {
            form = document.getElementById('contact-form');
            embed = {
                title: "💬 Nouveau message de contact",
                color: 0xD4AF37, // Couleur dorée
                fields: [
                    {
                        name: "👤 Client",
                        value: `**Nom :** ${data.nom} ${data.prenom}\n**ID :** ${data.id}\n**Discord :** ${data.discord}`,
                        inline: true
                    },
                    {
                        name: "📋 Sujet",
                        value: data.sujet,
                        inline: true
                    },
                    {
                        name: "💬 Message",
                        value: data.message,
                        inline: false
                    }
                ],
                timestamp: new Date().toISOString(),
                footer: {
                    text: "Pizza This - Système de Contact"
                },
                thumbnail: {
                    url: "https://em-content.zobj.net/thumbs/120/apple/354/pizza_1f355.png"
                },
                content: "<@&1428738967053795479>" 
            };
        } else if (type === 'reservation') {
            form = document.getElementById('reservation-form');
            const dateFormatted = new Date(data.jour).toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            embed = {
                title: "📅 Nouvelle demande de réservation",
                color: 0x28A745, // Couleur verte
                fields: [
                    {
                        name: "👤 Client",
                        value: `**Nom :** ${data.nom} ${data.prenom}\n**ID :** ${data.id}\n**Discord :** ${data.discord}`,
                        inline: true
                    },
                    {
                        name: "📊 Détails de la réservation",
                        value: `**Personnes :** ${data.personnes}\n**Date :** ${dateFormatted}\n**Heure :** ${data.heure}`,
                        inline: true
                    },
                    {
                        name: "💬 Informations complémentaires",
                        value: data.message,
                        inline: false
                    }
                ],
                timestamp: new Date().toISOString(),
                footer: {
                    text: "Pizza This - Système de Réservation"
                },
                thumbnail: {
                    url: "https://em-content.zobj.net/thumbs/120/apple/354/calendar_1f4c5.png"
                },
                content: "<@&1428738967053795479>" 
            };
        }

        // Afficher le loading
        this.toggleFormLoading(form, true);

        try {
            const response = await fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    embeds: [embed]
                })
            });

            if (response.ok) {
                const successMessage = type === 'contact' 
                    ? 'Message envoyé avec succès ! Nous vous répondrons bientôt.' 
                    : 'Demande de réservation envoyée ! Nous vous confirmerons votre réservation rapidement.';
                
                this.showFormMessage(form, successMessage, 'success');
                form.reset();
            } else {
                throw new Error('Erreur lors de l\'envoi');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showFormMessage(form, 'Erreur lors de l\'envoi. Veuillez réessayer ou nous contacter directement.', 'error');
        } finally {
            this.toggleFormLoading(form, false);
        }
    }

    // Afficher un message de succès/erreur
    showFormMessage(form, message, type) {
        // Supprimer les anciens messages
        const existingMessage = form.querySelector('.form-message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Créer le nouveau message
        const messageDiv = document.createElement('div');
        messageDiv.className = `form-message ${type}`;
        messageDiv.textContent = message;

        // Ajouter le message après le bouton
        form.appendChild(messageDiv);

        // Supprimer le message après 5 secondes
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }

    // Gérer l'état de chargement du formulaire
    toggleFormLoading(form, isLoading) {
        const button = form.querySelector('button[type="submit"]');
        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');

        if (isLoading) {
            button.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';
        } else {
            button.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    }

    // Attacher les événements spécifiques aux pages après chargement
    attachPageSpecificEvents() {
        // Attacher les événements des formulaires de contact
        const contactForm = document.getElementById('contact-form');
        const reservationForm = document.getElementById('reservation-form');
        
        if (contactForm) {
            // Supprimer les anciens event listeners s'ils existent
            contactForm.onsubmit = (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Contact form direct submit');
                this.handleContactForm(contactForm);
                return false;
            };
        }
        
        if (reservationForm) {
            reservationForm.onsubmit = (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('Reservation form direct submit');
                this.handleReservationForm(reservationForm);
                return false;
            };
        }
        
        // Attacher les événements des onglets
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.onclick = (e) => {
                e.preventDefault();
                const tabName = button.getAttribute('data-tab');
                this.switchTab(tabName);
            };
        });
    }
}

// Initialiser l'application quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    window.pizzaApp = new PizzaThisApp();
});

// Gérer le chargement initial depuis l'URL
window.addEventListener('load', () => {
    const page = window.location.hash.substring(1) || 'accueil';
    if (window.pizzaApp) {
        window.pizzaApp.loadPage(page);
    }
});

// Fonctions globales pour les formulaires (solution de secours)
window.submitContactForm = function(event) {
    event.preventDefault();
    event.stopPropagation();
    if (window.pizzaApp) {
        const form = document.getElementById('contact-form');
        window.pizzaApp.handleContactForm(form);
    }
    return false;
};

window.submitReservationForm = function(event) {
    event.preventDefault();
    event.stopPropagation();
    if (window.pizzaApp) {
        const form = document.getElementById('reservation-form');
        window.pizzaApp.handleReservationForm(form);
    }
    return false;
};

// ===== PIZZA THIS SPA APPLICATION WITH API INTEGRATION =====

class PizzaThisApp {
    constructor() {
        this.currentPage = 'accueil';
        this.apiBase = 'api';
        this.user = null;
        this.token = localStorage.getItem('pizzathis_token');
        this.init();
    }

    async init() {
        // Vérifier la session existante
        if (this.token) {
            await this.verifySession();
        }
        
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

    // ===== GESTION DES API =====
    
    async apiCall(endpoint, method = 'GET', data = null) {
        const url = `${this.apiBase}/${endpoint}`;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (this.token) {
            options.headers['Authorization'] = `Bearer ${this.token}`;
        }

        if (data) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || `Erreur HTTP ${response.status}`);
            }

            return result;
        } catch (error) {
            console.error('Erreur API:', error);
            throw error;
        }
    }

    async verifySession() {
        try {
            const response = await this.apiCall('auth.php/verify');
            if (response.valid) {
                this.user = response.user;
                this.updateAuthUI();
            } else {
                this.logout();
            }
        } catch (error) {
            console.error('Erreur vérification session:', error);
            this.logout();
        }
    }

    saveToken(token) {
        this.token = token;
        localStorage.setItem('pizzathis_token', token);
    }

    clearToken() {
        this.token = null;
        this.user = null;
        localStorage.removeItem('pizzathis_token');
    }

    // ===== GESTION DES COMPOSANTS ET PAGES =====

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
        this.updateAuthUI();
    }

    async loadFooter() {
        await this.loadComponent('footer', 'footer-container');
    }

    async loadPage(pageName) {
        try {
            this.showLoading();
            
            let response;
            if (pageName === 'actualites') {
                // Pour les actualités, charger les données depuis l'API
                const articlesResponse = await this.apiCall('articles.php/articles');
                response = await fetch(`pages/${pageName}.html`);
                
                if (response.ok) {
                    let html = await response.text();
                    
                    // Remplacer les articles statiques par les données de l'API
                    if (articlesResponse.success && articlesResponse.articles) {
                        html = this.generateArticlesHTML(articlesResponse.articles);
                    }
                    
                    document.getElementById('main-content').innerHTML = html;
                }
            } else {
                response = await fetch(`pages/${pageName}.html`);
                
                if (response.ok) {
                    const html = await response.text();
                    document.getElementById('main-content').innerHTML = html;
                }
            }
            
            this.currentPage = pageName;
            this.attachPageSpecificEvents();
            this.hideLoading();
            
        } catch (error) {
            console.error(`Erreur de chargement de la page ${pageName}:`, error);
            this.hideLoading();
        }
    }

    generateArticlesHTML(articles) {
        const articlesHtml = articles.map((article, index) => `
            <article class="news-card" data-article="${index}">
                <img src="${article.image_url || 'images/default.jpg'}" alt="${article.title}">
                <div class="news-content">
                    <h3>${article.title}</h3>
                    <p class="news-excerpt">${article.excerpt}</p>
                    <div class="news-meta">
                        <span class="author">Par ${article.author}</span>
                        <span class="date">${new Date(article.published_date).toLocaleDateString('fr-FR')}</span>
                        <span class="views">${article.views_count || 0} vues</span>
                    </div>
                    <button class="read-more">Lire plus</button>
                </div>
            </article>
        `).join('');

        const modalContent = articles.map((article, index) => `
            <div class="modal-article" data-article-id="${index}">
                <h2>${article.title}</h2>
                <div class="article-meta">
                    <span>Par ${article.author}</span>
                    <span>${new Date(article.published_date).toLocaleDateString('fr-FR')}</span>
                    <span>${article.views_count || 0} vues</span>
                </div>
                ${article.image_url ? `<img src="${article.image_url}" alt="${article.title}" class="article-image">` : ''}
                <div class="article-content">${article.content.replace(/\n/g, '<br>')}</div>
            </div>
        `).join('');

        return `
            <section class="news">
                <div class="container">
                    <h2 class="section-title">Actualités</h2>
                    <p class="section-description">Restez informé de toutes nos nouveautés, événements et offres spéciales</p>
                    
                    <div class="news-grid">
                        ${articlesHtml}
                    </div>
                </div>
            </section>

            <!-- Modal pour les articles -->
            <div id="article-modal" class="modal">
                <div class="modal-content">
                    <span class="modal-close">&times;</span>
                    <div class="modal-body">
                        ${modalContent}
                    </div>
                </div>
            </div>
        `;
    }

    updateAuthUI() {
        const authLink = document.getElementById('auth-link');
        if (authLink) {
            if (this.user) {
                authLink.innerHTML = `
                    <span class="user-info">
                        ${this.user.prenom} ${this.user.nom}
                        ${this.user.role === 'admin' ? '<span class="admin-badge">Admin</span>' : ''}
                    </span>
                `;
                authLink.onclick = () => this.loadPage('connexion');
            } else {
                authLink.innerHTML = '<i class="fas fa-user"></i> Connexion';
                authLink.onclick = () => this.loadPage('connexion');
            }
        }
    }

    // ===== GESTION DES ÉVÉNEMENTS =====

    setupEventListeners() {
        // Navigation
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-page]')) {
                e.preventDefault();
                const page = e.target.getAttribute('data-page');
                this.loadPage(page);
            }
        });
    }

    attachPageSpecificEvents() {
        // Événements pour les actualités
        if (this.currentPage === 'actualites') {
            this.attachNewsEvents();
        }
        
        // Événements pour les formulaires
        this.attachFormEvents();
        
        // Événements d'authentification
        if (this.currentPage === 'connexion') {
            this.attachAuthEvents();
        }
    }

    attachNewsEvents() {
        const readMoreButtons = document.querySelectorAll('.read-more');
        const modal = document.getElementById('article-modal');
        const closeModal = document.querySelector('.modal-close');

        if (readMoreButtons.length > 0) {
            readMoreButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const articleCard = e.target.closest('.news-card');
                    const articleIndex = articleCard.getAttribute('data-article');
                    this.showArticleModal(articleIndex);
                });
            });
        }

        if (closeModal) {
            closeModal.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    }

    showArticleModal(articleIndex) {
        const modal = document.getElementById('article-modal');
        const articles = document.querySelectorAll('.modal-article');
        
        // Cacher tous les articles
        articles.forEach(article => article.style.display = 'none');
        
        // Afficher l'article sélectionné
        const selectedArticle = document.querySelector(`[data-article-id="${articleIndex}"]`);
        if (selectedArticle) {
            selectedArticle.style.display = 'block';
            modal.style.display = 'block';
        }
    }

    // ===== GESTION DES FORMULAIRES =====

    attachFormEvents() {
        // Formulaire de contact
        const contactForm = document.getElementById('contact-form');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleContactForm(contactForm);
            });
            
            contactForm.onsubmit = (e) => {
                e.preventDefault();
                this.handleContactForm(contactForm);
                return false;
            };
        }

        // Formulaire de réservation
        const reservationForm = document.getElementById('reservation-form');
        if (reservationForm) {
            reservationForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleReservationForm(reservationForm);
            });
            
            reservationForm.onsubmit = (e) => {
                e.preventDefault();
                this.handleReservationForm(reservationForm);
                return false;
            };
        }
    }

    async handleContactForm(form) {
        console.log('handleContactForm called');
        
        const formData = new FormData(form);
        const data = {
            nom: formData.get('nom'),
            discord: formData.get('discord'),
            subject: formData.get('subject'),
            message: formData.get('message')
        };

        // Validation basique
        if (!data.nom || !data.discord || !data.subject || !data.message) {
            this.showFormMessage(form, 'Veuillez remplir tous les champs.', 'error');
            return;
        }

        this.toggleFormLoading(form, true);

        try {
            const response = await this.apiCall('contact.php/contact', 'POST', data);
            
            if (response.success) {
                this.showFormMessage(form, response.message, 'success');
                form.reset();
            }
        } catch (error) {
            this.showFormMessage(form, error.message || 'Erreur lors de l\'envoi du message.', 'error');
        }

        this.toggleFormLoading(form, false);
    }

    async handleReservationForm(form) {
        console.log('handleReservationForm called');
        
        const formData = new FormData(form);
        const data = {
            nom: formData.get('nom'),
            discord: formData.get('discord'),
            people: formData.get('people'),
            date: formData.get('date'),
            time: formData.get('time'),
            message: formData.get('message') || ''
        };

        // Validation basique
        if (!data.nom || !data.discord || !data.people || !data.date || !data.time) {
            this.showFormMessage(form, 'Veuillez remplir tous les champs obligatoires.', 'error');
            return;
        }

        // Validation de la date
        const selectedDate = new Date(data.date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            this.showFormMessage(form, 'La date de réservation ne peut pas être dans le passé.', 'error');
            return;
        }

        this.toggleFormLoading(form, true);

        try {
            const response = await this.apiCall('contact.php/reservation', 'POST', data);
            
            if (response.success) {
                this.showFormMessage(form, response.message, 'success');
                form.reset();
            }
        } catch (error) {
            this.showFormMessage(form, error.message || 'Erreur lors de l\'enregistrement de la réservation.', 'error');
        }

        this.toggleFormLoading(form, false);
    }

    // ===== GESTION DE L'AUTHENTIFICATION =====

    attachAuthEvents() {
        // Gestion des onglets
        const tabButtons = document.querySelectorAll('.auth-tab-button');
        if (tabButtons) {
            tabButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    const tabName = e.target.getAttribute('data-auth-tab');
                    this.switchAuthTab(tabName);
                });
            });
        }

        // Formulaires
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const profileForm = document.getElementById('profile-form');

        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLoginForm(loginForm);
            });
            
            loginForm.onsubmit = (e) => {
                e.preventDefault();
                this.handleLoginForm(loginForm);
                return false;
            };
        }

        if (registerForm) {
            registerForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleRegisterForm(registerForm);
            });
            
            registerForm.onsubmit = (e) => {
                e.preventDefault();
                this.handleRegisterForm(registerForm);
                return false;
            };
        }

        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProfileForm(profileForm);
            });
            
            profileForm.onsubmit = (e) => {
                e.preventDefault();
                this.handleProfileForm(profileForm);
                return false;
            };
        }

        // Afficher l'onglet approprié
        if (this.user) {
            this.switchAuthTab('profile');
            this.populateProfile(this.user);
            
            // Afficher le panel admin si nécessaire
            if (this.user.role === 'admin') {
                const adminPanel = document.getElementById('admin-panel');
                if (adminPanel) {
                    adminPanel.style.display = 'block';
                    this.loadAdminData();
                }
            }
        } else {
            this.switchAuthTab('login');
        }
    }

    async handleLoginForm(form) {
        console.log('handleLoginForm called');
        
        const formData = new FormData(form);
        const data = {
            identifier: formData.get('identifier'),
            password: formData.get('password'),
            remember: formData.get('remember') === 'on'
        };

        // Validation basique
        if (!data.identifier || !data.password) {
            this.showFormMessage(form, 'Veuillez remplir tous les champs.', 'error');
            return;
        }

        this.toggleFormLoading(form, true);

        try {
            const response = await this.apiCall('auth.php/login', 'POST', data);
            
            if (response.success) {
                this.user = response.user;
                this.saveToken(response.user.token);
                this.showFormMessage(form, response.message, 'success');
                this.updateAuthUI();
                
                setTimeout(() => {
                    this.switchAuthTab('profile');
                    this.populateProfile(this.user);
                }, 1000);
            }
        } catch (error) {
            this.showFormMessage(form, error.message || 'Erreur lors de la connexion.', 'error');
        }

        this.toggleFormLoading(form, false);
    }

    async handleRegisterForm(form) {
        console.log('handleRegisterForm called');
        
        const formData = new FormData(form);
        const data = {
            nom: formData.get('nom'),
            prenom: formData.get('prenom'),
            email: formData.get('email'),
            discord: formData.get('discord'),
            phone: formData.get('phone'),
            address: formData.get('address'),
            password: formData.get('password'),
            confirm_password: formData.get('confirm_password'),
            newsletter: formData.get('newsletter') === 'on'
        };

        // Validation
        if (!data.nom || !data.prenom || !data.email || !data.discord || !data.password) {
            this.showFormMessage(form, 'Veuillez remplir tous les champs obligatoires.', 'error');
            return;
        }

        if (data.password !== data.confirm_password) {
            this.showFormMessage(form, 'Les mots de passe ne correspondent pas.', 'error');
            return;
        }

        if (data.password.length < 6) {
            this.showFormMessage(form, 'Le mot de passe doit contenir au moins 6 caractères.', 'error');
            return;
        }

        this.toggleFormLoading(form, true);

        try {
            const response = await this.apiCall('auth.php/register', 'POST', data);
            
            if (response.success) {
                this.user = response.user;
                this.saveToken(response.user.token);
                this.showFormMessage(form, response.message, 'success');
                this.updateAuthUI();
                
                setTimeout(() => {
                    this.switchAuthTab('profile');
                    this.populateProfile(this.user);
                }, 1500);
            }
        } catch (error) {
            this.showFormMessage(form, error.message || 'Erreur lors de la création du compte.', 'error');
        }

        this.toggleFormLoading(form, false);
    }

    async handleProfileForm(form) {
        console.log('handleProfileForm called');
        
        const formData = new FormData(form);
        const data = {
            nom: formData.get('nom'),
            prenom: formData.get('prenom'),
            email: formData.get('email'),
            discord: formData.get('discord'),
            phone: formData.get('phone'),
            address: formData.get('address'),
            current_password: formData.get('current_password'),
            new_password: formData.get('new_password'),
            confirm_new_password: formData.get('confirm_new_password'),
            newsletter: formData.get('newsletter') === 'on'
        };

        // Validation
        if (!data.current_password) {
            this.showFormMessage(form, 'Veuillez saisir votre mot de passe actuel pour confirmer les modifications.', 'error');
            return;
        }

        if (data.new_password && data.new_password !== data.confirm_new_password) {
            this.showFormMessage(form, 'Les nouveaux mots de passe ne correspondent pas.', 'error');
            return;
        }

        this.toggleFormLoading(form, true);

        try {
            const response = await this.apiCall('auth.php/profile', 'PUT', data);
            
            if (response.success) {
                this.user = response.user;
                this.showFormMessage(form, response.message, 'success');
                this.populateProfile(this.user);
            }
        } catch (error) {
            this.showFormMessage(form, error.message || 'Erreur lors de la mise à jour du profil.', 'error');
        }

        this.toggleFormLoading(form, false);
    }

    switchAuthTab(tabName) {
        // Gérer les onglets
        const tabs = document.querySelectorAll('.auth-tab-button');
        const contents = document.querySelectorAll('.auth-container');

        tabs.forEach(tab => {
            tab.classList.remove('active');
            if (tab.getAttribute('data-auth-tab') === tabName) {
                tab.classList.add('active');
            }
        });

        contents.forEach(content => {
            content.classList.remove('active');
            if (content.id === `${tabName}-tab`) {
                content.classList.add('active');
            }
        });
    }

    populateProfile(user) {
        // Remplir les champs du profil
        const fields = ['nom', 'prenom', 'email', 'discord', 'phone', 'address'];
        fields.forEach(field => {
            const input = document.querySelector(`#profile-form [name="${field}"]`);
            if (input && user[field]) {
                input.value = user[field];
            }
        });

        const newsletterCheckbox = document.querySelector('#profile-form [name="newsletter"]');
        if (newsletterCheckbox) {
            newsletterCheckbox.checked = user.newsletter;
        }

        // Afficher les statistiques utilisateur
        const statsElements = {
            'user-id': user.id,
            'member-since': user.member_since,
            'orders-count': user.orders_count || 0,
            'total-spent': (user.total_spent || 0) + '€',
            'loyalty-points': user.loyalty_points || 0
        };

        Object.entries(statsElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }

    async loadAdminData() {
        try {
            const response = await this.apiCall('admin.php/stats');
            if (response.success) {
                this.populateAdminStats(response.stats);
            }
        } catch (error) {
            console.error('Erreur chargement données admin:', error);
        }
    }

    populateAdminStats(stats) {
        // Remplir les statistiques admin
        const statElements = {
            'admin-total-users': stats.total_users,
            'admin-new-users': stats.new_users_week,
            'admin-total-contacts': stats.total_contacts,
            'admin-new-contacts': stats.new_contacts_today,
            'admin-total-reservations': stats.total_reservations,
            'admin-pending-reservations': stats.pending_reservations,
            'admin-total-articles': stats.total_articles,
            'admin-published-articles': stats.published_articles
        };

        Object.entries(statElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }

    logout() {
        this.clearToken();
        this.updateAuthUI();
        this.loadPage('accueil');
        this.showFormMessage(document.body, 'Vous avez été déconnecté avec succès.', 'success');
    }

    // ===== FONCTIONS UTILITAIRES =====

    showLoading() {
        const content = document.getElementById('main-content');
        if (content) {
            content.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';
        }
    }

    hideLoading() {
        // Le contenu est déjà remplacé par loadPage
    }

    showFormMessage(form, message, type) {
        let messageContainer = form.querySelector('.form-message');
        
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'form-message';
            form.insertBefore(messageContainer, form.firstChild);
        }

        messageContainer.textContent = message;
        messageContainer.className = `form-message ${type}`;
        
        setTimeout(() => {
            if (messageContainer.parentNode) {
                messageContainer.remove();
            }
        }, 5000);
    }

    toggleFormLoading(form, loading) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            if (loading) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
            } else {
                submitButton.disabled = false;
                submitButton.innerHTML = submitButton.getAttribute('data-original-text') || 'Envoyer';
            }
        }
    }

    initializeExistingScript() {
        // Code du script original (animations, etc.)
        // Peut être conservé tel quel
    }
}

// Initialiser l'application
document.addEventListener('DOMContentLoaded', () => {
    window.pizzaApp = new PizzaThisApp();
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

// Fonctions d'authentification globales
window.submitLoginForm = function(event) {
    event.preventDefault();
    event.stopPropagation();
    if (window.pizzaApp) {
        const form = document.getElementById('login-form');
        window.pizzaApp.handleLoginForm(form);
    }
    return false;
};

window.submitRegisterForm = function(event) {
    event.preventDefault();
    event.stopPropagation();
    if (window.pizzaApp) {
        const form = document.getElementById('register-form');
        window.pizzaApp.handleRegisterForm(form);
    }
    return false;
};

window.submitProfileForm = function(event) {
    event.preventDefault();
    event.stopPropagation();
    if (window.pizzaApp) {
        const form = document.getElementById('profile-form');
        window.pizzaApp.handleProfileForm(form);
    }
    return false;
};

window.switchAuthTab = function(tabName) {
    if (window.pizzaApp) {
        window.pizzaApp.switchAuthTab(tabName);
    }
};

window.logout = function() {
    if (window.pizzaApp) {
        window.pizzaApp.logout();
    }
};

window.cancelProfileEdit = function() {
    if (window.pizzaApp) {
        const user = window.pizzaApp.user;
        if (user) {
            window.pizzaApp.populateProfile(user);
            window.pizzaApp.showFormMessage(
                document.getElementById('profile-form'), 
                'Modifications annulées.', 
                'success'
            );
        }
    }
};

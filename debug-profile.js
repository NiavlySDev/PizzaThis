// Test: Ajouter ce code temporaire dans la console du navigateur pour déboguer

// Vérifier les données utilisateur stockées
console.log('Utilisateur actuel:', window.pizzaApp?.user);

// Vérifier le token stocké
console.log('Token stocké:', localStorage.getItem('token'));

// Forcer une mise à jour du profil si un utilisateur est connecté
if (window.pizzaApp?.user) {
    console.log('Mise à jour forcée du profil...');
    window.pizzaApp.populateProfile(window.pizzaApp.user);
    
    // Vérifier les éléments du DOM
    console.log('profile-name element:', document.getElementById('profile-name'));
    console.log('profile-id element:', document.getElementById('profile-id'));
}

#!/bin/bash

# Script d'installation des dépendances pour le système de release
# Usage: ./setup-release.sh

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
echo_success() { echo -e "${GREEN}✅ $1${NC}"; }
echo_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
echo_error() { echo -e "${RED}❌ $1${NC}"; }

echo_info "🚀 Installation des dépendances pour le système de release..."

# Vérifier si jq est installé (optionnel - le script fonctionne sans)
if ! command -v jq &> /dev/null; then
    echo_warning "jq n'est pas installé (optionnel)"
    echo_info "Le script fonctionne sans jq, mais vous pouvez l'installer si souhaité:"
    echo_info "sudo apt update && sudo apt install -y jq"
else
    echo_success "jq est installé"
fi

# Vérifier si GitHub CLI est installé
if ! command -v gh &> /dev/null; then
    echo_warning "GitHub CLI n'est pas installé. Installation..."
    
    # Méthode d'installation pour Ubuntu/Debian
    type -p curl >/dev/null || sudo apt install curl -y
    curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg \
    && sudo chmod go+r /usr/share/keyrings/githubcli-archive-keyring.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
    && sudo apt update \
    && sudo apt install gh -y
    
    echo_success "GitHub CLI installé"
else
    echo_success "GitHub CLI est déjà installé"
fi

# Vérifier l'authentification GitHub
echo_info "Vérification de l'authentification GitHub..."
if ! gh auth status &> /dev/null; then
    echo_warning "Vous devez vous authentifier avec GitHub CLI"
    echo_info "Lancement de l'authentification..."
    gh auth login
    echo_success "Authentification GitHub configurée"
else
    echo_success "Authentification GitHub OK"
fi

# Configurer git si nécessaire
echo_info "Vérification de la configuration git..."
if [ -z "$(git config --global user.name)" ]; then
    echo_warning "Nom d'utilisateur git non configuré"
    read -p "Entrez votre nom pour git: " git_name
    git config --global user.name "$git_name"
    echo_success "Nom d'utilisateur git configuré: $git_name"
else
    echo_success "Nom d'utilisateur git: $(git config --global user.name)"
fi

if [ -z "$(git config --global user.email)" ]; then
    echo_warning "Email git non configuré"
    read -p "Entrez votre email pour git: " git_email
    git config --global user.email "$git_email"
    echo_success "Email git configuré: $git_email"
else
    echo_success "Email git: $(git config --global user.email)"
fi

# Rendre le script de release exécutable
chmod +x release.sh

echo
echo_success "🎉 Configuration terminée!"
echo
echo_info "📋 Utilisation du script de release:"
echo "  • Interface interactive:                ./release.sh"
echo "  • Le script vous demandera le type de release et le message"
echo "  • Types disponibles:"
echo "    - Patch (v0.1.9 → v0.1.10): Corrections et petites améliorations"
echo "    - Minor (v0.1.9 → v0.1.10): Nouvelles fonctionnalités (même comportement que patch)"
echo "    - Major (v0.1.2 → v0.2.0):  Versions importantes"
echo
echo_warning "💡 Le script utilise une interface interactive qui vous guide:"
echo "  • Menu de sélection du type de release (1-4)"
echo "  • Saisie du message de release (optionnel)"
echo "  • Confirmation avant exécution"
echo "  • Versioning: patch/minor: +0.0.1, major: +0.1.0"
echo

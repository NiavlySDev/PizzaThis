#!/bin/bash

# Script de release automatique pour Pizza This
# Usage: ./release.sh (interface interactive)

set -e  # Arrêter le script en cas d'erreur

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction d'affichage coloré
echo_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
echo_success() { echo -e "${GREEN}✅ $1${NC}"; }
echo_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
echo_error() { echo -e "${RED}❌ $1${NC}"; }

# Configuration
REPO_OWNER="NiavlySDev"
REPO_NAME="PizzaThis"
VERSION_FILE="version.json"
CHANGELOG_FILE="CHANGELOG.md"

# Fonction pour incrémenter la version
increment_version() {
    local version=$1
    local type=$2
    
    # Séparer les parties de la version
    IFS='.' read -ra VERSION_PARTS <<< "$version"
    major=${VERSION_PARTS[0]}
    minor=${VERSION_PARTS[1]}
    patch=${VERSION_PARTS[2]}
    
    case $type in
        "major")
            # major: v0.1.2 → v0.2.0 (incrémente minor, reset patch)
            minor=$((minor + 1))
            patch=0
            ;;
        "minor")
            # minor: v0.1.9 → v0.1.10 (incrémente patch)
            patch=$((patch + 1))
            ;;
        "patch"|*)
            # patch: v0.1.9 → v0.1.10 (incrémente patch - même comportement que minor)
            patch=$((patch + 1))
            ;;
    esac
    
    echo "${major}.${minor}.${patch}"
}

# Vérifier si on est dans un repo git
if [ ! -d ".git" ]; then
    echo_error "Ce script doit être exécuté dans un dépôt git"
    exit 1
fi

# Vérifier si GitHub CLI est installé
if ! command -v gh &> /dev/null; then
    echo_error "GitHub CLI (gh) n'est pas installé. Installez-le avec: sudo apt install gh"
    echo_info "Ou visitez: https://cli.github.com/"
    exit 1
fi

# Vérifier l'authentification GitHub
if ! gh auth status &> /dev/null; then
    echo_error "Vous devez vous authentifier avec GitHub CLI"
    echo_info "Exécutez: gh auth login"
    exit 1
fi

# Créer le fichier de version s'il n'existe pas
if [ ! -f "$VERSION_FILE" ]; then
    echo_info "Création du fichier de version initial..."
    cat > "$VERSION_FILE" << EOF
{
  "version": "0.1.7",
  "build": "$(date +%Y%m%d%H%M%S)",
  "date": "$(date -Iseconds)"
}
EOF
    echo_success "Fichier de version créé: v0.1.5"
fi

# Lire la version actuelle (sans dépendance jq)
current_version=$(grep '"version"' "$VERSION_FILE" | sed 's/.*"version":[[:space:]]*"\([^"]*\)".*/\1/')
echo_info "Version actuelle: v$current_version"

# Interface utilisateur interactive
clear
echo "🍕 Pizza This - Système de Release Automatique"
echo "=============================================="
echo
echo "Version actuelle: v$current_version"
echo

# Menu de sélection du type de release
echo "📋 Choisissez le type de release:"
echo "  1) Patch    - Corrections et petites améliorations  (v$current_version → v$(increment_version "$current_version" "patch"))"
echo "  2) Minor    - Nouvelles fonctionnalités            (v$current_version → v$(increment_version "$current_version" "minor"))"
echo "  3) Major    - Version importante                   (v$current_version → v$(increment_version "$current_version" "major"))"
echo "  4) Annuler"
echo

# Lecture du choix utilisateur
while true; do
    read -p "Votre choix (1-4): " choice
    case $choice in
        1)
            increment_type="patch"
            type_description="Patch - Corrections et petites améliorations"
            break
            ;;
        2)
            increment_type="minor"
            type_description="Minor - Nouvelles fonctionnalités"
            break
            ;;
        3)
            increment_type="major"
            type_description="Major - Version importante"
            break
            ;;
        4)
            echo_warning "Release annulée par l'utilisateur"
            exit 0
            ;;
        *)
            echo_error "Choix invalide. Veuillez choisir 1, 2, 3 ou 4."
            ;;
    esac
done

# Calculer la nouvelle version pour l'affichage
new_version_preview=$(increment_version "$current_version" "$increment_type")
echo
echo_success "Type sélectionné: $type_description"
echo_info "Version qui sera créée: v$new_version_preview"
echo

# Demander le message de release
echo "📝 Message de release:"
echo "  Décrivez brièvement les changements de cette version"
echo "  (Laissez vide pour un message automatique)"
echo
read -p "Message: " release_message

# Message par défaut si vide
if [ -z "$release_message" ]; then
    case $increment_type in
        "patch")
            release_message="Corrections de bugs et optimisations diverses"
            ;;
        "minor")
            release_message="Nouvelles fonctionnalités et améliorations"
            ;;
        "major")
            release_message="Version importante avec nouvelles fonctionnalités majeures"
            ;;
    esac
fi

echo
echo_info "Message de release: $release_message"
echo

# Confirmation finale
echo "🔍 Récapitulatif de la release:"
echo "  • Version actuelle: v$current_version"
echo "  • Nouvelle version: v$new_version_preview"
echo "  • Type: $type_description"
echo "  • Message: $release_message"
echo

read -p "Confirmer la création de cette release? (y/N): " confirm
if [[ ! $confirm =~ ^[Yy]$ ]]; then
    echo_warning "Release annulée par l'utilisateur"
    exit 0
fi

echo
echo_success "Démarrage de la création de la release v$new_version_preview..."
echo

# Calculer la nouvelle version finale
new_version=$(increment_version "$current_version" "$increment_type")

# Vérifier s'il y a des changements à commiter
if [ -n "$(git status --porcelain)" ]; then
    echo_warning "Il y a des changements non committes. Ajout automatique..."
    git add .
    git commit -m "Préparation release v$new_version"
    echo_success "Changements committes"
fi

# Mettre à jour le fichier de version
echo_info "Mise à jour du fichier de version..."
cat > "$VERSION_FILE" << EOF
{
  "version": "$new_version",
  "build": "$(date +%Y%m%d%H%M%S)",
  "date": "$(date -Iseconds)",
  "previous_version": "$current_version"
}
EOF

# Mettre à jour ou créer le CHANGELOG
echo_info "Mise à jour du changelog..."
if [ ! -f "$CHANGELOG_FILE" ]; then
    cat > "$CHANGELOG_FILE" << EOF
# Changelog - Pizza This

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

## [v$new_version] - $(date +%Y-%m-%d)

### 🎉 Nouvelles fonctionnalités
- $release_message

### 🔧 Améliorations
- Interface utilisateur moderne et responsive
- Système d'authentification avec base de données
- Integration Discord pour notifications
- Panel d'administration complet

### 🐛 Corrections
- Optimisations diverses et corrections de bugs

---

## [v$current_version] - Version précédente
- Version précédente du système
EOF
else
    # Ajouter la nouvelle version en haut du changelog existant
    temp_file=$(mktemp)
    cat > "$temp_file" << EOF
# Changelog - Pizza This

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

## [v$new_version] - $(date +%Y-%m-%d)

### 🎉 Nouvelles fonctionnalités
- $release_message

### 🔧 Améliorations
- Interface utilisateur moderne et responsive
- Système d'authentification avec base de données
- Integration Discord pour notifications
- Panel d'administration complet

### 🐛 Corrections
- Optimisations diverses et corrections de bugs

---

EOF
    tail -n +4 "$CHANGELOG_FILE" >> "$temp_file"
    mv "$temp_file" "$CHANGELOG_FILE"
fi

# Mettre à jour le README avec la nouvelle version
echo_info "Mise à jour du README..."
if [ -f "README.md" ]; then
    sed -i "s/Version: v[0-9]\+\.[0-9]\+\.[0-9]\+/Version: v$new_version/g" README.md
    sed -i "s/version [0-9]\+\.[0-9]\+\.[0-9]\+/version $new_version/g" README.md
fi

# Ajouter les fichiers modifiés
git add "$VERSION_FILE" "$CHANGELOG_FILE" "README.md"

# Créer le commit de release
echo_info "Création du commit de release..."
git commit -m "🚀 Release v$new_version

- $release_message
- Mise à jour automatique de la version
- Changelog mis à jour
- Build: $(date +%Y%m%d%H%M%S)"

echo_success "Commit de release créé"

# Créer le tag
echo_info "Création du tag v$new_version..."
git tag -a "v$new_version" -m "Release v$new_version

$release_message

Date: $(date)
Build: $(date +%Y%m%d%H%M%S)

Changements principaux:
- Interface utilisateur améliorée
- Système d'authentification complet
- Base de données intégrée
- Panel d'administration
- Notifications Discord"

echo_success "Tag v$new_version créé"

# Pousser vers GitHub
echo_info "Push vers GitHub..."
git push origin master
git push origin "v$new_version"
echo_success "Code et tags poussés vers GitHub"

# Créer la pré-release sur GitHub
echo_info "Création de la pré-release sur GitHub..."

# Préparer les notes de release
release_notes="## 🍕 Pizza This v$new_version

$release_message

### 📋 Changelog

#### 🎉 Nouvelles fonctionnalités
- Interface utilisateur moderne et responsive
- Système d'authentification avec base de données MySQL
- Gestion des utilisateurs avec ID Roleplay
- Page de conditions d'utilisation complète
- Affichage détaillé de l'utilisateur connecté dans le header

#### 🔧 Améliorations techniques
- API RESTful en PHP pour toutes les fonctionnalités
- Base de données MySQL avec tables optimisées
- Système de tokens JWT pour l'authentification
- Integration Discord pour les notifications de contact/réservation
- Panel d'administration avec statistiques temps réel

#### 🎨 Design et UX
- Interface moderne avec animations fluides
- Design responsive pour mobile et desktop
- Formulaires optimisés avec validation côté client et serveur
- Onglets d'authentification avec design amélioré

#### 🔐 Sécurité
- Hachage sécurisé des mots de passe
- Validation stricte des données utilisateur
- Protection contre les injections SQL
- Gestion des sessions sécurisée

### 🚀 Installation

1. Clonez le repository
2. Configurez la base de données avec \`api/setup.php\`
3. Utilisez les comptes de test:
   - **Admin**: 99999 / admin123
   - **Client**: 12345 / client123

### 📊 Statistiques

- **Fichiers modifiés**: $(git diff --name-only HEAD~1 HEAD | wc -l)
- **Lignes ajoutées**: $(git diff --stat HEAD~1 HEAD | tail -n 1 | grep -o '[0-9]* insertion' | grep -o '[0-9]*' || echo '0')
- **Build**: $(date +%Y%m%d%H%M%S)
- **Date**: $(date)

---

**🔗 Liens utiles:**
- [Documentation](./README.md)
- [Changelog complet](./CHANGELOG.md)
- [Conditions d'utilisation](./pages/conditions.html)"

# Créer la pré-release
gh release create "v$new_version" \
    --title "🍕 Pizza This v$new_version" \
    --notes "$release_notes" \
    --prerelease \
    --target master

echo_success "Pré-release v$new_version créée sur GitHub!"

# Résumé final
echo
echo_success "🎉 Release v$new_version terminée avec succès!"
echo
echo_info "📊 Résumé:"
echo "  • Version: $current_version → $new_version"
echo "  • Type: $increment_type"
echo "  • Commit: $(git rev-parse --short HEAD)"
echo "  • Tag: v$new_version"
echo "  • Release: https://github.com/$REPO_OWNER/$REPO_NAME/releases/tag/v$new_version"
echo
echo_info "🔗 Liens utiles:"
echo "  • Repository: https://github.com/$REPO_OWNER/$REPO_NAME"
echo "  • Releases: https://github.com/$REPO_OWNER/$REPO_NAME/releases"
echo "  • Issues: https://github.com/$REPO_OWNER/$REPO_NAME/issues"
echo
echo_warning "💡 Pour la prochaine release, utilisez:"
echo "  • ./release.sh patch \"Corrections\"             # v$new_version → v$(increment_version "$new_version" "patch") (incrémente patch)"
echo "  • ./release.sh minor \"Nouvelles fonctionnalités\" # v$new_version → v$(increment_version "$new_version" "minor") (incrémente patch)"
echo "  • ./release.sh major \"Version importante\"      # v$new_version → v$(increment_version "$new_version" "major") (incrémente minor, reset patch)"
echo

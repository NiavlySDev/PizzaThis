# 🎯 Guide de Démarrage Rapide - Release System

## 🚀 Première utilisation

### 1. Test de l'interface (sans dépendances)
```bash
./test-release.sh
```

### 2. Installation complète (pour vraies releases)
```bash
# Installer GitHub CLI (nécessite sudo)
./install-gh.sh

# Configurer le système
./setup-release.sh

# Authentifier GitHub
gh auth login
```

### 3. Créer une release complète
```bash
./release.sh
```

## 📋 États des scripts

| Script | Fonction | Dépendances | État |
|--------|----------|-------------|------|
| `test-release.sh` | Test interface | Aucune | ✅ Prêt |
| `install-gh.sh` | Install GitHub CLI | sudo | ✅ Prêt |  
| `setup-release.sh` | Configuration | GitHub CLI | ⚠️ Optionnel |
| `release.sh` | Release complète | GitHub CLI | ⚠️ Besoin auth |

## 🎮 Interface Interactive

L'interface vous guide à travers :

1. **Sélection du type** (1-4)
   - Patch: v0.1.9 → v0.1.10
   - Minor: v0.1.9 → v0.1.10 (même que patch)
   - Major: v0.1.2 → v0.2.0

2. **Message personnalisé** (optionnel)

3. **Confirmation** avec récapitulatif complet

## ⚡ Démarrage immédiat

```bash
# Test immédiat sans installation
./test-release.sh

# Résultat: Interface complète + mise à jour version.json
```

Le script de test fonctionne **immédiatement** sans aucune dépendance ! 🎉

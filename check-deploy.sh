#!/bin/bash

echo "🔍 Vérification de la configuration pour Render..."
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

errors=0
warnings=0

# Fonction pour afficher OK
ok() {
    echo -e "${GREEN}✅${NC} $1"
}

# Fonction pour afficher erreur
error() {
    echo -e "${RED}❌${NC} $1"
    ((errors++))
}

# Fonction pour afficher avertissement
warn() {
    echo -e "${YELLOW}⚠️${NC} $1"
    ((warnings++))
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 Vérification des fichiers de déploiement"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Vérifier render.yaml
if [ -f "render.yaml" ]; then
    ok "render.yaml trouvé"
    # Vérifier si c'est la version PostgreSQL
    if grep -q "pgsql" render.yaml; then
        ok "Configuration PostgreSQL détectée"
    else
        warn "Configuration MySQL détectée (PostgreSQL recommandé)"
    fi
else
    error "render.yaml manquant"
fi

# Vérifier Dockerfile
if [ -f "Dockerfile" ]; then
    ok "Dockerfile trouvé"
    # Vérifier si PostgreSQL est supporté
    if grep -q "pdo_pgsql" Dockerfile; then
        ok "Support PostgreSQL dans Dockerfile"
    else
        warn "Support PostgreSQL manquant dans Dockerfile"
    fi
else
    error "Dockerfile manquant"
fi

# Vérifier docker-entrypoint.sh
if [ -f "docker-entrypoint.sh" ]; then
    ok "docker-entrypoint.sh trouvé"
    if [ -x "docker-entrypoint.sh" ]; then
        ok "docker-entrypoint.sh est exécutable"
    else
        warn "docker-entrypoint.sh n'est pas exécutable (sera corrigé)"
        chmod +x docker-entrypoint.sh
    fi
else
    error "docker-entrypoint.sh manquant"
fi

# Vérifier .dockerignore
if [ -f ".dockerignore" ]; then
    ok ".dockerignore trouvé"
else
    warn ".dockerignore manquant (optionnel mais recommandé)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔧 Vérification de la configuration Laravel"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Vérifier composer.json
if [ -f "composer.json" ]; then
    ok "composer.json trouvé"
    
    # Vérifier la version de PHP
    if grep -q '"php": "\^8.1"' composer.json || grep -q '"php": "\^8.2"' composer.json; then
        ok "Version PHP compatible (8.1+)"
    else
        warn "Version PHP pourrait être incompatible"
    fi
else
    error "composer.json manquant"
fi

# Vérifier .env.example
if [ -f ".env.example" ]; then
    ok ".env.example trouvé"
else
    warn ".env.example manquant"
fi

# Vérifier .env local
if [ -f ".env" ]; then
    ok ".env local trouvé"
    
    # Vérifier la configuration PostgreSQL
    if grep -q "DB_CONNECTION=pgsql" .env; then
        ok "Configuration PostgreSQL locale"
    fi
    
    # Vérifier APP_KEY
    if grep -q "APP_KEY=base64:" .env; then
        ok "APP_KEY configurée localement"
    else
        warn "APP_KEY non configurée (exécutez: php artisan key:generate)"
    fi
else
    warn ".env local manquant (normal si pas encore configuré)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📦 Vérification des dépendances"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Vérifier vendor
if [ -d "vendor" ]; then
    ok "Dépendances Composer installées"
else
    warn "Dépendances Composer non installées (exécutez: composer install)"
fi

# Vérifier les dossiers de cache
if [ -d "bootstrap/cache" ]; then
    ok "Dossier bootstrap/cache existe"
else
    error "Dossier bootstrap/cache manquant"
fi

if [ -d "storage" ]; then
    ok "Dossier storage existe"
else
    error "Dossier storage manquant"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 Vérification des permissions"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -w "storage" ]; then
    ok "storage est inscriptible"
else
    warn "storage n'est pas inscriptible (normal sous Windows)"
fi

if [ -w "bootstrap/cache" ]; then
    ok "bootstrap/cache est inscriptible"
else
    warn "bootstrap/cache n'est pas inscriptible (normal sous Windows)"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🌐 Vérification Git"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -d ".git" ]; then
    ok "Dépôt Git initialisé"
    
    # Vérifier la branche
    current_branch=$(git branch --show-current 2>/dev/null)
    if [ "$current_branch" == "production" ]; then
        ok "Sur la branche production"
    else
        warn "Branche actuelle: $current_branch (Render attend: production)"
    fi
    
    # Vérifier si des fichiers ne sont pas commitées
    if [ -n "$(git status --porcelain)" ]; then
        warn "Des fichiers ne sont pas committés"
        echo "   Exécutez: git add . && git commit -m 'Ready for Render deployment'"
    else
        ok "Tous les fichiers sont committés"
    fi
    
    # Vérifier remote
    if git remote -v | grep -q "github.com"; then
        ok "Remote GitHub configuré"
    else
        warn "Remote GitHub non trouvé"
    fi
else
    error "Dépôt Git non initialisé"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 Résumé"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ $errors -eq 0 ] && [ $warnings -eq 0 ]; then
    echo -e "${GREEN}🎉 Parfait ! Tout est prêt pour le déploiement !${NC}"
    echo ""
    echo "Prochaines étapes :"
    echo "1. git push origin production"
    echo "2. Aller sur https://dashboard.render.com"
    echo "3. New + → Blueprint"
    echo "4. Connecter votre dépôt"
    echo ""
elif [ $errors -eq 0 ]; then
    echo -e "${YELLOW}⚠️  $warnings avertissement(s) détecté(s)${NC}"
    echo "Le déploiement devrait fonctionner, mais vérifiez les avertissements."
    echo ""
else
    echo -e "${RED}❌ $errors erreur(s) et $warnings avertissement(s) détecté(s)${NC}"
    echo "Corrigez les erreurs avant de déployer."
    echo ""
    exit 1
fi

echo "📖 Guides disponibles :"
echo "   - RENDER_CONFIG_GUIDE.md (guide de configuration)"
echo "   - QUICK_DEPLOY.md (déploiement rapide)"
echo "   - DEPLOYMENT.md (documentation complète)"
echo ""

#!/bin/bash

# Script de préparation pour le déploiement sur Render
echo "🚀 Préparation du déploiement sur Render..."

# Vérifier si Git est initialisé
if [ ! -d .git ]; then
    echo "❌ Ce projet n'est pas un dépôt Git."
    echo "Initialisation de Git..."
    git init
    git add .
    git commit -m "Initial commit for Render deployment"
else
    echo "✅ Dépôt Git détecté"
fi

# Vérifier les fichiers nécessaires
echo ""
echo "📋 Vérification des fichiers de déploiement..."

if [ -f "render.yaml" ]; then
    echo "✅ render.yaml trouvé"
else
    echo "❌ render.yaml manquant"
fi

if [ -f "Dockerfile" ]; then
    echo "✅ Dockerfile trouvé"
else
    echo "❌ Dockerfile manquant"
fi

if [ -f "docker-entrypoint.sh" ]; then
    echo "✅ docker-entrypoint.sh trouvé"
    chmod +x docker-entrypoint.sh
else
    echo "❌ docker-entrypoint.sh manquant"
fi

if [ -f ".dockerignore" ]; then
    echo "✅ .dockerignore trouvé"
else
    echo "❌ .dockerignore manquant"
fi

# Vérifier les dépendances
echo ""
echo "🔍 Vérification des dépendances..."

if [ -f "composer.json" ]; then
    echo "✅ composer.json trouvé"
    
    # Vérifier si composer est installé
    if command -v composer &> /dev/null; then
        echo "   Mise à jour des dépendances..."
        composer install --no-dev --optimize-autoloader
    else
        echo "⚠️  Composer n'est pas installé"
    fi
else
    echo "❌ composer.json manquant"
fi

# Tester la configuration
echo ""
echo "🧪 Test de la configuration Laravel..."

if [ -f ".env" ]; then
    echo "✅ Fichier .env trouvé"
else
    echo "⚠️  Fichier .env manquant - copie de .env.example"
    if [ -f ".env.example" ]; then
        cp .env.example .env
        php artisan key:generate
    fi
fi

# Vérifier les permissions
echo ""
echo "🔐 Vérification des permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || echo "⚠️  Impossible de modifier les permissions (normal sous Windows)"

# Afficher les instructions
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Préparation terminée !"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📝 Prochaines étapes :"
echo ""
echo "1. Committez les changements :"
echo "   git add ."
echo "   git commit -m 'Add Render deployment configuration'"
echo ""
echo "2. Poussez sur votre dépôt :"
echo "   git push origin production"
echo ""
echo "3. Connectez-vous à Render : https://dashboard.render.com"
echo ""
echo "4. Créez un nouveau Blueprint et connectez votre dépôt"
echo ""
echo "5. Render détectera automatiquement render.yaml"
echo ""
echo "📖 Pour plus de détails, consultez DEPLOYMENT.md"
echo ""
echo "💡 Choix de base de données :"
echo "   - MySQL : Utilisez render.yaml (actuel)"
echo "   - PostgreSQL : Renommez render.yaml.postgres en render.yaml"
echo ""

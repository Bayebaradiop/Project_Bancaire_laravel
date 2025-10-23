#!/bin/bash

# Script de déploiement rapide vers Render
echo "🚀 Déploiement vers Render..."
echo ""

# Vérifier si on est sur la bonne branche
current_branch=$(git branch --show-current)
if [ "$current_branch" != "production" ]; then
    echo "⚠️  Vous êtes sur la branche: $current_branch"
    echo "   Render attend la branche: production"
    echo ""
    read -p "Voulez-vous basculer sur production ? (o/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        git checkout production || {
            echo "❌ Impossible de basculer sur production"
            echo "   Créez la branche avec: git checkout -b production"
            exit 1
        }
    else
        echo "❌ Déploiement annulé"
        exit 1
    fi
fi

# Vérifier qu'il y a des changements
if [ -z "$(git status --porcelain)" ]; then
    echo "✅ Aucun changement à committer"
    echo ""
    read -p "Voulez-vous pousser les changements existants ? (o/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        git push origin production
        echo ""
        echo "✅ Code poussé sur GitHub !"
    else
        echo "❌ Push annulé"
        exit 0
    fi
else
    echo "📝 Fichiers modifiés détectés :"
    git status --short
    echo ""
    
    # Demander un message de commit
    read -p "Message de commit (défaut: 'Ready for Render deployment'): " commit_msg
    if [ -z "$commit_msg" ]; then
        commit_msg="Ready for Render deployment"
    fi
    
    # Ajouter tous les fichiers
    echo "📦 Ajout des fichiers..."
    git add .
    
    # Committer
    echo "💾 Commit..."
    git commit -m "$commit_msg"
    
    # Pousser
    echo "🚀 Push vers GitHub..."
    git push origin production
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "✅ Code poussé avec succès !"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    else
        echo ""
        echo "❌ Erreur lors du push"
        exit 1
    fi
fi

echo ""
echo "🎯 Prochaines étapes :"
echo ""
echo "1. Aller sur https://dashboard.render.com"
echo "2. Cliquer sur 'New +' → 'Blueprint'"
echo "3. Connecter le dépôt : Bayebaradiop/Project_Bancaire_laravel"
echo "4. Branche : production"
echo "5. Render détectera render.yaml automatiquement"
echo "6. Cliquer 'Apply' et attendre 5-10 minutes"
echo ""
echo "📖 Documentation complète : CONFIG_SUMMARY.md"
echo "📖 Guide rapide : QUICK_DEPLOY.md"
echo "📖 Guide de configuration : RENDER_CONFIG_GUIDE.md"
echo ""
echo "🎉 Bonne chance avec votre déploiement !"
echo ""

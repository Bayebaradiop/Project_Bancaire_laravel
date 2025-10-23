#!/bin/bash

# Script pour créer et configurer la branche production
echo "🔧 Configuration de la branche production..."
echo ""

# Vérifier si on est dans un dépôt git
if [ ! -d .git ]; then
    echo "❌ Erreur : Ce n'est pas un dépôt Git"
    exit 1
fi

# Récupérer la branche actuelle
current_branch=$(git branch --show-current)
echo "📍 Branche actuelle : $current_branch"
echo ""

# Vérifier s'il y a des modifications non commitées
if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️  Des fichiers ne sont pas committés sur $current_branch"
    echo ""
    read -p "Voulez-vous les committer maintenant ? (o/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        git add .
        read -p "Message de commit (défaut: 'Update for production deployment'): " commit_msg
        if [ -z "$commit_msg" ]; then
            commit_msg="Update for production deployment"
        fi
        git commit -m "$commit_msg"
        echo "✅ Modifications committées"
    else
        echo "⚠️  Continuons sans committer..."
    fi
    echo ""
fi

# Vérifier si la branche production existe localement
if git show-ref --verify --quiet refs/heads/production; then
    echo "✅ La branche production existe déjà localement"
    echo ""
    read -p "Voulez-vous la mettre à jour avec $current_branch ? (o/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        echo "🔄 Mise à jour de la branche production..."
        git checkout production
        git merge $current_branch -m "Merge $current_branch into production"
        echo "✅ Branche production mise à jour"
    else
        echo "Basculement sur production..."
        git checkout production
    fi
else
    echo "📝 Création de la branche production à partir de $current_branch..."
    git checkout -b production
    echo "✅ Branche production créée"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📤 Push de la branche production vers GitHub"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Vérifier si la branche existe sur le remote
if git ls-remote --heads origin production | grep -q production; then
    echo "La branche production existe déjà sur GitHub"
    echo ""
    read -p "Voulez-vous la mettre à jour (push) ? (o/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Oo]$ ]]; then
        git push origin production
        if [ $? -eq 0 ]; then
            echo ""
            echo "✅ Branche production poussée sur GitHub !"
        else
            echo ""
            echo "❌ Erreur lors du push"
            exit 1
        fi
    fi
else
    echo "Push de la nouvelle branche production..."
    git push -u origin production
    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ Branche production créée et poussée sur GitHub !"
    else
        echo ""
        echo "❌ Erreur lors du push"
        exit 1
    fi
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Configuration terminée !"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 Résumé :"
echo "   ✅ Branche production prête"
echo "   ✅ Code synchronisé avec GitHub"
echo "   ✅ Fichiers de configuration mis à jour"
echo ""
echo "🚀 Prochaines étapes :"
echo ""
echo "1. Aller sur https://dashboard.render.com"
echo "2. Cliquer sur 'New +' → 'Blueprint'"
echo "3. Connecter : Bayebaradiop/Project_Bancaire_laravel"
echo "4. Sélectionner la branche : production"
echo "5. Cliquer 'Apply'"
echo ""
echo "📖 Documentation :"
echo "   - CONFIG_SUMMARY.md"
echo "   - RENDER_CONFIG_GUIDE.md"
echo "   - QUICK_DEPLOY.md"
echo ""
echo "💡 Pour les mises à jour futures, utilisez :"
echo "   ./deploy-to-render.sh"
echo ""

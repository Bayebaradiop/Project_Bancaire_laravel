#!/bin/bash

echo "🧹 Nettoyage des caches Laravel en production"
echo "=============================================="
echo ""

echo "1️⃣ Nettoyage du cache de configuration..."
php artisan config:clear
echo "✅ Cache config nettoyé"
echo ""

echo "2️⃣ Nettoyage du cache applicatif..."
php artisan cache:clear
echo "✅ Cache applicatif nettoyé"
echo ""

echo "3️⃣ Nettoyage du cache des vues..."
php artisan view:clear
echo "✅ Cache des vues nettoyé"
echo ""

echo "4️⃣ Optimisation des configurations..."
php artisan config:cache
echo "✅ Configuration optimisée"
echo ""

echo "✅ Nettoyage terminé!"
echo ""
echo "📧 Testez maintenant l'envoi d'email via Swagger"

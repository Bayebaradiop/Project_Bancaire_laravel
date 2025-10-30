#!/bin/bash

# Script pour diagnostiquer les problèmes d'envoi d'email en production

API_URL="https://baye-bara-diop-project-bancaire-laravel.onrender.com/api"
ADMIN_EMAIL="admin@banque.sn"
ADMIN_PASSWORD="Admin@2025"

echo "=========================================="
echo "DIAGNOSTIC EMAIL PRODUCTION"
echo "=========================================="
echo ""

# 1. Login
echo "1. Connexion admin..."
LOGIN_RESPONSE=$(curl -s -X POST "${API_URL}/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"${ADMIN_EMAIL}\", \"password\": \"${ADMIN_PASSWORD}\"}")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.access_token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo "❌ Impossible d'obtenir le token"
    exit 1
fi
echo "✅ Token obtenu"
echo ""

# 2. Vérifier les jobs en queue
echo "2. Vérification de la queue (jobs en attente)..."
echo "   Connexion SSH à Render nécessaire pour cette commande:"
echo "   SELECT COUNT(*) as pending_jobs FROM jobs;"
echo ""

# 3. Vérifier les failed jobs
echo "3. Vérification des jobs échoués..."
echo "   Connexion SSH à Render nécessaire pour cette commande:"
echo "   SELECT id, exception, failed_at FROM failed_jobs ORDER BY id DESC LIMIT 5;"
echo ""

# 4. Informations à vérifier manuellement sur Render
echo "=========================================="
echo "ÉTAPES DE DIAGNOSTIC RENDER"
echo "=========================================="
echo ""
echo "📋 Connectez-vous à Render Shell et exécutez :"
echo ""
echo "1️⃣ Vérifier les jobs en attente :"
echo "   php artisan queue:monitor"
echo ""
echo "2️⃣ Voir les derniers logs Laravel :"
echo "   tail -100 storage/logs/laravel.log | grep -i 'email\\|mail\\|CompteCreated\\|SendClientNotification'"
echo ""
echo "3️⃣ Vérifier les failed jobs :"
echo "   php artisan queue:failed"
echo ""
echo "4️⃣ Vérifier que le queue worker tourne :"
echo "   ps aux | grep queue:work"
echo ""
echo "5️⃣ Tester la connexion SMTP :"
echo "   php artisan tinker"
echo "   >>> Mail::raw('Test', function(\$message) { \$message->to('nabuudione@gmail.com')->subject('Test'); });"
echo ""
echo "6️⃣ Vérifier la configuration mail :"
echo "   php artisan config:show mail"
echo ""
echo "=========================================="
echo "SOLUTIONS RAPIDES"
echo "=========================================="
echo ""
echo "Si le queue worker ne tourne pas :"
echo "   → Redéployer l'application sur Render"
echo ""
echo "Si les jobs sont en failed_jobs :"
echo "   → php artisan queue:retry all"
echo ""
echo "Si la config mail est incorrecte :"
echo "   → Vérifier les variables d'environnement Render"
echo "   → php artisan config:clear"
echo "   → Redémarrer le service"
echo ""
echo "=========================================="

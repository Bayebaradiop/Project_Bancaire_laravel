#!/bin/bash

# Script pour supprimer un client par email en production

API_URL="https://baye-bara-diop-project-bancaire-laravel.onrender.com/api"
ADMIN_EMAIL="admin@banque.sn"
ADMIN_PASSWORD="Admin@2025"
EMAIL_TO_DELETE="tt3435336@gmail.com"

echo "=========================================="
echo "SUPPRESSION CLIENT PAR EMAIL"
echo "=========================================="
echo ""

# 1. Login
echo "1. Connexion en tant qu'admin..."
LOGIN_RESPONSE=$(curl -s -X POST "${API_URL}/v1/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"${ADMIN_EMAIL}\", \"password\": \"${ADMIN_PASSWORD}\"}")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.access_token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo "❌ Erreur: Impossible d'obtenir le token"
    exit 1
fi

echo "✅ Token obtenu"
echo ""

# 2. Rechercher le client par email
echo "2. Recherche du client avec email: ${EMAIL_TO_DELETE}..."

# Récupérer tous les comptes et filtrer par email client
COMPTES_RESPONSE=$(curl -s -X GET "${API_URL}/v1/comptes" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Accept: application/json")

echo "$COMPTES_RESPONSE" | jq '.'

echo ""
echo "Pour supprimer manuellement, connectez-vous au Shell Render et exécutez:"
echo ""
echo "php artisan tinker"
echo ">>> \$user = App\\Models\\User::where('email', '${EMAIL_TO_DELETE}')->first();"
echo ">>> if (\$user) {"
echo ">>>   \$client = \$user->client;"
echo ">>>   if (\$client) {"
echo ">>>     foreach (\$client->comptes as \$compte) {"
echo ">>>       \$compte->forceDelete();"
echo ">>>     }"
echo ">>>     \$client->forceDelete();"
echo ">>>   }"
echo ">>>   \$user->forceDelete();"
echo ">>>   echo 'Client supprimé';"
echo ">>> } else {"
echo ">>>   echo 'Aucun utilisateur trouvé';"
echo ">>> }"
echo ""
echo "=========================================="

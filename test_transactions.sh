#!/bin/bash

# Script de test des endpoints Transactions
# Usage: ./test_transactions.sh

set -e

BASE_URL="http://localhost:8000/api/v1"
ADMIN_TOKEN=""
CLIENT_TOKEN=""

echo "========================================="
echo "🧪 TEST TRANSACTIONS ENDPOINTS"
echo "========================================="
echo ""

# Fonction pour afficher les résultats
function print_result() {
    echo "📋 $1"
    echo "Response:"
    echo "$2" | jq '.' 2>/dev/null || echo "$2"
    echo ""
    echo "---"
    echo ""
}

# 1. Login Admin
echo "1️⃣  Login Admin..."
ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@banque.sn",
    "password": "Admin@2025"
  }')

ADMIN_TOKEN=$(echo "$ADMIN_RESPONSE" | jq -r '.data.access_token' 2>/dev/null)

if [ "$ADMIN_TOKEN" = "null" ] || [ -z "$ADMIN_TOKEN" ]; then
    echo "❌ Erreur login admin"
    echo "$ADMIN_RESPONSE"
    exit 1
fi

echo "✅ Admin logged in"
echo "Token: ${ADMIN_TOKEN:0:20}..."
echo ""

# 2. Login Client
echo "2️⃣  Login Client..."
CLIENT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "client@test.sn",
    "password": "Client@2025"
  }')

CLIENT_TOKEN=$(echo "$CLIENT_RESPONSE" | jq -r '.data.access_token' 2>/dev/null)

if [ "$CLIENT_TOKEN" = "null" ] || [ -z "$CLIENT_TOKEN" ]; then
    echo "❌ Erreur login client"
    echo "$CLIENT_RESPONSE"
    exit 1
fi

echo "✅ Client logged in"
echo "Token: ${CLIENT_TOKEN:0:20}..."
echo ""

# 3. Obtenir les comptes du client
echo "3️⃣  Récupération des comptes du client..."
COMPTES_RESPONSE=$(curl -s -X GET "$BASE_URL/comptes" \
  -H "Authorization: Bearer $CLIENT_TOKEN")

COMPTE_CLIENT=$(echo "$COMPTES_RESPONSE" | jq -r '.data[0].numeroCompte' 2>/dev/null)

if [ "$COMPTE_CLIENT" = "null" ] || [ -z "$COMPTE_CLIENT" ]; then
    echo "❌ Aucun compte trouvé pour le client"
    echo "$COMPTES_RESPONSE"
    exit 1
fi

echo "✅ Compte client: $COMPTE_CLIENT"
echo ""

# 4. Test Dépôt (Admin)
echo "4️⃣  Test Dépôt (Admin sur compte client)..."
DEPOT_RESPONSE=$(curl -s -X POST "$BASE_URL/transactions/depot" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"compte_destinataire\": \"$COMPTE_CLIENT\",
    \"montant\": 100000,
    \"devise\": \"FCFA\",
    \"description\": \"Dépôt test initial\"
  }")

print_result "Dépôt Admin" "$DEPOT_RESPONSE"

# Vérifier le succès
if echo "$DEPOT_RESPONSE" | jq -e '.success == true' >/dev/null 2>&1; then
    echo "✅ Dépôt effectué avec succès"
else
    echo "❌ Erreur lors du dépôt"
fi
echo ""

# 5. Test Dépôt par Client (doit échouer)
echo "5️⃣  Test Dépôt (Client - doit échouer)..."
DEPOT_CLIENT_RESPONSE=$(curl -s -X POST "$BASE_URL/transactions/depot" \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"compte_destinataire\": \"$COMPTE_CLIENT\",
    \"montant\": 50000,
    \"devise\": \"FCFA\"
  }")

print_result "Dépôt Client (doit échouer)" "$DEPOT_CLIENT_RESPONSE"

# 6. Test Retrait (Client)
echo "6️⃣  Test Retrait (Client)..."
RETRAIT_RESPONSE=$(curl -s -X POST "$BASE_URL/transactions/retrait" \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"compte_source\": \"$COMPTE_CLIENT\",
    \"montant\": 5000,
    \"devise\": \"FCFA\",
    \"description\": \"Retrait test\"
  }")

print_result "Retrait Client" "$RETRAIT_RESPONSE"

if echo "$RETRAIT_RESPONSE" | jq -e '.success == true' >/dev/null 2>&1; then
    echo "✅ Retrait effectué avec succès"
else
    echo "❌ Erreur lors du retrait"
fi
echo ""

# 7. Obtenir un deuxième compte pour le transfert
echo "7️⃣  Récupération d'un deuxième compte..."
ALL_COMPTES_RESPONSE=$(curl -s -X GET "$BASE_URL/comptes" \
  -H "Authorization: Bearer $ADMIN_TOKEN")

COMPTE_DEST=$(echo "$ALL_COMPTES_RESPONSE" | jq -r ".data[] | select(.numeroCompte != \"$COMPTE_CLIENT\") | .numeroCompte" 2>/dev/null | head -1)

if [ "$COMPTE_DEST" = "null" ] || [ -z "$COMPTE_DEST" ]; then
    echo "❌ Aucun compte destinataire trouvé"
    echo "Création d'un nouveau compte..."
    
    # Créer un nouveau compte
    NEW_COMPTE_RESPONSE=$(curl -s -X POST "$BASE_URL/comptes" \
      -H "Authorization: Bearer $ADMIN_TOKEN" \
      -H "Content-Type: application/json" \
      -d '{
        "client_id": "existing-client-uuid",
        "typeCompte": "courant",
        "devise": "FCFA"
      }')
    
    COMPTE_DEST=$(echo "$NEW_COMPTE_RESPONSE" | jq -r '.data.numeroCompte' 2>/dev/null)
fi

echo "✅ Compte destinataire: $COMPTE_DEST"
echo ""

# 8. Test Transfert (Client)
echo "8️⃣  Test Transfert (Client)..."
TRANSFERT_RESPONSE=$(curl -s -X POST "$BASE_URL/transactions/transfert" \
  -H "Authorization: Bearer $CLIENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"compte_source\": \"$COMPTE_CLIENT\",
    \"compte_destinataire\": \"$COMPTE_DEST\",
    \"montant\": 10000,
    \"devise\": \"FCFA\",
    \"description\": \"Transfert test\"
  }")

print_result "Transfert Client" "$TRANSFERT_RESPONSE"

NUMERO_TRANSACTION=""
if echo "$TRANSFERT_RESPONSE" | jq -e '.success == true' >/dev/null 2>&1; then
    echo "✅ Transfert effectué avec succès"
    NUMERO_TRANSACTION=$(echo "$TRANSFERT_RESPONSE" | jq -r '.data.numeroTransaction' 2>/dev/null)
    echo "Numéro transaction: $NUMERO_TRANSACTION"
else
    echo "❌ Erreur lors du transfert"
fi
echo ""

# 9. Liste des transactions (Client)
echo "9️⃣  Liste des transactions du client..."
LIST_RESPONSE=$(curl -s -X GET "$BASE_URL/transactions?per_page=5" \
  -H "Authorization: Bearer $CLIENT_TOKEN")

print_result "Liste Transactions Client" "$LIST_RESPONSE"

# 10. Détails d'une transaction
if [ ! -z "$NUMERO_TRANSACTION" ] && [ "$NUMERO_TRANSACTION" != "null" ]; then
    echo "🔟 Détails de la transaction $NUMERO_TRANSACTION..."
    
    # Get transaction ID
    TRANSACTION_ID=$(curl -s -X GET "$BASE_URL/transactions" \
      -H "Authorization: Bearer $CLIENT_TOKEN" | jq -r ".data[] | select(.numeroTransaction == \"$NUMERO_TRANSACTION\") | .id" 2>/dev/null | head -1)
    
    if [ ! -z "$TRANSACTION_ID" ] && [ "$TRANSACTION_ID" != "null" ]; then
        DETAIL_RESPONSE=$(curl -s -X GET "$BASE_URL/transactions/$TRANSACTION_ID" \
          -H "Authorization: Bearer $CLIENT_TOKEN")
        
        print_result "Détails Transaction" "$DETAIL_RESPONSE"
    fi
fi
echo ""

# 11. Test Annulation (si < 24h)
if [ ! -z "$NUMERO_TRANSACTION" ] && [ "$NUMERO_TRANSACTION" != "null" ]; then
    echo "1️⃣1️⃣  Test Annulation de la transaction..."
    ANNULATION_RESPONSE=$(curl -s -X DELETE "$BASE_URL/transactions/$NUMERO_TRANSACTION" \
      -H "Authorization: Bearer $CLIENT_TOKEN")
    
    print_result "Annulation Transaction" "$ANNULATION_RESPONSE"
    
    if echo "$ANNULATION_RESPONSE" | jq -e '.success == true' >/dev/null 2>&1; then
        echo "✅ Transaction annulée avec succès"
    else
        echo "⚠️  Annulation impossible (peut-être > 24h ou solde insuffisant)"
    fi
fi
echo ""

# 12. Test filtres
echo "1️⃣2️⃣  Test des filtres..."
FILTER_RESPONSE=$(curl -s -X GET "$BASE_URL/transactions?type=transfert&per_page=3" \
  -H "Authorization: Bearer $CLIENT_TOKEN")

print_result "Filtrage par type=transfert" "$FILTER_RESPONSE"

echo ""
echo "========================================="
echo "✅ TESTS TERMINÉS"
echo "========================================="

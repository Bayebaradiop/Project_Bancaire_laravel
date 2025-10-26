#!/bin/bash

echo "🧪 Test des accès aux comptes"
echo "=============================="
echo ""

BASE_URL="http://localhost:8000/api/v1"

# Test 1: Admin
echo "📝 Test 1: Login Admin"
echo "---------------------"
curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@banque.sn", "password": "Admin@2025"}' \
  -c cookies_admin.txt | jq -r '.message'

echo ""
echo "📝 Test 2: Admin récupère tous les comptes"
echo "-------------------------------------------"
curl -s -X GET "$BASE_URL/comptes" \
  -b cookies_admin.txt | jq '{success, message, total: .pagination.total, comptes: [.data[] | {numeroCompte, titulaire, type, solde}]}'

echo ""
echo "📝 Test 3: Login Client"
echo "----------------------"
curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "client@banque.sn", "password": "Client@2025"}' \
  -c cookies_client.txt | jq -r '.message'

echo ""
echo "📝 Test 4: Client récupère ses comptes"
echo "--------------------------------------"
curl -s -X GET "$BASE_URL/comptes" \
  -b cookies_client.txt | jq '{success, message, total: .pagination.total, comptes: [.data[] | {numeroCompte, titulaire, type, solde}]}'

echo ""
echo "📝 Test 5: Login Client 2 (Fatou)"
echo "---------------------------------"
curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "fatou@banque.sn", "password": "Client@2025"}' \
  -c cookies_fatou.txt | jq -r '.message'

echo ""
echo "📝 Test 6: Client 2 récupère ses comptes"
echo "----------------------------------------"
curl -s -X GET "$BASE_URL/comptes" \
  -b cookies_fatou.txt | jq '{success, message, total: .pagination.total, comptes: [.data[] | {numeroCompte, titulaire, type, solde}]}'

# Nettoyage
rm -f cookies_*.txt

echo ""
echo "✅ Tests terminés !"

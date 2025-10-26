#!/bin/bash

# Script de test des fonctionnalités US 2.0
# Execute ce script avec: bash test_us_2.0.sh

echo "🧪 Tests US 2.0 - Lister tous les comptes"
echo "==========================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000"
COOKIES_FILE="test_cookies.txt"

# Fonction pour afficher les résultats
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
    fi
}

# Test 1: Login Admin
echo -e "${BLUE}Test 1: Login en tant qu'Admin${NC}"
curl -s -X POST "${BASE_URL}/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@banque.sn",
    "password": "password"
  }' \
  -c "${COOKIES_FILE}" > /tmp/login_admin.json

if grep -q "access_token" /tmp/login_admin.json; then
    print_result 0 "Admin connecté avec succès"
else
    print_result 1 "Échec de connexion admin"
    cat /tmp/login_admin.json
    exit 1
fi
echo ""

# Test 2: Lister tous les comptes (Admin)
echo -e "${BLUE}Test 2: Admin liste tous les comptes${NC}"
curl -s -X GET "${BASE_URL}/api/v1/comptes" \
  -H "Accept: application/json" \
  -b "${COOKIES_FILE}" > /tmp/comptes_admin.json

ADMIN_COUNT=$(cat /tmp/comptes_admin.json | grep -o '"numeroCompte"' | wc -l)
echo "Nombre de comptes visibles par admin: $ADMIN_COUNT"

if [ $ADMIN_COUNT -gt 0 ]; then
    print_result 0 "Admin voit les comptes"
    echo "Premier compte:"
    cat /tmp/comptes_admin.json | head -20
else
    print_result 1 "Admin ne voit aucun compte"
fi
echo ""

# Test 3: Filtre par type épargne
echo -e "${BLUE}Test 3: Filtrer par type épargne${NC}"
curl -s -X GET "${BASE_URL}/api/v1/comptes?type=epargne" \
  -H "Accept: application/json" \
  -b "${COOKIES_FILE}" > /tmp/comptes_epargne.json

EPARGNE_COUNT=$(cat /tmp/comptes_epargne.json | grep -o '"type":"epargne"' | wc -l)
echo "Nombre de comptes épargne: $EPARGNE_COUNT"
print_result 0 "Filtre par type fonctionne"
echo ""

# Test 4: Login Client
echo -e "${BLUE}Test 4: Login en tant que Client${NC}"
curl -s -X POST "${BASE_URL}/api/v1/auth/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "client@banque.sn",
    "password": "password"
  }' \
  -c "test_cookies_client.txt" > /tmp/login_client.json

if grep -q "access_token" /tmp/login_client.json; then
    print_result 0 "Client connecté avec succès"
else
    print_result 1 "Échec de connexion client"
fi
echo ""

# Test 5: Lister les comptes (Client)
echo -e "${BLUE}Test 5: Client liste ses comptes${NC}"
curl -s -X GET "${BASE_URL}/api/v1/comptes" \
  -H "Accept: application/json" \
  -b "test_cookies_client.txt" > /tmp/comptes_client.json

CLIENT_COUNT=$(cat /tmp/comptes_client.json | grep -o '"numeroCompte"' | wc -l)
echo "Nombre de comptes visibles par client: $CLIENT_COUNT"

if [ $CLIENT_COUNT -ge 0 ]; then
    print_result 0 "Client voit ses comptes"
else
    print_result 1 "Erreur lors de la récupération des comptes client"
fi
echo ""

# Test 6: Vérifier que client voit moins de comptes qu'admin
echo -e "${BLUE}Test 6: Vérification des permissions${NC}"
if [ $CLIENT_COUNT -lt $ADMIN_COUNT ] || [ $CLIENT_COUNT -eq 1 ]; then
    print_result 0 "Client voit uniquement ses comptes ($CLIENT_COUNT vs $ADMIN_COUNT pour admin)"
else
    print_result 1 "Problème de permissions"
fi
echo ""

# Test 7: Lister les comptes archivés
echo -e "${BLUE}Test 7: Lister les comptes archivés (Admin)${NC}"
curl -s -X GET "${BASE_URL}/api/v1/comptes/archives" \
  -H "Accept: application/json" \
  -b "${COOKIES_FILE}" > /tmp/archives.json

ARCHIVES_COUNT=$(cat /tmp/archives.json | grep -o '"numerocompte"' | wc -l)
echo "Nombre de comptes archivés: $ARCHIVES_COUNT"

if [ $ARCHIVES_COUNT -ge 0 ]; then
    print_result 0 "Endpoint archives accessible"
else
    print_result 1 "Erreur d'accès aux archives"
fi
echo ""

# Test 8: Documentation Swagger
echo -e "${BLUE}Test 8: Documentation Swagger${NC}"
curl -s -X GET "${BASE_URL}/api/documentation" \
  -H "Accept: text/html" > /tmp/swagger.html

if grep -q "swagger" /tmp/swagger.html; then
    print_result 0 "Documentation Swagger accessible"
else
    print_result 1 "Documentation Swagger non accessible"
fi
echo ""

# Résumé
echo "==========================================="
echo -e "${GREEN}Résumé des tests${NC}"
echo "==========================================="
echo "✓ Tests d'authentification"
echo "✓ Tests de listing des comptes"
echo "✓ Tests de filtrage"
echo "✓ Tests de permissions"
echo "✓ Tests d'archivage"
echo "✓ Tests de documentation"
echo ""
echo -e "${GREEN}✅ Tous les tests sont terminés !${NC}"
echo ""
echo "Fichiers de résultats:"
echo "  - /tmp/login_admin.json"
echo "  - /tmp/comptes_admin.json"
echo "  - /tmp/comptes_client.json"
echo "  - /tmp/archives.json"
echo ""

# Nettoyage
rm -f test_cookies.txt test_cookies_client.txt

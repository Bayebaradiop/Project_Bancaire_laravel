#!/bin/bash

# =============================================================================
# TESTS CURL - API BANCAIRE v1
# =============================================================================
# URL de base
BASE_URL="https://baye-bara-diop-project-bancaire-laravel.onrender.com/api/v1"

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables globales
TOKEN=""
COMPTE_ID=""
NUMERO_COMPTE=""

# =============================================================================
# FONCTIONS UTILITAIRES
# =============================================================================

print_section() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_test() {
    echo -e "${YELLOW}TEST: $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}\n"
}

print_error() {
    echo -e "${RED}✗ $1${NC}\n"
}

# =============================================================================
# 1. HEALTH CHECK
# =============================================================================

test_health_check() {
    print_section "1. HEALTH CHECK"
    print_test "GET /health"
    
    curl -X GET "${BASE_URL}/health" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Health check terminé"
}

# =============================================================================
# 2. AUTHENTIFICATION
# =============================================================================

test_login_admin() {
    print_section "2. AUTHENTIFICATION - LOGIN ADMIN"
    print_test "POST /auth/login (Admin)"
    
    RESPONSE=$(curl -X POST "${BASE_URL}/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "email": "admin@banque.sn",
            "password": "Admin@2024"
        }' \
        -w "\nHTTP_STATUS:%{http_code}" \
        -s)
    
    HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
    BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')
    
    echo "$BODY" | jq '.'
    echo -e "HTTP Status: $HTTP_STATUS\n"
    
    if [ "$HTTP_STATUS" = "200" ]; then
        TOKEN=$(echo "$BODY" | jq -r '.token // .data.token // .access_token // empty')
        if [ -n "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
            print_success "Login admin réussi - Token récupéré"
            echo "Token: ${TOKEN:0:50}..."
        else
            print_error "Token non trouvé dans la réponse"
        fi
    else
        print_error "Login admin échoué"
    fi
}

# =============================================================================
# 3. CRÉATION DE COMPTE
# =============================================================================

test_create_compte() {
    print_section "3. CRÉATION DE COMPTE"
    print_test "POST /comptes"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    RESPONSE=$(curl -X POST "${BASE_URL}/comptes" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "type": "epargne",
            "devise": "FCFA",
            "client": {
                "titulaire": "Test CURL User",
                "nci": "1'.$(date +%s)'234567",
                "email": "test.curl.'$(date +%s)'@example.com",
                "telephone": "+221771234'$(shuf -i 100-999 -n 1)'",
                "adresse": "Dakar, Senegal"
            }
        }' \
        -w "\nHTTP_STATUS:%{http_code}" \
        -s)
    
    HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
    BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')
    
    echo "$BODY" | jq '.'
    echo -e "HTTP Status: $HTTP_STATUS\n"
    
    if [ "$HTTP_STATUS" = "201" ] || [ "$HTTP_STATUS" = "200" ]; then
        COMPTE_ID=$(echo "$BODY" | jq -r '.data.id // empty')
        NUMERO_COMPTE=$(echo "$BODY" | jq -r '.data.numeroCompte // empty')
        print_success "Compte créé avec succès"
        echo "Compte ID: $COMPTE_ID"
        echo "Numéro: $NUMERO_COMPTE"
    else
        print_error "Création de compte échouée"
    fi
}

# =============================================================================
# 4. LISTER LES COMPTES
# =============================================================================

test_list_comptes() {
    print_section "4. LISTER LES COMPTES"
    print_test "GET /comptes?page=1&limit=5"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    curl -X GET "${BASE_URL}/comptes?page=1&limit=5&type=epargne&sort=dateCreation&order=desc" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Liste des comptes récupérée"
}

# =============================================================================
# 5. RÉCUPÉRER UN COMPTE PAR ID
# =============================================================================

test_get_compte_by_id() {
    print_section "5. RÉCUPÉRER COMPTE PAR ID"
    print_test "GET /comptes/{id}"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    if [ -z "$COMPTE_ID" ]; then
        print_error "Pas de compte ID - Créer un compte d'abord"
        return
    fi
    
    curl -X GET "${BASE_URL}/comptes/${COMPTE_ID}" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Compte récupéré par ID"
}

# =============================================================================
# 6. RÉCUPÉRER UN COMPTE PAR NUMÉRO
# =============================================================================

test_get_compte_by_numero() {
    print_section "6. RÉCUPÉRER COMPTE PAR NUMÉRO"
    print_test "GET /comptes/numero/{numero}"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    if [ -z "$NUMERO_COMPTE" ]; then
        print_error "Pas de numéro de compte - Créer un compte d'abord"
        return
    fi
    
    curl -X GET "${BASE_URL}/comptes/numero/${NUMERO_COMPTE}" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Compte récupéré par numéro"
}

# =============================================================================
# 7. METTRE À JOUR UN COMPTE
# =============================================================================

test_update_compte() {
    print_section "7. METTRE À JOUR UN COMPTE"
    print_test "PATCH /comptes/{id}"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    if [ -z "$COMPTE_ID" ]; then
        print_error "Pas de compte ID - Créer un compte d'abord"
        return
    fi
    
    curl -X PATCH "${BASE_URL}/comptes/${COMPTE_ID}" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "devise": "EUR"
        }' \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Compte mis à jour"
}

# =============================================================================
# 8. BLOQUER UN COMPTE
# =============================================================================

test_bloquer_compte() {
    print_section "8. BLOQUER UN COMPTE ÉPARGNE"
    print_test "POST /comptes/{id}/bloquer"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    if [ -z "$COMPTE_ID" ]; then
        print_error "Pas de compte ID - Créer un compte d'abord"
        return
    fi
    
    # Date dans 2 jours
    DATE_BLOCAGE=$(date -d "+2 days" +%Y-%m-%d)
    
    curl -X POST "${BASE_URL}/comptes/${COMPTE_ID}/bloquer" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{
            "dateDebutBlocage": "'"$DATE_BLOCAGE"'",
            "raison": "Test blocage automatique"
        }' \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Blocage programmé"
}

# =============================================================================
# 9. DÉBLOQUER UN COMPTE
# =============================================================================

test_debloquer_compte() {
    print_section "9. DÉBLOQUER UN COMPTE"
    print_test "POST /comptes/{id}/debloquer"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    if [ -z "$COMPTE_ID" ]; then
        print_error "Pas de compte ID - Créer un compte d'abord"
        return
    fi
    
    curl -X POST "${BASE_URL}/comptes/${COMPTE_ID}/debloquer" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{}' \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Compte débloqué"
}

# =============================================================================
# 10. LISTER LES COMPTES ARCHIVÉS
# =============================================================================

test_list_archives() {
    print_section "10. LISTER LES COMPTES ARCHIVÉS"
    print_test "GET /comptes/archives"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    curl -X GET "${BASE_URL}/comptes/archives" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Archives récupérées"
}

# =============================================================================
# 11. SUPPRIMER UN COMPTE
# =============================================================================

test_delete_compte() {
    print_section "11. SUPPRIMER UN COMPTE ÉPARGNE"
    print_test "DELETE /comptes/{numero}"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    if [ -z "$NUMERO_COMPTE" ]; then
        print_error "Pas de numéro de compte - Créer un compte d'abord"
        return
    fi
    
    curl -X DELETE "${BASE_URL}/comptes/${NUMERO_COMPTE}" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Compte supprimé et archivé"
}

# =============================================================================
# 12. RESTAURER UN COMPTE
# =============================================================================

test_restore_compte() {
    print_section "12. RESTAURER UN COMPTE"
    print_test "POST /comptes/restore/{id}"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    if [ -z "$COMPTE_ID" ]; then
        print_error "Pas de compte ID"
        return
    fi
    
    curl -X POST "${BASE_URL}/comptes/restore/${COMPTE_ID}" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    print_success "Compte restauré"
}

# =============================================================================
# 13. REFRESH TOKEN
# =============================================================================

test_refresh_token() {
    print_section "13. REFRESH TOKEN"
    print_test "POST /auth/refresh"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Authentification requise d'abord"
        return
    fi
    
    RESPONSE=$(curl -X POST "${BASE_URL}/auth/refresh" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP_STATUS:%{http_code}" \
        -s)
    
    HTTP_STATUS=$(echo "$RESPONSE" | grep "HTTP_STATUS" | cut -d':' -f2)
    BODY=$(echo "$RESPONSE" | sed '/HTTP_STATUS/d')
    
    echo "$BODY" | jq '.'
    echo -e "HTTP Status: $HTTP_STATUS\n"
    
    if [ "$HTTP_STATUS" = "200" ]; then
        NEW_TOKEN=$(echo "$BODY" | jq -r '.token // .data.token // .access_token // empty')
        if [ -n "$NEW_TOKEN" ] && [ "$NEW_TOKEN" != "null" ]; then
            TOKEN="$NEW_TOKEN"
            print_success "Token rafraîchi"
        fi
    else
        print_error "Refresh token échoué"
    fi
}

# =============================================================================
# 14. LOGOUT
# =============================================================================

test_logout() {
    print_section "14. LOGOUT"
    print_test "POST /auth/logout"
    
    if [ -z "$TOKEN" ]; then
        print_error "Pas de token - Déjà déconnecté"
        return
    fi
    
    curl -X POST "${BASE_URL}/auth/logout" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n" \
        -s | jq '.'
    
    TOKEN=""
    print_success "Déconnexion réussie"
}

# =============================================================================
# MENU PRINCIPAL
# =============================================================================

show_menu() {
    echo -e "\n${BLUE}╔════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║   TESTS API BANCAIRE - MENU PRINCIPAL  ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════╝${NC}\n"
    echo "1)  Health Check"
    echo "2)  Login Admin"
    echo "3)  Créer un compte"
    echo "4)  Lister les comptes"
    echo "5)  Récupérer compte par ID"
    echo "6)  Récupérer compte par numéro"
    echo "7)  Mettre à jour un compte"
    echo "8)  Bloquer un compte"
    echo "9)  Débloquer un compte"
    echo "10) Lister les archives"
    echo "11) Supprimer un compte"
    echo "12) Restaurer un compte"
    echo "13) Refresh token"
    echo "14) Logout"
    echo "---"
    echo "99) TOUT TESTER (séquence complète)"
    echo "0)  Quitter"
    echo ""
    read -p "Choisissez une option: " choice
    echo ""
}

# =============================================================================
# SÉQUENCE COMPLÈTE DE TESTS
# =============================================================================

run_all_tests() {
    print_section "EXÉCUTION DE TOUS LES TESTS"
    
    test_health_check
    sleep 1
    
    test_login_admin
    sleep 1
    
    test_create_compte
    sleep 1
    
    test_list_comptes
    sleep 1
    
    test_get_compte_by_id
    sleep 1
    
    test_get_compte_by_numero
    sleep 1
    
    test_update_compte
    sleep 1
    
    test_bloquer_compte
    sleep 1
    
    test_debloquer_compte
    sleep 1
    
    test_list_archives
    sleep 1
    
    test_refresh_token
    sleep 1
    
    # On ne supprime pas pour pouvoir réutiliser le compte
    # test_delete_compte
    # test_restore_compte
    
    test_logout
    
    print_section "TOUS LES TESTS TERMINÉS"
}

# =============================================================================
# BOUCLE PRINCIPALE
# =============================================================================

main() {
    # Vérifier si jq est installé
    if ! command -v jq &> /dev/null; then
        echo -e "${RED}Erreur: 'jq' n'est pas installé${NC}"
        echo "Installez-le avec: sudo apt install jq"
        exit 1
    fi
    
    while true; do
        show_menu
        
        case $choice in
            1) test_health_check ;;
            2) test_login_admin ;;
            3) test_create_compte ;;
            4) test_list_comptes ;;
            5) test_get_compte_by_id ;;
            6) test_get_compte_by_numero ;;
            7) test_update_compte ;;
            8) test_bloquer_compte ;;
            9) test_debloquer_compte ;;
            10) test_list_archives ;;
            11) test_delete_compte ;;
            12) test_restore_compte ;;
            13) test_refresh_token ;;
            14) test_logout ;;
            99) run_all_tests ;;
            0) echo -e "${GREEN}Au revoir!${NC}"; exit 0 ;;
            *) echo -e "${RED}Option invalide${NC}" ;;
        esac
        
        read -p "Appuyez sur Entrée pour continuer..."
    done
}

# Lancer le script
main

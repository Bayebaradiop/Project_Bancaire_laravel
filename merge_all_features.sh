#!/bin/bash

# Couleurs pour le terminal
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "╔══════════════════════════════════════════════════════════╗"
echo "║     MERGE DE TOUTES LES FEATURES DANS PRODUCTION        ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# S'assurer qu'on est sur production
git checkout production

# Liste des branches à merger dans l'ordre logique
BRANCHES=(
  "feature/auth-passport-httponly"
  "feature/creation-compte-US-2.2"
  "feature/get-compte-specifique-US-2.1"
  "feature/update-compte-us2.3"
  "feature/suppression-US-2.4"
  "feature/bloquer-debloquer-compte-US-2.5"
  "feature/archivage-US-2.6"
  "dev/v1.0.0"
)

# Compteurs
SUCCESS=0
FAILED=0

for BRANCH in "${BRANCHES[@]}"; do
  echo -e "${YELLOW}→ Merge de $BRANCH...${NC}"
  
  # Tenter le merge avec stratégie ours (on garde tout de la branche feature)
  if git merge origin/$BRANCH --no-edit -X theirs; then
    echo -e "${GREEN}✅ $BRANCH mergé avec succès${NC}"
    ((SUCCESS++))
  else
    echo -e "${RED}❌ Conflit avec $BRANCH${NC}"
    echo "Résolution automatique des conflits..."
    
    # Accepter automatiquement leur version pour tous les conflits
    git checkout --theirs .
    git add .
    git commit --no-edit
    
    if [ $? -eq 0 ]; then
      echo -e "${GREEN}✅ Conflits résolus et $BRANCH mergé${NC}"
      ((SUCCESS++))
    else
      echo -e "${RED}❌ Impossible de merger $BRANCH${NC}"
      ((FAILED++))
      git merge --abort
    fi
  fi
  
  echo ""
done

echo "╔══════════════════════════════════════════════════════════╗"
echo "║                    RÉSUMÉ DU MERGE                       ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo -e "${GREEN}Succès: $SUCCESS${NC}"
echo -e "${RED}Échecs: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
  echo -e "${GREEN}🎉 Tous les merges ont réussi !${NC}"
  echo "Voulez-vous pousser vers origin/production ? (y/n)"
else
  echo -e "${YELLOW}⚠️  Certains merges ont échoué. Vérifiez manuellement.${NC}"
fi


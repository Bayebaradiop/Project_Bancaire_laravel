# 🚀 Guide Rapide - Accès aux Comptes

## Pour Commencer

### 1. Tester en tant qu'Admin
```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@banque.sn", "password": "Admin@2025"}' \
  -c cookies.txt

# Voir tous les comptes
curl -X GET http://localhost:8000/api/v1/comptes -b cookies.txt
```

### 2. Tester en tant que Client
```bash
# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "client@banque.sn", "password": "Client@2025"}' \
  -c cookies.txt

# Voir ses comptes
curl -X GET http://localhost:8000/api/v1/comptes -b cookies.txt
```

## Tests Automatiques

```bash
# Tous les tests
php artisan test --filter CompteAccessTest

# Test avec détails
php artisan test --filter CompteAccessTest --verbose
```

## Test Rapide avec Script
```bash
./test_comptes_access.sh
```

## Documentation Complète
- 📖 `RESUME_COMPTES_ACCESS.md` - Vue d'ensemble
- 📚 `COMPTES_ACCESS_DOCUMENTATION.md` - Documentation détaillée
- 🔧 `IMPLEMENTATION_COMPTES_ACCESS.md` - Détails techniques

## Ce qui a été implémenté
✅ Admin voit tous les comptes  
✅ Client voit uniquement ses comptes  
✅ Authentification requise  
✅ Filtrage sécurisé  
✅ Cache optimisé  
✅ Tests complets

## Résultat Attendu

### Admin
- Voit TOUS les comptes de tous les clients
- Message : "Liste des comptes récupérée avec succès"

### Client  
- Voit UNIQUEMENT ses propres comptes
- Message : "Vos comptes ont été récupérés avec succès"

## Support
Pour plus de détails, consultez les fichiers de documentation créés.

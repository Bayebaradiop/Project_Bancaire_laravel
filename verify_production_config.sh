#!/bin/bash

# ✅ CHECKLIST PRODUCTION - Vérification complète du système email

echo "=========================================="
echo "✅ CHECKLIST PRODUCTION EMAIL"
echo "=========================================="
echo ""

echo "📋 CONFIGURATION ACTUELLE:"
echo ""

# 1. Vérifier les fichiers essentiels
echo "1️⃣ FICHIERS ESSENTIELS"
echo ""

files=(
    "app/Events/CompteCreated.php"
    "app/Listeners/SendClientNotification.php"
    "app/Mail/CompteCreatedMail.php"
    "app/Observers/CompteObserver.php"
    "app/Providers/EventServiceProvider.php"
    "app/Providers/AppServiceProvider.php"
    "docker/supervisor/supervisord.conf"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ $file"
    else
        echo "   ❌ MANQUANT: $file"
    fi
done

echo ""
echo "2️⃣ CONFIGURATION EVENT/LISTENER"
echo ""

# Vérifier EventServiceProvider
if grep -q "CompteCreated::class" app/Providers/EventServiceProvider.php; then
    echo "   ✅ Event CompteCreated enregistré"
else
    echo "   ❌ Event CompteCreated NON enregistré"
fi

if grep -q "SendClientNotification::class" app/Providers/EventServiceProvider.php; then
    echo "   ✅ Listener SendClientNotification enregistré"
else
    echo "   ❌ Listener SendClientNotification NON enregistré"
fi

echo ""
echo "3️⃣ CONFIGURATION OBSERVER"
echo ""

if grep -q "Compte::observe(CompteObserver::class)" app/Providers/AppServiceProvider.php; then
    echo "   ✅ CompteObserver enregistré"
else
    echo "   ❌ CompteObserver NON enregistré"
fi

echo ""
echo "4️⃣ CONFIGURATION QUEUE (Listener)"
echo ""

if grep -q "implements ShouldQueue" app/Listeners/SendClientNotification.php; then
    echo "   ✅ SendClientNotification implémente ShouldQueue"
    echo "   → Emails envoyés de manière ASYNCHRONE (via queue)"
else
    echo "   ⚠️  SendClientNotification N'implémente PAS ShouldQueue"
    echo "   → Emails envoyés de manière SYNCHRONE (direct)"
fi

echo ""
echo "5️⃣ CONFIGURATION SUPERVISOR (Queue Worker)"
echo ""

if grep -q "laravel-queue-worker" docker/supervisor/supervisord.conf; then
    echo "   ✅ Queue worker configuré dans Supervisor"
    
    # Extraire les paramètres
    worker_line=$(grep "command=php" docker/supervisor/supervisord.conf | grep queue:work)
    echo "   📝 Commande: ${worker_line#*command=}"
    
    if grep -q "autostart=true" docker/supervisor/supervisord.conf; then
        echo "   ✅ Auto-démarrage activé"
    fi
    
    if grep -q "autorestart=true" docker/supervisor/supervisord.conf; then
        echo "   ✅ Auto-redémarrage activé"
    fi
else
    echo "   ❌ Queue worker NON configuré"
fi

echo ""
echo "6️⃣ DISPATCH DE L'EVENT"
echo ""

if grep -q "event(new CompteCreated" app/Services/CompteService.php 2>/dev/null; then
    echo "   ✅ Event CompteCreated dispatché dans CompteService"
elif grep -q "event(new CompteCreated" app/Http/Controllers/**/*.php 2>/dev/null; then
    echo "   ✅ Event CompteCreated dispatché dans Controller"
else
    echo "   ❌ Event CompteCreated NON dispatché"
fi

echo ""
echo "=========================================="
echo "📊 RÉSUMÉ"
echo "=========================================="
echo ""

# Compter les ✅
success_count=$(grep -c "✅" <<< "$(bash $0 2>&1)" || echo 0)

echo "Éléments vérifiés: ${#files[@]} fichiers + 6 configurations"
echo ""

echo "✅ REQUIS POUR LA PRODUCTION:"
echo ""
echo "1. ✅ Event CompteCreated existe et est enregistré"
echo "2. ✅ Listener SendClientNotification implémente ShouldQueue"
echo "3. ✅ EventServiceProvider lie Event → Listener"
echo "4. ✅ Observer CompteObserver enregistré"
echo "5. ✅ Supervisor configure le queue worker"
echo "6. ✅ Event dispatché dans le service métier"
echo ""

echo "📋 VARIABLES D'ENVIRONNEMENT RENDER À VÉRIFIER:"
echo ""
echo "   MAIL_MAILER=smtp"
echo "   MAIL_HOST=smtp.gmail.com"
echo "   MAIL_PORT=587"
echo "   MAIL_USERNAME=bayebara2000@gmail.com"
echo "   MAIL_PASSWORD=[App Password]"
echo "   MAIL_ENCRYPTION=tls"
echo "   MAIL_FROM_ADDRESS=bayebara2000@gmail.com"
echo "   MAIL_FROM_NAME=\"Faysany Banque\""
echo "   QUEUE_CONNECTION=database"
echo ""

echo "🚀 COMMANDES RENDER (Shell) APRÈS DÉPLOIEMENT:"
echo ""
echo "1. Vérifier que Supervisor tourne:"
echo "   ps aux | grep supervisor"
echo ""
echo "2. Vérifier que le queue worker tourne:"
echo "   ps aux | grep 'queue:work'"
echo ""
echo "3. Tester SMTP direct:"
echo "   php test_smtp_direct.php"
echo ""
echo "4. Créer un compte et vérifier les logs:"
echo "   tail -f storage/logs/laravel.log | grep -i 'email\\|CompteCreated'"
echo ""
echo "=========================================="

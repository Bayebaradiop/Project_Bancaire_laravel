<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue chez Faysany Banque</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 30px -30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .credentials {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .credential-item {
            margin: 12px 0;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
        }
        .credential-label {
            font-weight: bold;
            color: #667eea;
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            text-transform: uppercase;
        }
        .credential-value {
            font-size: 18px;
            color: #2d3748;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-icon {
            color: #ffc107;
            font-size: 20px;
            margin-right: 10px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .security-tips {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .security-tips ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .security-tips li {
            margin: 5px 0;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏦 Faysany Banque</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px;">Bienvenue dans votre espace bancaire</p>
        </div>

        <p>Bonjour <strong>{{ $nomComplet }}</strong>,</p>

        <p>Nous sommes ravis de vous accueillir chez <strong>Faysany Banque</strong> ! Votre compte a été créé avec succès.</p>

        <div class="credentials">
            <h3 style="margin-top: 0; color: #2d3748;">🔐 Vos identifiants de connexion</h3>
            
            <div class="credential-item">
                <span class="credential-label">📧 Email</span>
                <span class="credential-value">{{ $email }}</span>
            </div>

            <div class="credential-item">
                <span class="credential-label">🔑 Mot de passe</span>
                <span class="credential-value">{{ $password }}</span>
            </div>

            <div class="credential-item">
                <span class="credential-label">🔢 Code de sécurité</span>
                <span class="credential-value">{{ $code }}</span>
            </div>

            <div class="credential-item">
                <span class="credential-label">💳 Numéro de compte</span>
                <span class="credential-value">{{ $numeroCompte }}</span>
            </div>
        </div>

        <div class="warning">
            <strong><span class="warning-icon">⚠️</span>Important !</strong>
            <p style="margin: 10px 0 0 0;">
                Pour votre sécurité, nous vous recommandons fortement de <strong>changer votre mot de passe</strong> 
                lors de votre première connexion.
            </p>
        </div>

        <div class="security-tips">
            <h4 style="margin-top: 0; color: #004085;">🛡️ Conseils de sécurité</h4>
            <ul>
                <li>Ne partagez jamais vos identifiants avec qui que ce soit</li>
                <li>Utilisez un mot de passe unique et complexe</li>
                <li>Déconnectez-vous toujours après chaque session</li>
                <li>Vérifiez régulièrement vos transactions</li>
                <li>Contactez-nous immédiatement en cas d'activité suspecte</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <p>Prêt à commencer ?</p>
            <a href="{{ config('app.url') }}/login" class="btn">Accéder à mon compte</a>
        </div>

        <div class="footer">
            <p><strong>Faysany Banque</strong></p>
            <p>Service Client disponible 24/7</p>
            <p style="font-size: 12px; margin-top: 15px;">
                Cet email contient des informations confidentielles. Si vous l'avez reçu par erreur, 
                veuillez le supprimer immédiatement et nous en informer.
            </p>
        </div>
    </div>
</body>
</html>

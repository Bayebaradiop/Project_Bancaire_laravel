<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur Faysany Banque</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h2 {
            margin-top: 0;
            color: #667eea;
            font-size: 18px;
        }
        .credentials {
            background: #fff;
            border: 2px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .credentials p {
            margin: 10px 0;
            font-size: 16px;
        }
        .credentials strong {
            color: #667eea;
            font-size: 18px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏦 Bienvenue sur Faysany Banque</h1>
            <p>Votre compte a été créé avec succès !</p>
        </div>
        
        <div class="content">
            <p>Bonjour <strong>{{ $compte->client->titulaire }}</strong>,</p>
            
            <p>Nous sommes ravis de vous accueillir chez Faysany Banque. Votre compte bancaire a été créé avec succès.</p>
            
            <div class="info-box">
                <h2>📋 Informations de votre compte</h2>
                <p><strong>Numéro de compte :</strong> {{ $compte->numeroCompte }}</p>
                <p><strong>Type de compte :</strong> {{ ucfirst($compte->type) }}</p>
                <p><strong>Devise :</strong> {{ $compte->devise }}</p>
                <p><strong>Solde initial :</strong> {{ $compte->solde }} {{ $compte->devise }}</p>
                <p><strong>Date de création :</strong> {{ $compte->dateCreation->format('d/m/Y à H:i') }}</p>
            </div>
            
            <div class="credentials">
                <h2 style="color: #667eea; margin-top: 0;">🔐 Vos identifiants de connexion</h2>
                <p><strong>Email :</strong> {{ $compte->client->email }}</p>
                <p><strong>Mot de passe :</strong> <span style="font-family: monospace; background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-size: 20px;">{{ $password }}</span></p>
                @if($code)
                <p><strong>🔑 Code de première connexion :</strong> <span style="font-family: monospace; background: #fff3cd; padding: 8px 15px; border-radius: 4px; font-size: 24px; color: #856404; font-weight: bold; letter-spacing: 2px;">{{ $code }}</span></p>
                @endif
            </div>
            
            <div class="warning">
                <p><strong>⚠️ Important :</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Conservez ce mot de passe en lieu sûr</li>
                    <li>Ne le partagez avec personne</li>
                    <li>Changez-le lors de votre première connexion</li>
                    <li>Activez l'authentification à deux facteurs pour plus de sécurité</li>
                </ul>
            </div>
            
            <p style="text-align: center;">
                <a href="{{ config('app.url') }}/login" class="button">Se connecter maintenant</a>
            </p>
            
            <p>Si vous avez des questions, n'hésitez pas à contacter notre service client.</p>
            
            <p>Cordialement,<br>
            <strong>L'équipe Faysany Banque</strong></p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Faysany Banque. Tous droits réservés.</p>
            <p>Cet email a été envoyé à {{ $compte->client->email }}</p>
            <p style="font-size: 12px; color: #999;">
                Si vous n'êtes pas à l'origine de cette demande, veuillez contacter immédiatement notre service client.
            </p>
        </div>
    </div>
</body>
</html>

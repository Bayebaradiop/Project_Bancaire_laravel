<?php

namespace App\Http\Controllers;

use App\Services\BrevoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestEmailController extends Controller
{
    /**
     * Test simple d'envoi d'email
     */
    public function testEmail(Request $request)
    {
        try {
            $to = $request->input('email', 'maildedjigo@gmail.com');
            
            Log::info('Test email début', ['to' => $to]);
            
            // Utiliser l'API Brevo si BREVO_API_KEY est défini
            if (env('BREVO_API_KEY')) {
                Log::info('Utilisation de l\'API Brevo pour le test');
                
                $brevoService = new BrevoApiService();
                $result = $brevoService->sendEmail(
                    $to,
                    'Test Email Faysany Banque',
                    '<h1>Test Email</h1><p>Ceci est un email de test depuis Faysany Banque via l\'API Brevo. Si vous recevez ceci, la configuration email fonctionne!</p>'
                );

                if (!$result['success']) {
                    throw new \Exception($result['error']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Email de test envoyé via API Brevo',
                    'destination' => $to,
                    'messageId' => $result['messageId'],
                    'method' => 'API Brevo'
                ]);
            } else {
                Log::info('Utilisation de SMTP pour le test');
                
                // Test simple avec Mail::raw (SMTP)
                Mail::raw('Ceci est un email de test depuis Faysany Banque. Si vous recevez ceci, la configuration email fonctionne!', function ($message) use ($to) {
                    $message->to($to)
                            ->subject('Test Email Faysany Banque')
                            ->from(config('mail.from.address'), config('mail.from.name'));
                });
                
                Log::info('Test email envoyé via SMTP', ['to' => $to]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Email de test envoyé via SMTP',
                    'destination' => $to,
                    'config' => [
                        'mailer' => config('mail.default'),
                        'host' => config('mail.mailers.smtp.host'),
                        'port' => config('mail.mailers.smtp.port'),
                        'from' => config('mail.from.address'),
                    ],
                    'method' => 'SMTP'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur test email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

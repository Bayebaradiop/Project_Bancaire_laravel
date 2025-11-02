<?php

namespace App\Http\Controllers;

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
            
            // Test simple avec Mail::raw
            Mail::raw('Ceci est un email de test depuis Faysany Banque. Si vous recevez ceci, la configuration email fonctionne!', function ($message) use ($to) {
                $message->to($to)
                        ->subject('Test Email Faysany Banque')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            Log::info('Test email envoyé', ['to' => $to]);
            
            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé',
                'destination' => $to,
                'config' => [
                    'mailer' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'from' => config('mail.from.address'),
                ]
            ]);
            
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

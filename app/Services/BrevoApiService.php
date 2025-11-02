<?php

namespace App\Services;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BrevoApiService
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
        $this->apiInstance = new TransactionalEmailsApi(
            new Client(),
            $config
        );
    }

    public function sendEmail($to, $subject, $htmlContent, $from = null)
    {
        try {
            $sendSmtpEmail = new SendSmtpEmail([
                'subject' => $subject,
                'sender' => [
                    'name' => $from['name'] ?? env('MAIL_FROM_NAME', 'Faysany Banque'),
                    'email' => $from['email'] ?? env('MAIL_FROM_ADDRESS', 'bayebara2000@gmail.com')
                ],
                'to' => [
                    ['email' => $to]
                ],
                'htmlContent' => $htmlContent
            ]);

            $result = $this->apiInstance->sendTransacEmail($sendSmtpEmail);
            
            Log::info('✅ Email envoyé via API Brevo', [
                'to' => $to,
                'subject' => $subject,
                'messageId' => $result->getMessageId()
            ]);

            return [
                'success' => true,
                'messageId' => $result->getMessageId()
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi email API Brevo', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

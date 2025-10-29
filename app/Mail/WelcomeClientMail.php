<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nomComplet;
    public string $email;
    public string $password;
    public string $code;
    public string $numeroCompte;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $nomComplet,
        string $email,
        string $password,
        string $code,
        string $numeroCompte
    ) {
        $this->nomComplet = $nomComplet;
        $this->email = $email;
        $this->password = $password;
        $this->code = $code;
        $this->numeroCompte = $numeroCompte;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue chez Faysany Banque - Vos identifiants',
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
            replyTo: [
                new \Illuminate\Mail\Mailables\Address(
                    config('mail.from.address'),
                    config('mail.from.name')
                ),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-client',
            with: [
                'nomComplet' => $this->nomComplet,
                'email' => $this->email,
                'password' => $this->password,
                'code' => $this->code,
                'numeroCompte' => $this->numeroCompte,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Build the message with anti-spam headers.
     */
    public function build()
    {
        return $this->withSymfonyMessage(function ($message) {
            $headers = $message->getHeaders();
            
            // Ajouter des en-têtes anti-spam
            $headers->addTextHeader('X-Mailer', 'Faysany Banque');
            $headers->addTextHeader('X-Priority', '1 (Highest)');
            $headers->addTextHeader('X-MSMail-Priority', 'High');
            $headers->addTextHeader('Importance', 'High');
            
            // Catégorie SendGrid pour les statistiques
            $headers->addTextHeader('X-SMTPAPI', json_encode([
                'category' => ['welcome_email', 'new_account'],
            ]));
        });
    }
}

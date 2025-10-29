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
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-client',
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
}

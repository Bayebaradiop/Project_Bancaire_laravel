<?php

namespace App\Jobs;

use App\Mail\WelcomeClientMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Le nombre de fois que le job peut être tenté.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Le nombre de secondes avant que le job ne soit timeout.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * Données pour l'email
     */
    protected string $nomComplet;
    protected string $email;
    protected string $password;
    protected string $code;
    protected string $numeroCompte;

    /**
     * Create a new job instance.
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
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(
                new WelcomeClientMail(
                    $this->nomComplet,
                    $this->email,
                    $this->password,
                    $this->code,
                    $this->numeroCompte
                )
            );

            Log::info('✅ Email de bienvenue envoyé avec succès (Job)', [
                'compte' => $this->numeroCompte,
                'email' => $this->email,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de l\'envoi de l\'email de bienvenue (Job)', [
                'compte' => $this->numeroCompte,
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Relancer l'exception pour que Laravel puisse réessayer
            throw $e;
        }
    }

    /**
     * Gérer l'échec du job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ Échec définitif de l\'envoi de l\'email de bienvenue après ' . $this->tries . ' tentatives', [
            'compte' => $this->numeroCompte,
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Compte;

class CompteNumeroService
{
    /**
     * Générer un numéro de compte unique.
     * Format: CPXXXXXXXXXX (CP + 10 chiffres aléatoires)
     *
     * @return string
     */
    public function genererNumero(): string
    {
        do {
            // Format: CP + 10 chiffres aléatoires
            $numero = 'CP' . str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (Compte::where('numeroCompte', $numero)->exists());

        return $numero;
    }
}

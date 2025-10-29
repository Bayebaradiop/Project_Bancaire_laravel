<?php

namespace App\Services;

use App\Models\Compte;

class NumeroCompteService
{
    /**
     * Génère un numéro de compte unique
     * Format: CPxxxxxxxxxx (CP + 10 chiffres aléatoires)
     *
     * @return string
     */
    public function generer(): string
    {
        do {
            $numero = 'CP' . $this->genererChiffres(10);
        } while ($this->existe($numero));

        return $numero;
    }

    /**
     * Génère une séquence de chiffres aléatoires
     *
     * @param int $longueur
     * @return string
     */
    private function genererChiffres(int $longueur): string
    {
        $chiffres = '';
        for ($i = 0; $i < $longueur; $i++) {
            $chiffres .= random_int(0, 9);
        }
        return $chiffres;
    }

    /**
     * Vérifie si un numéro de compte existe déjà
     *
     * @param string $numero
     * @return bool
     */
    private function existe(string $numero): bool
    {
        return Compte::where('numeroCompte', $numero)->exists();
    }
}

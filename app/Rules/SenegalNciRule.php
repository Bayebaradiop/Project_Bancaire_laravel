<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalNciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Format NCI Sénégal: 13 chiffres (1YYMMDDSSSSSSC)
        // 1: type de document (1 pour NCI)
        // YY: année de naissance
        // MM: mois de naissance
        // DD: jour de naissance
        // SSSSS: numéro de série
        // C: clé de contrôle
        $pattern = '/^1\d{12}$/';
        
        if (!preg_match($pattern, $value)) {
            $fail('Le :attribute doit être un numéro de carte d\'identité nationale (NCI) sénégalais valide (13 chiffres commençant par 1).');
        }
        
        // Validation supplémentaire: la date doit être cohérente
        $year = substr($value, 1, 2);
        $month = substr($value, 3, 2);
        $day = substr($value, 5, 2);
        
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            $fail('Le :attribute contient une date invalide.');
        }
    }
}

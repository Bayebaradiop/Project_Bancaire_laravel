<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalPhoneRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Format accepté: +221XXXXXXXXX ou 221XXXXXXXXX
        // Opérateurs mobiles Sénégal: 77, 78, 76, 70, 75
        $pattern = '/^(\+221|221)?(77|78|76|70|75)\d{7}$/';
        
        if (!preg_match($pattern, $value)) {
            $fail('Le :attribute doit être un numéro de téléphone portable sénégalais valide (format: +221XXXXXXXXX avec préfixe 77, 78, 76, 70 ou 75).');
        }
    }
}

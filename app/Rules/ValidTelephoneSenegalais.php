<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidTelephoneSenegalais implements Rule
{
    public function passes($attribute, $value)
    {
        // Format : +221XXXXXXXXX (9 chiffres après +221)
        // Opérateurs: 70, 75, 76, 77, 78 (Orange, Free, Expresso)
        return preg_match('/^\+221(70|75|76|77|78)\d{7}$/', $value);
    }

    public function message()
    {
        return 'Le :attribute doit être un numéro de téléphone sénégalais valide (format: +221XXXXXXXXX).';
    }
}

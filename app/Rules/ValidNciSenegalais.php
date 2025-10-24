<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidNciSenegalais implements Rule
{
    public function passes($attribute, $value)
    {
        // NCI Sénégalais : 13 chiffres (1 pour sexe + 12 pour identifiant)
        return preg_match('/^[12]\d{12}$/', $value);
    }

    public function message()
    {
        return 'Le :attribute doit être un numéro NCI sénégalais valide (13 chiffres commençant par 1 ou 2).';
    }
}

<?php

namespace App\Observers;

use App\Models\Compte;
use App\Events\CompteCreated;

class CompteObserver
{
    public function created(Compte $compte)
    {
        // Récupérer le password et le code depuis la session ou les attributs temporaires
        $password = session('temp_client_password');
        $code = session('temp_client_code');

        if ($password && $code) {
            event(new CompteCreated($compte, $password, $code));
            
            // Nettoyer la session
            session()->forget(['temp_client_password', 'temp_client_code']);
        }
    }
}

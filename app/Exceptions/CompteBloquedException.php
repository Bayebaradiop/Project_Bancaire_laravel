<?php

namespace App\Exceptions;

use Exception;

class CompteBloquedException extends Exception
{
    protected $message = 'Compte bloqué';
    protected $code = 403;

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->message,
        ], $this->code);
    }
}

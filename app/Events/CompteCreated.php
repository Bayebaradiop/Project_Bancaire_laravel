<?php

namespace App\Events;

use App\Models\Compte;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $compte;
    public $password;
    public $code;

    public function __construct(Compte $compte, $password, $code)
    {
        $this->compte = $compte;
        $this->password = $password;
        $this->code = $code;
    }
}

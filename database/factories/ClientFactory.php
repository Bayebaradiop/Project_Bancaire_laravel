<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        
        return [
            'user_id' => $user->id,
            'titulaire' => $user->nomComplet,
            'nci' => $user->nci,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'adresse' => $user->adresse,
        ];
    }
}

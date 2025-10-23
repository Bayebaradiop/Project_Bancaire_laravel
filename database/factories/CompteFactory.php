<?php

namespace Database\Factories;

use App\Models\Compte;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Compte::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'type' => $this->faker->randomElement(['cheque', 'epargne']),
            'devise' => 'FCFA',
            'statut' => 'actif',
            'motifBlocage' => null,
            'version' => 1,
        ];
    }

    /**
     * Indicate that the compte is blocked.
     *
     * @return static
     */
    public function bloque(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'bloque',
            'motifBlocage' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the compte is closed.
     *
     * @return static
     */
    public function ferme(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'ferme',
            'motifBlocage' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the compte is a checking account.
     *
     * @return static
     */
    public function cheque(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'cheque',
        ]);
    }

    /**
     * Indicate that the compte is a savings account.
     *
     * @return static
     */
    public function epargne(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'epargne',
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position_id' => Position::factory(),
            'nama' => $this->faker->name(),
            'nomor_urut' => $this->faker->unique()->numberBetween(1, 20),
        ];
    }
}

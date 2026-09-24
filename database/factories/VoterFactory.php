<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\Voter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voter>
 */
class VoterFactory extends Factory
{
    protected $model = Voter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nik = (string) $this->faker->unique()->numerify('################');

        return [
            'election_id' => Election::factory(),
            'nik_hash' => hash('sha256', $nik),
            'nik_encrypted' => $nik,
            'nama' => $this->faker->name(),
            'alamat' => $this->faker->address(),
            'status_verifikasi' => 'terverifikasi',
        ];
    }
}

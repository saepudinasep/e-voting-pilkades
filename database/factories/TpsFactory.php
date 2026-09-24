<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\Tps;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tps>
 */
class TpsFactory extends Factory
{
    protected $model = Tps::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'nama_lokasi' => 'Balai Desa ' . $this->faker->word(),
            'kode_tps' => 'TPS-' . Str::upper(Str::random(6)),
            'status_koneksi' => 'offline',
        ];
    }
}

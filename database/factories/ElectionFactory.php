<?php

namespace Database\Factories;

use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Election>
 */
class ElectionFactory extends Factory
{
    protected $model = Election::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Pemilihan Kepala Desa ' . $this->faker->city(),
            'wilayah' => 'Desa ' . $this->faker->citySuffix(),
            'status' => 'berjalan',
            'tanggal_mulai' => now()->subHour(),
            'tanggal_selesai' => now()->addHours(6),
        ];
    }
}

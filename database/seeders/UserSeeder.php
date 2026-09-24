<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@evoting.test'],
            [
                'name' => 'Admin Pemilihan',
                'password' => 'password', // otomatis di-hash lewat cast 'hashed' di model User
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Petugas dibuat TANPA tps_id (null) karena TPS baru ada setelah admin
        // membuat pemilihan + TPS lewat panel admin (Tahap 3). Assign tps_id-nya
        // belakangan lewat tinker atau UI, sesuaikan jumlah dengan TPS yang kamu buat:
        //
        //   $u = \App\Models\User::where('email', 'petugas1@evoting.test')->first();
        //   $u->update(['tps_id' => 1]);
        foreach (range(1, 3) as $i) {
            User::updateOrCreate(
                ['email' => "petugas{$i}@evoting.test"],
                [
                    'name' => "Petugas TPS {$i}",
                    'password' => 'password',
                    'role' => 'petugas_tps',
                    'tps_id' => null,
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('User seeded: admin@evoting.test, petugas1-3@evoting.test (password: "password")');
        $this->command->warn('Ingat: assign tps_id tiap petugas manual setelah TPS dibuat di panel admin.');
    }
}

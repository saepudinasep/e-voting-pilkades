<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Tps;
use App\Models\User;
use App\Models\Vote;
use App\Models\Voter;
use App\Models\VoterToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VotingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function siapkanPemilihan(): array
    {
        // User ini yang dicatat sebagai "dibuat_oleh" saat generate token —
        // WAJIB dibuat dulu di sini karena database test selalu kosong di awal
        // (RefreshDatabase reset total tiap test, bukan seperti database dev/production
        // yang datanya menetap).
        $petugas = User::factory()->create(['role' => 'petugas_tps']);

        $election = Election::factory()->create();
        $position = Position::factory()->for($election)->create();
        $kandidatA = Candidate::factory()->for($position)->create(['nomor_urut' => 1]);
        $kandidatB = Candidate::factory()->for($position)->create(['nomor_urut' => 2]);
        $tps = Tps::factory()->for($election)->create();
        $voter = Voter::factory()->for($election)->create();

        return compact('election', 'position', 'kandidatA', 'kandidatB', 'tps', 'voter', 'petugas');
    }

    /** Token yang sudah dipakai tidak boleh bisa dipakai untuk vote kedua kalinya. */
    public function test_token_yang_sudah_terpakai_ditolak(): void
    {
        $s = $this->siapkanPemilihan();

        $hasil = VoterToken::generateFor($s['voter'], $s['tps'], $s['petugas']->id);
        $plainToken = $hasil['plain_token'];

        // Vote pertama — harus berhasil
        $this->withSession([])
            ->post(route('bilik.masuk.submit', $s['tps']->kode_tps), ['token' => $plainToken])
            ->assertRedirect(route('bilik.pilih', $s['tps']->kode_tps));

        $this->assertDatabaseHas('voter_tokens', [
            'id' => $hasil['token_model']->id,
            'status' => 'aktif', // belum berubah sampai submit vote
        ]);

        // Coba masuk lagi dengan token yang SAMA setelah token ditandai terpakai secara manual
        // (mensimulasikan token sudah dipakai vote sebelumnya)
        $hasil['token_model']->update(['status' => 'terpakai']);

        $response = $this->post(route('bilik.masuk.submit', $s['tps']->kode_tps), ['token' => $plainToken]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('votes', 0);
    }

    /** Token yang sudah kedaluwarsa tidak boleh bisa dipakai. */
    public function test_token_kedaluwarsa_ditolak(): void
    {
        $s = $this->siapkanPemilihan();

        $hasil = VoterToken::generateFor($s['voter'], $s['tps'], $s['petugas']->id);
        $hasil['token_model']->update(['kedaluwarsa_pada' => now()->subMinute()]);

        $response = $this->post(route('bilik.masuk.submit', $s['tps']->kode_tps), [
            'token' => $hasil['plain_token'],
        ]);

        $response->assertSessionHas('error');
    }

    /** Token dari TPS lain tidak boleh dipakai di TPS ini (cegah "pindah TPS" curang). */
    public function test_token_dari_tps_lain_ditolak(): void
    {
        $s = $this->siapkanPemilihan();
        $tpsLain = Tps::factory()->for($s['election'])->create();

        $hasil = VoterToken::generateFor($s['voter'], $s['tps'], $s['petugas']->id);

        // Coba masuk ke bilik suara TPS LAIN pakai token yang digenerate untuk TPS asal
        $response = $this->post(route('bilik.masuk.submit', $tpsLain->kode_tps), [
            'token' => $hasil['plain_token'],
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * Ini pengujian paling penting: submit vote 2x dengan token yang sama secara
     * BERSAMAAN (mensimulasikan race condition / double-click / replay request)
     * hanya boleh menghasilkan SATU set suara, bukan dua.
     */
    public function test_submit_ganda_dengan_token_sama_tidak_menghasilkan_suara_ganda(): void
    {
        $s = $this->siapkanPemilihan();
        $hasil = VoterToken::generateFor($s['voter'], $s['tps'], $s['petugas']->id);

        // Masuk dulu supaya session berisi token yang valid
        $this->post(route('bilik.masuk.submit', $s['tps']->kode_tps), ['token' => $hasil['plain_token']]);

        $payload = [
            'pilihan' => [
                ['position_id' => $s['position']->id, 'candidate_id' => $s['kandidatA']->id],
            ],
        ];

        // Submit pertama — harus berhasil
        $this->post(route('bilik.submit', $s['tps']->kode_tps), $payload);

        $this->assertDatabaseCount('votes', 1);
        $this->assertDatabaseHas('voter_tokens', [
            'id' => $hasil['token_model']->id,
            'status' => 'terpakai',
        ]);

        // Manipulasi manual: kembalikan token_id ke session (mensimulasikan request
        // kedua yang masih membawa session lama / replay), lalu submit lagi
        session(['bilik_token_id_' . $s['tps']->id => $hasil['token_model']->id]);

        $response = $this->post(route('bilik.submit', $s['tps']->kode_tps), [
            'pilihan' => [
                ['position_id' => $s['position']->id, 'candidate_id' => $s['kandidatB']->id],
            ],
        ]);

        // Submit kedua harus ditolak — token sudah 'terpakai'
        $response->assertRedirect(route('bilik.masuk', $s['tps']->kode_tps));
        $this->assertDatabaseCount('votes', 1); // TETAP 1, bukan 2
    }

    /** Suara di tabel votes tidak boleh bisa ditelusuri balik ke voter_id manapun. */
    public function test_tabel_votes_tidak_punya_kolom_voter_id(): void
    {
        $s = $this->siapkanPemilihan();
        $hasil = VoterToken::generateFor($s['voter'], $s['tps'], $s['petugas']->id);

        $this->post(route('bilik.masuk.submit', $s['tps']->kode_tps), ['token' => $hasil['plain_token']]);
        $this->post(route('bilik.submit', $s['tps']->kode_tps), [
            'pilihan' => [
                ['position_id' => $s['position']->id, 'candidate_id' => $s['kandidatA']->id],
            ],
        ]);

        $vote = Vote::first();

        $this->assertArrayNotHasKey('voter_id', $vote->getAttributes());
    }
}

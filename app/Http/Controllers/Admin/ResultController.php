<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    public function show(Election $election): Response
    {
        $positions = $election->positions()
            ->with(['candidates' => function ($q) {
                $q->withCount('votes')->orderBy('nomor_urut');
            }])
            ->orderBy('urutan')
            ->get()
            ->map(function ($position) {
                $totalSuara = $position->candidates->sum('votes_count');

                return [
                    'id' => $position->id,
                    'nama' => $position->nama,
                    'total_suara' => $totalSuara,
                    'kandidat' => $position->candidates->map(fn($c) => [
                        'id' => $c->id,
                        'nama' => $c->nama,
                        'nomor_urut' => $c->nomor_urut,
                        'foto' => $c->foto,
                        'suara' => $c->votes_count,
                        'persentase' => $totalSuara > 0 ? round($c->votes_count / $totalSuara * 100, 1) : 0,
                    ]),
                ];
            });

        $jumlahTpsSudahLapor = $election->tpsList()
            ->whereHas('votes')
            ->count();

        return Inertia::render('Admin/Results/Show', [
            'election' => $election,
            'positions' => $positions,
            'ringkasanTps' => [
                'total' => $election->tpsList()->count(),
                'sudah_lapor' => $jumlahTpsSudahLapor,
            ],
        ]);
    }

    /**
     * Endpoint ringan untuk polling berkala dari frontend (mis. tiap 10 detik),
     * supaya tidak perlu reload seluruh halaman Inertia untuk data yang sering berubah.
     */
    public function poll(Election $election)
    {
        $positions = $election->positions()
            ->with(['candidates' => fn($q) => $q->withCount('votes')])
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'kandidat' => $p->candidates->map(fn($c) => [
                    'id' => $c->id,
                    'suara' => $c->votes_count,
                ]),
            ]);

        return response()->json(['positions' => $positions]);
    }
}

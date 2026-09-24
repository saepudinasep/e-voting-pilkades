<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $aksi = $request->query('aksi');
        $userId = $request->query('user_id');

        $logs = AuditLog::with('user:id,name,role')
            ->when($aksi, fn($q) => $q->where('aksi', $aksi))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('waktu')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filterAksi' => $aksi,
            'filterUserId' => $userId,
            'daftarAksi' => AuditLog::select('aksi')->distinct()->orderBy('aksi')->pluck('aksi'),
            'daftarUser' => \App\Models\User::whereIn('id', AuditLog::select('user_id')->distinct())
                ->get(['id', 'name']),
        ]);
    }
}

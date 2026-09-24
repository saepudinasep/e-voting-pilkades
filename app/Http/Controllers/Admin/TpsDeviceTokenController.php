<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tps;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpsDeviceTokenController extends Controller
{
    public function generateToken(Tps $tpsLokasi): Response
    {
        $plainToken = $tpsLokasi->generateDeviceToken();

        AuditLog::catat('generate_device_token', 'tps', $tpsLokasi->id);

        // Ditampilkan HANYA SEKALI di sini — setelah ini plaintext-nya tidak bisa
        // dilihat lagi dari database (Sanctum cuma simpan hash-nya).
        return Inertia::render('Admin/Tps/DeviceToken', [
            'tps' => $tpsLokasi,
            'plainToken' => $plainToken,
        ]);
    }
}

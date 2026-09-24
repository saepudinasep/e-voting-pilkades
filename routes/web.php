<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\PetugasController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\TpsController;
use App\Http\Controllers\Admin\TpsDeviceTokenController;
use App\Http\Controllers\Admin\VoterController;
use App\Http\Controllers\BilikSuaraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tps\CheckinController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Dashboard — sekarang jadi "router" berdasarkan role, bukan halaman statis
|--------------------------------------------------------------------------
| Breeze selalu redirect ke route('dashboard') setelah login (lihat
| AuthenticatedSessionController::store). Daripada ubah controller bawaan
| Breeze itu, lebih simpel: biarkan dia tetap redirect ke sini, lalu di
| SINI kita yang lempar ke halaman yang sesuai role. Satu titik kontrol,
| gampang ditambah role baru nanti.
*/
Route::get('/dashboard', function () {
    $user = Auth::user();

    return match ($user->role) {
        'admin' => redirect()->route('admin.elections.index'),
        'petugas_tps' => redirect()->route('tps.checkin'),
        default => Inertia::render('Dashboard'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('elections', ElectionController::class);
        Route::resource('elections.positions', PositionController::class)
            ->shallow();
        Route::resource('positions.candidates', CandidateController::class)
            ->shallow();
        Route::resource('elections.tps', TpsController::class)
            ->shallow()
            ->parameters(['tps' => 'tpsLokasi']);

        Route::get('elections/{election}/voters', [VoterController::class, 'index'])
            ->name('elections.voters.index');
        Route::post('elections/{election}/voters/import', [VoterController::class, 'import'])
            ->name('elections.voters.import');
        Route::patch('voters/{voter}/verify', [VoterController::class, 'verify'])
            ->name('voters.verify');
        Route::patch('voters/{voter}/reject', [VoterController::class, 'reject'])
            ->name('voters.reject');
        Route::patch('voters/{voter}/assign-tps', [VoterController::class, 'assignTps'])
            ->name('voters.assign-tps');
        Route::delete('voters/{voter}', [VoterController::class, 'destroy'])
            ->name('voters.destroy');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('elections/{election}/results', [ResultController::class, 'show'])->name('elections.results');
        Route::get('elections/{election}/results/poll', [ResultController::class, 'poll'])->name('elections.results.poll');

        // FIX: dipindah ke sini dari dalam grup 'bilik' — ini aksi admin
        // (generate token device Electron), bukan sesuatu yang boleh publik.
        Route::post('tps/{tpsLokasi}/device-token', [TpsDeviceTokenController::class, 'generateToken'])
            ->name('tps.device-token');

        Route::get('petugas', [PetugasController::class, 'index'])->name('petugas.index');
        Route::post('petugas', [PetugasController::class, 'store'])->name('petugas.store');
        Route::patch('petugas/{petugas}', [PetugasController::class, 'update'])->name('petugas.update');
        Route::delete('petugas/{petugas}', [PetugasController::class, 'destroy'])->name('petugas.destroy');
    });

/*
|--------------------------------------------------------------------------
| Petugas TPS (check-in)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'petugas'])
    ->prefix('tps')
    ->name('tps.')
    ->group(function () {
        Route::get('checkin', [CheckinController::class, 'index'])->name('checkin');

        Route::middleware('throttle:20,1')->group(function () {
            Route::post('checkin/{voter}/token', [CheckinController::class, 'generateToken'])->name('checkin.token');
        });
    });

/*
|--------------------------------------------------------------------------
| Bilik Suara Digital — publik, tanpa auth, dibatasi oleh token QR
|--------------------------------------------------------------------------
*/
Route::prefix('bilik/{kodeTps}')
    ->name('bilik.')
    ->group(function () {
        Route::get('/', [BilikSuaraController::class, 'tokenEntry'])->name('masuk');

        Route::middleware('throttle:6,1')->group(function () {
            Route::post('/masuk', [BilikSuaraController::class, 'masuk'])->name('masuk.submit');
        });

        Route::get('/pilih', [BilikSuaraController::class, 'pilih'])->name('pilih');

        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/submit', [BilikSuaraController::class, 'submit'])->name('submit');
        });

        // FIX: route device-token DIHAPUS dari sini — sudah dipindah ke grup admin di atas.
    });

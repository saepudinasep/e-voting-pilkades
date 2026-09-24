<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\TpsController;
use App\Http\Controllers\Admin\VoterController;
use App\Http\Controllers\BilikSuaraController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tps\CheckinController;
use Illuminate\Foundation\Application;
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

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('elections', ElectionController::class);
        Route::resource('elections.positions', PositionController::class)
            ->shallow(); // /admin/positions/{position} untuk show/edit/update/destroy
        Route::resource('positions.candidates', CandidateController::class)
            ->shallow();
        Route::resource('elections.tps', TpsController::class)
            ->shallow()
            ->parameters(['tps' => 'tpsLokasi']); // hindari bentrok nama 'tps' sbg kata jamak
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
    });

Route::middleware(['auth', 'verified', 'petugas'])
    ->prefix('tps')
    ->name('tps.')
    ->group(function () {
        Route::get('checkin', [CheckinController::class, 'index'])->name('checkin');

        // Batasi pencarian NIK — cegah petugas (atau akun yang dibobol) melakukan
        // scan/brute-force NIK satu-satu ke seluruh DPT dalam waktu singkat.
        Route::middleware('throttle:20,1')->group(function () {
            Route::post('checkin/{voter}/token', [CheckinController::class, 'generateToken'])->name('checkin.token');
        });
    });

Route::prefix('bilik/{kodeTps}')
    ->name('bilik.')
    ->group(function () {
        Route::get('/', [BilikSuaraController::class, 'tokenEntry'])->name('masuk');

        // Ini yang PALING penting dibatasi: token acak 32 karakter praktis mustahil
        // ditebak, TAPI tanpa rate limit, seseorang tetap bisa mencoba ribuan tebakan
        // per menit lewat script. throttle:6,1 (6 percobaan/menit per IP) menutup celah ini.
        Route::middleware('throttle:6,1')->group(function () {
            Route::post('/masuk', [BilikSuaraController::class, 'masuk'])->name('masuk.submit');
        });

        Route::get('/pilih', [BilikSuaraController::class, 'pilih'])->name('pilih');

        // Submit vote juga dibatasi — bukan untuk brute force (sudah dijaga token+lock),
        // tapi supaya request berulang otomatis (mis. dari bot/script) tidak membanjiri server.
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/submit', [BilikSuaraController::class, 'submit'])->name('submit');
        });
    });

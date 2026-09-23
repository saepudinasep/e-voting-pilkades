<?php

use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\TpsController;
use App\Http\Controllers\Admin\VoterController;
use App\Http\Controllers\ProfileController;
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
    });

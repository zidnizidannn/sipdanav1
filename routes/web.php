<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PriceCapController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Middleware\RedirectAdminToDashboard;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    // Route yang tetap bisa diakses Admin
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/dev/switch-role', [AuthController::class, 'switchRole'])->name('dev.switch-role');

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/price-caps', [PriceCapController::class, 'index'])->name('price-caps.index');
    Route::post('/price-caps', [PriceCapController::class, 'store'])->name('price-caps.store');
    Route::put('/price-caps/{priceCap}', [PriceCapController::class, 'update'])->name('price-caps.update');
    Route::delete('/price-caps/{priceCap}', [PriceCapController::class, 'destroy'])->name('price-caps.destroy');

    // Route Non-Admin: jika Admin mengakses URL di bawah ini, otomatis dialihkan ke /admin/dashboard
    Route::middleware(RedirectAdminToDashboard::class)->group(function () {
        Route::get('/', fn () => redirect()->route('proposals.index'));

        Route::post('/yayasan/toggle-mode', [AuthController::class, 'toggleYayasanMode'])->name('yayasan.toggle-mode');
        Route::get('/notifications/poll', [NotificationController::class, 'poll'])->name('notifications.poll');

        Route::get('/proposals', [ProposalController::class, 'index'])->name('proposals.index');
        Route::get('/proposals/create', [ProposalController::class, 'create'])->name('proposals.create');
        Route::post('/proposals', [ProposalController::class, 'store'])->name('proposals.store');
        Route::get('/proposals/{proposal}', [ProposalController::class, 'show'])->name('proposals.show');
        Route::get('/proposals/{proposal}/edit', [ProposalController::class, 'edit'])->name('proposals.edit');
        Route::put('/proposals/{proposal}/resubmit', [ProposalController::class, 'resubmit'])->name('proposals.resubmit');

        Route::post('/proposals/{proposal}/examine', [ProposalController::class, 'examine'])->name('proposals.examine');
        Route::post('/proposals/{proposal}/approve', [ProposalController::class, 'approve'])->name('proposals.approve');
        Route::post('/proposals/{proposal}/escalate', [ProposalController::class, 'escalate'])->name('proposals.escalate');
        Route::post('/proposals/{proposal}/disburse', [ProposalController::class, 'disburse'])->name('proposals.disburse');
    });
});
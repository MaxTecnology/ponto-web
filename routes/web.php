<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Rh\ExportController;
use App\Livewire\Admin\UserRoles;
use App\Livewire\Ponto\BaterPonto;
use App\Livewire\Ponto\MeuEspelho;
use App\Livewire\Rh\Ajustes;
use App\Livewire\Rh\Dashboard;
use App\Livewire\Rh\Fechamento;
use App\Livewire\Account\Profile as ProfilePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ponto');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/ponto', BaterPonto::class)->name('ponto.index');
    Route::get('/meu-espelho', MeuEspelho::class)->name('ponto.espelho');
    Route::get('/perfil', ProfilePage::class)->name('perfil');

    Route::middleware('can:view-rh')->group(function (): void {
        Route::get('/rh/ponto', Dashboard::class)->name('rh.dashboard');
        Route::get('/rh/ajustes', Ajustes::class)->name('rh.ajustes');
        Route::get('/rh/fechamento', Fechamento::class)->name('rh.fechamento');
        Route::get('/rh/export', ExportController::class)->name('rh.export');
    });

    Route::middleware('can:manage-roles')->group(function (): void {
        Route::get('/admin/users', UserRoles::class)->name('admin.users');
        Route::view('/admin/style-guide', 'style-guide')->name('admin.style-guide');
    });
});

Route::fallback(function () {
    if (Auth::check()) {
        $user = Auth::user();

        if ($user->can('view-rh')) {
            return redirect()->route('rh.dashboard');
        }

        return redirect()->route('ponto.index');
    }

    return redirect()->route('login');
});

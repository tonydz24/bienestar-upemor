<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Redirección inicial
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas temporales para pruebas
Route::middleware(['auth', 'account.active', 'session.timeout'])->group(function () {
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/admin/dashboard', function () {
            return view('dashboard_test');
        })->name('admin.dashboard');
    });

    Route::middleware('role:Docente')->group(function () {
        Route::get('/docente/dashboard', function () {
            return view('dashboard_test');
        })->name('docente.dashboard');
    });

    Route::middleware('role:Alumno')->group(function () {
        Route::get('/alumno/dashboard', function () {
            return view('dashboard_test');
        })->name('alumno.dashboard');
    });
});
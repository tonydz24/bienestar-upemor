<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Admin\SolicitudAlumnoController;

// Redirección inicial
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Registro público de alumnos
Route::get('/registro', [RegisterController::class, 'create'])->name('register');
Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');

// Rutas protegidas temporales para pruebas
Route::middleware(['auth', 'account.active', 'session.timeout'])->group(function () {
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/admin/dashboard', function () {
            return view('dashboard_test');
        })->name('admin.dashboard');

        Route::get('/admin/solicitudes', [SolicitudAlumnoController::class, 'index'])
            ->name('admin.solicitudes.index');
        Route::patch('/admin/solicitudes/{usuario}/aprobar', [SolicitudAlumnoController::class, 'aprobar'])
            ->whereNumber('usuario')
            ->name('admin.solicitudes.aprobar');
        Route::patch('/admin/solicitudes/{usuario}/rechazar', [SolicitudAlumnoController::class, 'rechazar'])
            ->whereNumber('usuario')
            ->name('admin.solicitudes.rechazar');
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
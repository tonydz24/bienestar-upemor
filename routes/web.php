<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\SolicitudAlumnoController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CarreraController;
use App\Http\Controllers\Admin\PlanEstudioController;
use App\Http\Controllers\Admin\PeriodoAcademicoController;
use App\Http\Controllers\Admin\RecompensaAdminController;
use App\Http\Controllers\Admin\UsuarioAdminController;
use App\Http\Controllers\Admin\TrayectoriaAcademicaAdminController;
use App\Http\Controllers\Docente\DashboardController as DocenteDashboardController;
use App\Http\Controllers\Alumno\DashboardController as AlumnoDashboardController;
use App\Http\Controllers\Alumno\BitacoraController;
use App\Http\Controllers\Alumno\EvaluacionController;
use App\Http\Controllers\Alumno\CitaController;
use App\Http\Controllers\Alumno\RecompensaAlumnoController;
use App\Http\Controllers\Alumno\MeditacionController;
use App\Http\Controllers\Admin\MeditacionAdminController;
use App\Http\Controllers\Admin\EncuestaAdminController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\RespaldoAdminController;
use App\Http\Controllers\Admin\DocenteAsignacionAdminController;
use App\Http\Controllers\Admin\GrupoAdminController;
use App\Http\Controllers\Admin\TestAdminController;
use App\Http\Controllers\Alumno\EncuestaAlumnoController;
use App\Http\Controllers\Alumno\AcademicoController as AlumnoAcademicoController;
use App\Http\Controllers\Docente\CitaGestionController;
use App\Http\Controllers\Docente\AcademicoController;
use App\Http\Controllers\Docente\RecompensaDocenteController;

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
Route::post('/registro', [RegisterController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('register.store');

// Recuperación de contraseñas (FN.1)
Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

// Rutas protegidas por sesión activa y control de inactividad
Route::middleware(['auth', 'account.active', 'session.timeout'])->group(function () {
    Route::middleware('role:Administrador')->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('/admin/solicitudes', [SolicitudAlumnoController::class, 'index'])
            ->name('admin.solicitudes.index');
        Route::patch('/admin/solicitudes/{usuario}/aprobar', [SolicitudAlumnoController::class, 'aprobar'])
            ->whereNumber('usuario')
            ->name('admin.solicitudes.aprobar');
        Route::patch('/admin/solicitudes/{usuario}/rechazar', [SolicitudAlumnoController::class, 'rechazar'])
            ->whereNumber('usuario')
            ->name('admin.solicitudes.rechazar');

        // Gestión Curricular y Carreras
        Route::get('/admin/carreras', [CarreraController::class, 'index'])->name('admin.carreras.index');
        Route::get('/admin/carreras/crear', [CarreraController::class, 'create'])->name('admin.carreras.create');
        Route::post('/admin/carreras', [CarreraController::class, 'store'])->name('admin.carreras.store');
        Route::get('/admin/carreras/{carrera}/editar', [CarreraController::class, 'edit'])->whereNumber('carrera')->name('admin.carreras.edit');
        Route::put('/admin/carreras/{carrera}', [CarreraController::class, 'update'])->whereNumber('carrera')->name('admin.carreras.update');
        Route::delete('/admin/carreras/{carrera}', [CarreraController::class, 'destroy'])->whereNumber('carrera')->name('admin.carreras.destroy');

        // Planes de Estudio
        Route::get('/admin/carreras/{carrera}/planes', [PlanEstudioController::class, 'index'])->whereNumber('carrera')->name('admin.planes.index');
        Route::get('/admin/carreras/{carrera}/planes/crear', [PlanEstudioController::class, 'create'])->whereNumber('carrera')->name('admin.planes.create');
        Route::post('/admin/carreras/{carrera}/planes', [PlanEstudioController::class, 'store'])->whereNumber('carrera')->name('admin.planes.store');
        Route::get('/admin/planes/{plan}', [PlanEstudioController::class, 'show'])->whereNumber('plan')->name('admin.planes.show');
        Route::put('/admin/planes/{plan}', [PlanEstudioController::class, 'update'])->whereNumber('plan')->name('admin.planes.update');
        Route::post('/admin/planes/{plan}/materias', [PlanEstudioController::class, 'agregarMateria'])->whereNumber('plan')->name('admin.planes.materias.store');
        Route::patch('/admin/planes/materias/{asignatura}', [PlanEstudioController::class, 'editarMateria'])->whereNumber('asignatura')->name('admin.planes.materias.update');
        Route::delete('/admin/planes/materias/{asignatura}', [PlanEstudioController::class, 'eliminarMateria'])->whereNumber('asignatura')->name('admin.planes.materias.destroy');
        Route::patch('/admin/planes/{plan}/estado', [PlanEstudioController::class, 'cambiarEstado'])->whereNumber('plan')->name('admin.planes.estado');
        Route::delete('/admin/planes/{plan}', [PlanEstudioController::class, 'destroy'])->whereNumber('plan')->name('admin.planes.destroy');

        // Periodos Académicos y Promociones
        Route::get('/admin/periodos', [PeriodoAcademicoController::class, 'index'])->name('admin.periodos.index');
        Route::get('/admin/periodos/crear', [PeriodoAcademicoController::class, 'create'])->name('admin.periodos.create');
        Route::post('/admin/periodos', [PeriodoAcademicoController::class, 'store'])->name('admin.periodos.store');
        Route::get('/admin/periodos/{periodo}/editar', [PeriodoAcademicoController::class, 'edit'])->whereNumber('periodo')->name('admin.periodos.edit');
        Route::put('/admin/periodos/{periodo}', [PeriodoAcademicoController::class, 'update'])->whereNumber('periodo')->name('admin.periodos.update');
        Route::get('/admin/periodos/{periodo}/promociones', [PeriodoAcademicoController::class, 'promociones'])->whereNumber('periodo')->name('admin.periodos.promociones');
        Route::post('/admin/periodos/{periodo}/promociones', [PeriodoAcademicoController::class, 'ejecutarPromocion'])->whereNumber('periodo')->name('admin.periodos.promociones.ejecutar');
        Route::patch('/admin/periodos/{periodo}/toggle-activo', [PeriodoAcademicoController::class, 'toggleActivo'])->whereNumber('periodo')->name('admin.periodos.toggle-activo');

        // Asignaciones Docentes y Aprobación de Cátedras (Incremento 9)
        Route::get('/admin/docente-asignaciones', [DocenteAsignacionAdminController::class, 'index'])->name('admin.docente-asignaciones.index');
        Route::post('/admin/docente-asignaciones/{asignacion}/aprobar', [DocenteAsignacionAdminController::class, 'aprobar'])->whereNumber('asignacion')->name('admin.docente-asignaciones.aprobar');
        Route::post('/admin/docente-asignaciones/{asignacion}/rechazar', [DocenteAsignacionAdminController::class, 'rechazar'])->whereNumber('asignacion')->name('admin.docente-asignaciones.rechazar');

        // Gestión Institucional de Grupos Académicos (Incremento 13)
        Route::get('/admin/grupos', [GrupoAdminController::class, 'index'])->name('admin.grupos.index');
        Route::post('/admin/grupos', [GrupoAdminController::class, 'store'])->name('admin.grupos.store');
        Route::patch('/admin/grupos/{grupo}/toggle-activo', [GrupoAdminController::class, 'toggleActivo'])->whereNumber('grupo')->name('admin.grupos.toggle-activo');
        Route::post('/admin/grupos/sincronizar', [GrupoAdminController::class, 'sincronizarNominales'])->name('admin.grupos.sincronizar');

        // Catálogo de Recompensas Institucional y Aprobación de Propuestas
        Route::get('/admin/recompensas/catalogo', [RecompensaAdminController::class, 'catalogo'])->name('admin.recompensas.catalogo');
        Route::post('/admin/recompensas/catalogo', [RecompensaAdminController::class, 'storeCatalogo'])->name('admin.recompensas.store');
        Route::put('/admin/recompensas/catalogo/{recompensa}', [RecompensaAdminController::class, 'updateCatalogo'])->whereNumber('recompensa')->name('admin.recompensas.update');
        Route::patch('/admin/recompensas/catalogo/{recompensa}/toggle', [RecompensaAdminController::class, 'toggleActivo'])->whereNumber('recompensa')->name('admin.recompensas.toggle');
        Route::delete('/admin/recompensas/catalogo/{recompensa}', [RecompensaAdminController::class, 'destroyCatalogo'])->whereNumber('recompensa')->name('admin.recompensas.destroy');
        Route::get('/admin/recompensas/catalogo/{recompensa}/activaciones', [RecompensaAdminController::class, 'activaciones'])->whereNumber('recompensa')->name('admin.recompensas.activaciones');
        Route::get('/admin/recompensas/catalogo/{recompensa}/canjes', [RecompensaAdminController::class, 'canjes'])->whereNumber('recompensa')->name('admin.recompensas.canjes');
        Route::patch('/admin/recompensas/propuestas/{recompensa}/aprobar', [RecompensaAdminController::class, 'aprobarPropuesta'])->whereNumber('recompensa')->name('admin.recompensas.aprobar');
        Route::patch('/admin/recompensas/propuestas/{recompensa}/rechazar', [RecompensaAdminController::class, 'rechazarPropuesta'])->whereNumber('recompensa')->name('admin.recompensas.rechazar');

        // Gestión Institucional de Usuarios (FN.2)
        Route::get('/admin/usuarios', [UsuarioAdminController::class, 'index'])->name('admin.usuarios.index');
        Route::get('/admin/usuarios/crear', [UsuarioAdminController::class, 'create'])->name('admin.usuarios.create');
        Route::post('/admin/usuarios', [UsuarioAdminController::class, 'store'])->name('admin.usuarios.store');
        Route::get('/admin/usuarios/{usuario}/editar', [UsuarioAdminController::class, 'edit'])->whereNumber('usuario')->name('admin.usuarios.edit');
        Route::put('/admin/usuarios/{usuario}', [UsuarioAdminController::class, 'update'])->whereNumber('usuario')->name('admin.usuarios.update');
        Route::patch('/admin/usuarios/{usuario}/estado', [UsuarioAdminController::class, 'cambiarEstado'])->whereNumber('usuario')->name('admin.usuarios.estado');
        Route::post('/admin/usuarios/{usuario}/reset-password', [UsuarioAdminController::class, 'resetPassword'])->whereNumber('usuario')->name('admin.usuarios.reset-password');
        Route::delete('/admin/usuarios/{usuario}', [UsuarioAdminController::class, 'destroy'])->whereNumber('usuario')->name('admin.usuarios.destroy');
        Route::get('/admin/estudiantes/{usuario}/trayectoria', [TrayectoriaAcademicaAdminController::class, 'show'])->whereNumber('usuario')->name('admin.estudiantes.trayectoria');
        Route::post('/admin/estudiantes/{usuario}/cambiar-plan', [TrayectoriaAcademicaAdminController::class, 'cambiarPlan'])->whereNumber('usuario')->name('admin.estudiantes.cambiar-plan');

        // Gestión de Meditaciones y Relajación Guiada (FN.6)
        Route::get('/admin/meditaciones', [MeditacionAdminController::class, 'index'])->name('admin.meditaciones.index');
        Route::get('/admin/meditaciones/crear', [MeditacionAdminController::class, 'create'])->name('admin.meditaciones.create');
        Route::post('/admin/meditaciones', [MeditacionAdminController::class, 'store'])->name('admin.meditaciones.store');
        Route::get('/admin/meditaciones/{meditacion}/editar', [MeditacionAdminController::class, 'edit'])->whereNumber('meditacion')->name('admin.meditaciones.edit');
        Route::put('/admin/meditaciones/{meditacion}', [MeditacionAdminController::class, 'update'])->whereNumber('meditacion')->name('admin.meditaciones.update');
        Route::patch('/admin/meditaciones/{meditacion}/toggle-publicado', [MeditacionAdminController::class, 'togglePublicado'])->whereNumber('meditacion')->name('admin.meditaciones.toggle-publicado');
        Route::patch('/admin/meditaciones/{meditacion}/retirar', [MeditacionAdminController::class, 'retirar'])->whereNumber('meditacion')->name('admin.meditaciones.retirar');
        Route::patch('/admin/meditaciones/{meditacion}/publicar', [MeditacionAdminController::class, 'publicar'])->whereNumber('meditacion')->name('admin.meditaciones.publicar');
        Route::delete('/admin/meditaciones/{meditacion}', [MeditacionAdminController::class, 'destroy'])->whereNumber('meditacion')->name('admin.meditaciones.destroy');

        // Gestión de Tests e Instrumentos Psicométricos
        Route::get('/admin/tests', [TestAdminController::class, 'index'])->name('admin.tests.index');
        Route::get('/admin/tests/crear', [TestAdminController::class, 'create'])->name('admin.tests.create');
        Route::post('/admin/tests', [TestAdminController::class, 'store'])->name('admin.tests.store');
        Route::get('/admin/tests/{test}', [TestAdminController::class, 'show'])->whereNumber('test')->name('admin.tests.show');
        Route::get('/admin/tests/{test}/editar', [TestAdminController::class, 'edit'])->whereNumber('test')->name('admin.tests.edit');
        Route::put('/admin/tests/{test}', [TestAdminController::class, 'update'])->whereNumber('test')->name('admin.tests.update');
        Route::patch('/admin/tests/{test}/toggle-estado', [TestAdminController::class, 'toggleEstado'])->whereNumber('test')->name('admin.tests.toggle-estado');
        Route::patch('/admin/tests/{test}/retirar', [TestAdminController::class, 'retirar'])->whereNumber('test')->name('admin.tests.retirar');
        Route::post('/admin/tests/{test}/duplicar', [TestAdminController::class, 'duplicar'])->whereNumber('test')->name('admin.tests.duplicar');
        Route::post('/admin/tests/{test}/nueva-version', [TestAdminController::class, 'nuevaVersion'])->whereNumber('test')->name('admin.tests.nueva-version');
        Route::patch('/admin/tests/{test}/publicar-version', [TestAdminController::class, 'publicarVersion'])->whereNumber('test')->name('admin.tests.publicar-version');
        Route::delete('/admin/tests/{test}', [TestAdminController::class, 'destroy'])->whereNumber('test')->name('admin.tests.destroy');
        Route::get('/admin/tests/{test}/resultados', [TestAdminController::class, 'resultados'])->whereNumber('test')->name('admin.tests.resultados');
        Route::get('/admin/tests/{test}/intentos/{intento}', [TestAdminController::class, 'intentoDetalle'])->whereNumber('test')->whereNumber('intento')->name('admin.tests.intentos.show');

        // Módulo de Encuestas de Satisfacción (FN.8)
        Route::get('/admin/encuestas', [EncuestaAdminController::class, 'index'])->name('admin.encuestas.index');
        Route::get('/admin/encuestas/crear', [EncuestaAdminController::class, 'create'])->name('admin.encuestas.create');
        Route::post('/admin/encuestas', [EncuestaAdminController::class, 'store'])->name('admin.encuestas.store');
        Route::get('/admin/encuestas/{encuesta}/editar', [EncuestaAdminController::class, 'edit'])->whereNumber('encuesta')->name('admin.encuestas.edit');
        Route::put('/admin/encuestas/{encuesta}', [EncuestaAdminController::class, 'update'])->whereNumber('encuesta')->name('admin.encuestas.update');
        Route::patch('/admin/encuestas/{encuesta}/toggle-estado', [EncuestaAdminController::class, 'toggleEstado'])->whereNumber('encuesta')->name('admin.encuestas.toggle-estado');
        Route::get('/admin/encuestas/{encuesta}/resultados', [EncuestaAdminController::class, 'resultados'])->whereNumber('encuesta')->name('admin.encuestas.resultados');
        Route::delete('/admin/encuestas/{encuesta}', [EncuestaAdminController::class, 'destroy'])->whereNumber('encuesta')->name('admin.encuestas.destroy');

        // Módulo de Reportes Institucionales (FN.15)
        Route::get('/admin/reportes', [ReporteController::class, 'index'])->name('admin.reportes.index');
        Route::get('/admin/reportes/{tipo}', [ReporteController::class, 'show'])->name('admin.reportes.show');
        Route::get('/admin/reportes/{tipo}/exportar-pdf', [ReporteController::class, 'exportarPdf'])->name('admin.reportes.exportar-pdf');
        Route::get('/admin/reportes/{tipo}/exportar-excel', [ReporteController::class, 'exportarExcel'])->name('admin.reportes.exportar-excel');

        // Módulo de Respaldos Administrativos (FN.16)
        Route::get('/admin/respaldos', [RespaldoAdminController::class, 'index'])->name('admin.respaldos.index');
        Route::post('/admin/respaldos', [RespaldoAdminController::class, 'store'])->name('admin.respaldos.store');
        Route::get('/admin/respaldos/descargar/{archivo}', [RespaldoAdminController::class, 'descargar'])->name('admin.respaldos.descargar');
        Route::delete('/admin/respaldos/{archivo}', [RespaldoAdminController::class, 'destroy'])->name('admin.respaldos.destroy');
        Route::post('/admin/respaldos/verificar/{archivo}', [RespaldoAdminController::class, 'verificar'])->name('admin.respaldos.verificar');
        Route::post('/admin/respaldos/restaurar/{archivo}', [RespaldoAdminController::class, 'solicitarRestauracion'])->name('admin.respaldos.solicitar-restauracion');
    });

    // Módulo de Orientación y Agenda de Citas (orientadores autorizados y docentes con función orientadora)
    Route::middleware('role:Docente,Orientador')->group(function () {
        Route::get('/docente/citas', [CitaGestionController::class, 'index'])->name('docente.citas.index');
        Route::get('/docente/citas/disponibilidad', [CitaGestionController::class, 'disponibilidadIndex'])->name('docente.citas.disponibilidad');
        Route::post('/docente/citas/disponibilidad', [CitaGestionController::class, 'disponibilidadStore'])->name('docente.citas.disponibilidad.store');
        Route::delete('/docente/citas/disponibilidad/{id}', [CitaGestionController::class, 'disponibilidadDestroy'])->whereNumber('id')->name('docente.citas.disponibilidad.destroy');
        Route::get('/docente/citas/{cita}', [CitaGestionController::class, 'show'])->whereNumber('cita')->name('docente.citas.show');
        Route::patch('/docente/citas/{cita}/confirmar', [CitaGestionController::class, 'confirmar'])->whereNumber('cita')->name('docente.citas.confirmar');
        Route::patch('/docente/citas/{cita}/rechazar', [CitaGestionController::class, 'rechazar'])->whereNumber('cita')->name('docente.citas.rechazar');
        Route::patch('/docente/citas/{cita}/cancelar', [CitaGestionController::class, 'cancelar'])->whereNumber('cita')->name('docente.citas.cancelar');
        Route::patch('/docente/citas/{cita}/finalizar', [CitaGestionController::class, 'finalizar'])->whereNumber('cita')->name('docente.citas.finalizar');
        Route::patch('/docente/citas/{cita}/no-asistio', [CitaGestionController::class, 'noAsistio'])->whereNumber('cita')->name('docente.citas.no-asistio');
        Route::patch('/docente/citas/{cita}/reprogramacion/aprobar', [CitaGestionController::class, 'aprobarReprogramacion'])->whereNumber('cita')->name('docente.citas.reprogramacion.aprobar');
        Route::patch('/docente/citas/{cita}/reprogramacion/rechazar', [CitaGestionController::class, 'rechazarReprogramacion'])->whereNumber('cita')->name('docente.citas.reprogramacion.rechazar');
    });

    Route::middleware('role:Docente')->group(function () {
        Route::get('/docente/dashboard', [DocenteDashboardController::class, 'index'])->name('docente.dashboard');

        // Módulo de Estructura Académica (Asignaturas, Grupos y Solicitudes)
        Route::get('/docente/academico', [AcademicoController::class, 'index'])->name('docente.academico.index');
        Route::get('/docente/academico/solicitar', [AcademicoController::class, 'createSolicitud'])->name('docente.academico.solicitar');
        Route::post('/docente/academico/solicitar', [AcademicoController::class, 'storeSolicitud'])->name('docente.academico.solicitar.store');
        Route::get('/docente/academico/grupos/{asignacion}', [AcademicoController::class, 'grupo'])
            ->whereNumber('asignacion')
            ->name('docente.academico.grupo');
        Route::post('/docente/academico/inscripciones/{inscripcion}/aprobar', [AcademicoController::class, 'aprobarInscripcion'])
            ->whereNumber('inscripcion')
            ->name('docente.academico.inscripciones.aprobar');
        Route::post('/docente/academico/inscripciones/{inscripcion}/rechazar', [AcademicoController::class, 'rechazarInscripcion'])
            ->whereNumber('inscripcion')
            ->name('docente.academico.inscripciones.rechazar');

        // Módulo de Recompensas Docente
        Route::get('/docente/recompensas', [RecompensaDocenteController::class, 'index'])->name('docente.recompensas.index');
        Route::get('/docente/recompensas/activar/{asignacion}', [RecompensaDocenteController::class, 'create'])
            ->whereNumber('asignacion')
            ->name('docente.recompensas.create');
        Route::post('/docente/recompensas/activar/{asignacion}', [RecompensaDocenteController::class, 'store'])
            ->whereNumber('asignacion')
            ->name('docente.recompensas.store');
        Route::patch('/docente/recompensas/{activacion}/estado', [RecompensaDocenteController::class, 'cambiarEstado'])
            ->whereNumber('activacion')
            ->name('docente.recompensas.estado');
        Route::get('/docente/recompensas/{activacion}/editar', [RecompensaDocenteController::class, 'edit'])
            ->whereNumber('activacion')
            ->name('docente.recompensas.edit');
        Route::put('/docente/recompensas/{activacion}', [RecompensaDocenteController::class, 'update'])
            ->whereNumber('activacion')
            ->name('docente.recompensas.update');
        Route::patch('/docente/recompensas/canjes/{canje}/aprobar', [RecompensaDocenteController::class, 'aprobar'])
            ->whereNumber('canje')
            ->name('docente.recompensas.aprobar');
        Route::patch('/docente/recompensas/canjes/{canje}/utilizar', [RecompensaDocenteController::class, 'utilizar'])
            ->whereNumber('canje')
            ->name('docente.recompensas.utilizar');
        Route::patch('/docente/recompensas/canjes/{canje}/rechazar', [RecompensaDocenteController::class, 'rechazar'])
            ->whereNumber('canje')
            ->name('docente.recompensas.rechazar');
        Route::post('/docente/recompensas/proponer', [RecompensaDocenteController::class, 'proponer'])
            ->name('docente.recompensas.proponer');
    });

    Route::middleware('role:Alumno')->group(function () {
        Route::get('/alumno/dashboard', [AlumnoDashboardController::class, 'index'])->name('alumno.dashboard');

        // Módulo de Citas de Orientación
        Route::get('/alumno/citas', [CitaController::class, 'index'])->name('alumno.citas.index');
        Route::get('/alumno/citas/solicitar', [CitaController::class, 'create'])->name('alumno.citas.create');
        Route::get('/alumno/citas/horarios-disponibles', [CitaController::class, 'horariosDisponibles'])->name('alumno.citas.horarios-disponibles');
        Route::post('/alumno/citas', [CitaController::class, 'store'])->name('alumno.citas.store');
        Route::get('/alumno/citas/{cita}', [CitaController::class, 'show'])->whereNumber('cita')->name('alumno.citas.show');
        Route::patch('/alumno/citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->whereNumber('cita')->name('alumno.citas.cancelar');
        Route::post('/alumno/citas/{cita}/reprogramar', [CitaController::class, 'solicitarReprogramacion'])->whereNumber('cita')->name('alumno.citas.reprogramar');
        Route::delete('/alumno/citas/{cita}/reprogramar', [CitaController::class, 'cancelarReprogramacion'])->whereNumber('cita')->name('alumno.citas.cancelar-reprogramacion');

        // Módulo de Recompensas Alumno
        Route::get('/alumno/recompensas', [RecompensaAlumnoController::class, 'index'])->name('alumno.recompensas.index');
        Route::post('/alumno/recompensas/{activacion}/canjear', [RecompensaAlumnoController::class, 'canjear'])
            ->whereNumber('activacion')
            ->name('alumno.recompensas.canjear');
        Route::delete('/alumno/recompensas/canjes/{canje}', [RecompensaAlumnoController::class, 'cancelar'])
            ->whereNumber('canje')
            ->name('alumno.recompensas.cancelar');

        Route::get('/alumno/evaluaciones', [EvaluacionController::class, 'index'])->name('alumno.evaluaciones');
        Route::get('/alumno/tests', [EvaluacionController::class, 'index'])->name('alumno.tests.index');
        Route::get('/alumno/evaluaciones/historial', [EvaluacionController::class, 'historial'])->name('alumno.evaluaciones.historial');
        Route::get('/alumno/evaluaciones/{instrumento}/instrucciones', [EvaluacionController::class, 'instrucciones'])
            ->whereNumber('instrumento')
            ->name('alumno.evaluaciones.instrucciones');
        Route::post('/alumno/evaluaciones/{instrumento}/iniciar', [EvaluacionController::class, 'iniciar'])
            ->whereNumber('instrumento')
            ->name('alumno.evaluaciones.iniciar');
        Route::get('/alumno/evaluaciones/intentos/{intento}/cuestionario', [EvaluacionController::class, 'cuestionario'])
            ->whereNumber('intento')
            ->name('alumno.evaluaciones.cuestionario');
        Route::post('/alumno/evaluaciones/intentos/{intento}/respuesta', [EvaluacionController::class, 'guardarRespuesta'])
            ->whereNumber('intento')
            ->name('alumno.evaluaciones.guardar-respuesta');
        Route::get('/alumno/evaluaciones/intentos/{intento}/progreso', [EvaluacionController::class, 'progreso'])
            ->whereNumber('intento')
            ->name('alumno.evaluaciones.progreso');
        Route::post('/alumno/evaluaciones/intentos/{intento}/finalizar', [EvaluacionController::class, 'finalizar'])
            ->whereNumber('intento')
            ->name('alumno.evaluaciones.finalizar');
        Route::get('/alumno/evaluaciones/intentos/{intento}/finalizado', [EvaluacionController::class, 'finalizado'])
            ->whereNumber('intento')
            ->name('alumno.evaluaciones.finalizado');
        Route::get('/alumno/bitacora', [BitacoraController::class, 'index'])->name('alumno.bitacora.index');
        Route::post('/alumno/bitacora', [BitacoraController::class, 'store'])->name('alumno.bitacora.store');
        Route::get('/alumno/bitacora/{bitacora}', [BitacoraController::class, 'show'])
            ->whereNumber('bitacora')
            ->name('alumno.bitacora.show');
        Route::put('/alumno/bitacora/{bitacora}', [BitacoraController::class, 'update'])
            ->whereNumber('bitacora')
            ->name('alumno.bitacora.update');
        Route::delete('/alumno/bitacora/{bitacora}', [BitacoraController::class, 'destroy'])
            ->whereNumber('bitacora')
            ->name('alumno.bitacora.destroy');

        // Módulo de Meditaciones y Relajación Guiada (FN.6)
        Route::get('/alumno/meditaciones', [MeditacionController::class, 'index'])->name('alumno.meditaciones.index');
        Route::get('/alumno/meditaciones/historial', [MeditacionController::class, 'historial'])->name('alumno.meditaciones.historial');
        Route::get('/alumno/meditaciones/{meditacion}', [MeditacionController::class, 'show'])->whereNumber('meditacion')->name('alumno.meditaciones.show');
        Route::get('/alumno/meditaciones/{meditacion}/sesion', [MeditacionController::class, 'sesion'])->whereNumber('meditacion')->name('alumno.meditaciones.sesion');
        Route::post('/alumno/meditaciones/{meditacion}/guardar-sesion', [MeditacionController::class, 'guardarSesion'])->whereNumber('meditacion')->name('alumno.meditaciones.guardar-sesion');
        Route::post('/alumno/meditaciones/{meditacion}/completar', [MeditacionController::class, 'completar'])->whereNumber('meditacion')->name('alumno.meditaciones.completar');

        // Encuestas de Satisfacción Estudiantil (FN.8)
        Route::get('/alumno/encuestas', [EncuestaAlumnoController::class, 'index'])->name('alumno.encuestas.index');
        Route::get('/alumno/encuestas/{encuesta}', [EncuestaAlumnoController::class, 'responder'])->whereNumber('encuesta')->name('alumno.encuestas.responder');
        Route::post('/alumno/encuestas/{encuesta}', [EncuestaAlumnoController::class, 'guardar'])->whereNumber('encuesta')->name('alumno.encuestas.guardar');
        Route::get('/alumno/encuestas/citas/{cita}', [EncuestaAlumnoController::class, 'postCita'])->whereNumber('cita')->name('alumno.encuestas.post-cita');

        // Módulo Académico Estudiantil (Mi Grupo, Mis Materias e Inscripciones)
        Route::get('/alumno/academico', [AlumnoAcademicoController::class, 'index'])->name('alumno.academico.index');
        Route::post('/alumno/academico/solicitar', [AlumnoAcademicoController::class, 'solicitar'])->name('alumno.academico.solicitar');
        Route::post('/alumno/academico/inscripciones/{inscripcion}/cancelar', [AlumnoAcademicoController::class, 'cancelar'])
            ->whereNumber('inscripcion')
            ->name('alumno.academico.cancelar');
    });

    // Centro de Notificaciones en la Aplicación (FN.14 & FF.5)
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notificaciones.index');
    Route::get('/notificaciones/recientes-unread', [NotificationController::class, 'getUnreadRecent'])->name('notificaciones.recientes-unread');
    Route::get('/notificaciones/recientes', [NotificationController::class, 'getUnreadRecent'])->name('notificaciones.recientes');
    Route::get('/notificaciones/{id}/ir', [NotificationController::class, 'goToResource'])->name('notificaciones.ir');
    Route::post('/notificaciones/{id}/leer', [NotificationController::class, 'markAsRead'])->name('notificaciones.leer');
    Route::post('/notificaciones/marcar-todas', [NotificationController::class, 'markAllAsRead'])->name('notificaciones.marcar-todas');

    // Control de sesión e inactividad institucional (FN.13, FF.4)
    Route::post('/session/keep-alive', function () {
        session(['last_activity_time' => time()]);
        return response()->json(['status' => 'active', 'timestamp' => time()]);
    })->name('session.keep-alive');
});
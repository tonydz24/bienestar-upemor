<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraEmocional;
use App\Models\CitaDisponibilidad;
use App\Models\CitaOrientacion;
use App\Models\CitaProfesional;
use App\Models\Docente;
use App\Models\DocenteAsignacion;
use App\Models\Estudiante;
use App\Models\EstudianteInscripcion;
use App\Models\EvaluacionIntento;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaCanje;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controlador de Gestión Integral de Usuarios y Cuentas Institucionales.
 *
 * Administra el ciclo de vida de usuarios con roles Administrador, Docente,
 * Alumno y Orientador; validación de perfiles específicos, reseteo de contraseñas,
 * cambios de estado y auditoría preventiva de dependencias antes de bajas.
 */
class UsuarioAdminController extends Controller
{
    /**
     * Catálogo de estados institucionales permitidos.
     *
     * @var array<int, string>
     */
    private const ESTADOS = ['TODOS', 'ACTIVO', 'PENDIENTE', 'INACTIVO', 'BLOQUEADO', 'RECHAZADO'];

    /**
     * Catálogo de roles válidos para filtrado.
     *
     * @var array<int, string>
     */
    private const ROLES_VALIDOS = ['TODOS', 'Administrador', 'Docente', 'Alumno', 'Orientador'];

    /**
     * Muestra la lista paginada de usuarios institucionales con filtros por rol, estado y búsqueda textual.
     *
     * @param Request $request Petición HTTP con filtros opcionales ('rol', 'estado', 'buscar').
     * @return View Vista con la relación de usuarios y contadores métricos institucionales.
     */
    public function index(Request $request): View
    {
        $rolFiltro = $request->query('rol', 'TODOS');
        if (! in_array($rolFiltro, self::ROLES_VALIDOS, true)) {
            $rolFiltro = 'TODOS';
        }

        $estadoFiltro = $request->query('estado', 'TODOS');
        if (! in_array($estadoFiltro, self::ESTADOS, true)) {
            $estadoFiltro = 'TODOS';
        }

        $buscar = trim((string) $request->query('buscar', ''));

        // Query principal con relaciones
        $query = User::query()
            ->with(['rol', 'estudiante.programa', 'docente', 'citaProfesional', 'roles']);

        // Filtro por Rol
        if ($rolFiltro !== 'TODOS') {
            if ($rolFiltro === 'Orientador') {
                $query->where(function ($q) {
                    $q->whereHas('rol', fn ($r) => $r->where('nombre', 'Orientador'))
                        ->orWhereHas('roles', fn ($r) => $r->where('nombre', 'Orientador'))
                        ->orWhereHas('citaProfesional', fn ($cp) => $cp->where('activo', true));
                });
            } else {
                $query->whereHas('rol', fn ($r) => $r->where('nombre', $rolFiltro));
            }
        }

        // Filtro por Estado
        if ($estadoFiltro !== 'TODOS') {
            $query->where('estado', $estadoFiltro);
        }

        // Búsqueda por texto (nombre, apellidos, correo, teléfono, matrícula, número de empleado, cédula)
        if ($buscar !== '') {
            $like = '%' . addcslashes($buscar, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('telefono', 'like', $like)
                    ->orWhereHas('estudiante', fn ($e) => $e->where('matricula', 'like', $like))
                    ->orWhereHas('docente', fn ($d) => $d->where('numero_empleado', 'like', $like))
                    ->orWhereHas('citaProfesional', fn ($cp) => $cp->where('cedula_profesional', 'like', $like));
            });
        }

        // Métricas resumidas
        $totalUsuarios = User::count();
        $totalAlumnos = User::whereHas('rol', fn ($r) => $r->where('nombre', 'Alumno'))->count();
        $totalDocentes = User::whereHas('rol', fn ($r) => $r->where('nombre', 'Docente'))->count();
        $totalOrientadores = User::where(function ($q) {
            $q->whereHas('rol', fn ($r) => $r->where('nombre', 'Orientador'))
                ->orWhereHas('citaProfesional');
        })->count();
        $totalAdministradores = User::whereHas('rol', fn ($r) => $r->where('nombre', 'Administrador'))->count();
        $totalPendientes = User::where('estado', 'PENDIENTE')->count();

        $usuarios = $query
            ->orderByDesc('usuario_id')
            ->paginate(15)
            ->withQueryString();

        $rolesDisponibles = Rol::all();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'rolFiltro' => $rolFiltro,
            'estadoFiltro' => $estadoFiltro,
            'buscar' => $buscar,
            'rolesValidos' => self::ROLES_VALIDOS,
            'estadosValidos' => self::ESTADOS,
            'totalUsuarios' => $totalUsuarios,
            'totalAlumnos' => $totalAlumnos,
            'totalDocentes' => $totalDocentes,
            'totalOrientadores' => $totalOrientadores,
            'totalAdministradores' => $totalAdministradores,
            'totalPendientes' => $totalPendientes,
            'rolesDisponibles' => $rolesDisponibles,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario institucional.
     *
     * @return View Vista con catálogo de roles y programas educativos activos.
     */
    public function create(): View
    {
        $roles = Rol::where('activo', true)->orderBy('rol_id')->get();
        $programas = ProgramaEducativo::where('activo', true)->orderBy('nombre')->get();

        return view('admin.usuarios.create', [
            'roles' => $roles,
            'programas' => $programas,
        ]);
    }

    /**
     * Almacena un nuevo usuario en la base de datos junto con su perfil especializado (Alumno, Docente u Orientador).
     *
     * @param Request $request Petición HTTP con datos del usuario y perfil según el rol.
     * @return RedirectResponse Redirección a la lista de usuarios con mensaje de confirmación.
     * @throws \Illuminate\Validation\ValidationException Si los datos no superan la validación.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'rol_id' => ['required', 'integer', 'exists:roles,rol_id'],
            'estado' => ['required', 'string', Rule::in(['ACTIVO', 'PENDIENTE', 'INACTIVO', 'BLOQUEADO'])],
        ]);

        $rol = Rol::findOrFail($request->input('rol_id'));

        // Validaciones específicas según el rol
        if ($rol->nombre === 'Alumno') {
            $request->validate([
                'matricula' => ['required', 'string', 'max:20', 'unique:estudiantes,matricula'],
                'programa_id' => ['required', 'integer', 'exists:programas_educativos,programa_id'],
                'cuatrimestre' => ['required', 'integer', 'min:1', 'max:12'],
                'grupo' => ['nullable', 'string', 'max:5'],
            ]);
        } elseif ($rol->nombre === 'Docente') {
            $request->validate([
                'numero_empleado' => ['required', 'string', 'max:30', 'unique:docentes,numero_empleado'],
                'puesto' => ['nullable', 'string', 'max:100'],
                'especialidad' => ['nullable', 'string', 'max:150'],
                'cedula_profesional' => ['nullable', 'string', 'max:50'],
            ]);
        } elseif ($rol->nombre === 'Orientador') {
            $request->validate([
                'cargo' => ['required', 'string', 'max:120'],
                'cedula_profesional' => ['nullable', 'string', 'max:50'],
                'lugar_atencion' => ['nullable', 'string', 'max:150'],
                'modalidades_permitidas' => ['nullable', 'string', 'max:100'],
                'permite_citas' => ['nullable', 'boolean'],
            ]);
        }

        DB::transaction(function () use ($request, $rol) {
            $user = User::create([
                'rol_id' => $rol->rol_id,
                'nombre' => trim($request->input('nombre')),
                'apellido_paterno' => trim($request->input('apellido_paterno')),
                'apellido_materno' => trim($request->input('apellido_materno')),
                'email' => strtolower(trim($request->input('email'))),
                'password' => Hash::make($request->input('password')),
                'telefono' => $request->filled('telefono') ? trim($request->input('telefono')) : null,
                'estado' => $request->input('estado', 'ACTIVO'),
            ]);

            if ($rol->nombre === 'Alumno') {
                Estudiante::create([
                    'usuario_id' => $user->usuario_id,
                    'programa_id' => $request->input('programa_id'),
                    'matricula' => strtoupper(trim($request->input('matricula'))),
                    'cuatrimestre' => (int) $request->input('cuatrimestre', 1),
                    'grupo' => $request->filled('grupo') ? strtoupper(trim($request->input('grupo'))) : null,
                    'fecha_ingreso' => now()->toDateString(),
                    'saldo_puntos' => 0,
                    'permite_visualizacion' => true,
                ]);
            } elseif ($rol->nombre === 'Docente') {
                Docente::create([
                    'usuario_id' => $user->usuario_id,
                    'numero_empleado' => strtoupper(trim($request->input('numero_empleado'))),
                    'puesto' => $request->filled('puesto') ? trim($request->input('puesto')) : 'Profesor de Tiempo Completo / Asignatura',
                    'especialidad' => $request->filled('especialidad') ? trim($request->input('especialidad')) : null,
                    'cedula_profesional' => $request->filled('cedula_profesional') ? trim($request->input('cedula_profesional')) : null,
                    'activo' => true,
                ]);
            } elseif ($rol->nombre === 'Orientador') {
                $modalidad = strtoupper($request->input('modalidades_permitidas', 'PRESENCIAL'));
                if (!in_array($modalidad, ['PRESENCIAL', 'VIRTUAL', 'AMBAS'], true)) {
                    $modalidad = 'PRESENCIAL';
                }

                CitaProfesional::create([
                    'usuario_id' => $user->usuario_id,
                    'cargo' => trim($request->input('cargo', 'Psicólogo / Orientador Estudiantil')),
                    'cedula_profesional' => $request->filled('cedula_profesional') ? trim($request->input('cedula_profesional')) : null,
                    'lugar_atencion' => $request->filled('lugar_atencion') ? trim($request->input('lugar_atencion')) : 'Edificio de Acompañamiento y Bienestar',
                    'modalidades_permitidas' => $modalidad,
                    'permite_citas' => $request->has('permite_citas') ? $request->boolean('permite_citas') : true,
                    'activo' => true,
                ]);
            }
        });

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario institucional creado exitosamente con rol {$rol->nombre}.");
    }

    /**
     * Muestra el formulario para editar la información de un usuario institucional.
     *
     * @param int $usuarioId Identificador del usuario.
     * @return View Vista de edición con roles, programas y planes de estudio.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el usuario no existe.
     */
    public function edit(int $usuarioId): View
    {
        $usuario = User::with(['rol', 'estudiante.programa', 'docente', 'citaProfesional', 'roles'])
            ->findOrFail($usuarioId);

        $roles = Rol::where('activo', true)->orderBy('rol_id')->get();
        $programas = ProgramaEducativo::where('activo', true)->orderBy('nombre')->get();
        $planesEstudio = PlanEstudio::with('programa')->orderBy('codigo')->get();

        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
            'roles' => $roles,
            'programas' => $programas,
            'planesEstudio' => $planesEstudio,
        ]);
    }

    /**
     * Actualiza los datos generales y específicos de rol del usuario.
     *
     * Incluye salvaguardas contra auto-bloqueo y prevención de desactivación
     * del único administrador activo del sistema.
     *
     * @param Request $request Petición con campos validados.
     * @param int $usuarioId Identificador del usuario.
     * @return RedirectResponse Redirección con mensaje de confirmación o error de salvaguarda.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el usuario no existe.
     * @throws \Illuminate\Validation\ValidationException Si los datos fallan validación.
     */
    public function update(Request $request, int $usuarioId): RedirectResponse
    {
        $usuario = User::with(['rol', 'estudiante', 'docente', 'citaProfesional', 'roles'])
            ->findOrFail($usuarioId);

        $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'apellido_paterno' => ['required', 'string', 'max:80'],
            'apellido_materno' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->usuario_id, 'usuario_id')],
            'telefono' => ['nullable', 'string', 'max:20'],
            'estado' => ['required', 'string', Rule::in(['ACTIVO', 'PENDIENTE', 'INACTIVO', 'BLOQUEADO', 'RECHAZADO'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $rolActual = $usuario->rol?->nombre;
        $esPropiaCuenta = (auth()->id() === $usuario->usuario_id);

        // Protección contra auto-bloqueo o auto-desactivación del admin en sesión
        if ($esPropiaCuenta && in_array($request->input('estado'), ['INACTIVO', 'BLOQUEADO', 'RECHAZADO'], true)) {
            return back()->withInput()->with('error', 'No puedes desactivar, bloquear o rechazar tu propia cuenta de Administrador.');
        }

        // Protección contra dejar el sistema sin administradores activos
        if ($rolActual === 'Administrador' && $request->input('estado') !== 'ACTIVO') {
            $otrosAdminsActivos = User::whereHas('rol', fn ($r) => $r->where('nombre', 'Administrador'))
                ->where('estado', 'ACTIVO')
                ->where('usuario_id', '!=', $usuario->usuario_id)
                ->count();

            if ($otrosAdminsActivos === 0) {
                return back()->withInput()->with('error', 'No es posible desactivar o bloquear al único Administrador activo del sistema.');
            }
        }

        // Validaciones condicionales según el rol actual
        if ($rolActual === 'Alumno' || $usuario->estudiante) {
            $request->validate([
                'matricula' => ['required', 'string', 'max:20', Rule::unique('estudiantes', 'matricula')->ignore($usuario->usuario_id, 'usuario_id')],
                'programa_id' => ['required', 'integer', 'exists:programas_educativos,programa_id'],
                'plan_id' => ['nullable', 'integer', 'exists:planes_estudio,plan_id'],
                'cuatrimestre' => ['required', 'integer', 'min:1', 'max:12'],
                'grupo' => ['nullable', 'string', 'max:5'],
            ]);
        } elseif ($rolActual === 'Docente' || $usuario->docente) {
            $request->validate([
                'numero_empleado' => ['required', 'string', 'max:30', Rule::unique('docentes', 'numero_empleado')->ignore($usuario->usuario_id, 'usuario_id')],
                'puesto' => ['nullable', 'string', 'max:100'],
                'especialidad' => ['nullable', 'string', 'max:150'],
                'cedula_profesional' => ['nullable', 'string', 'max:50'],
            ]);
        } elseif ($rolActual === 'Orientador' || $usuario->citaProfesional) {
            $request->validate([
                'cargo' => ['required', 'string', 'max:120'],
                'cedula_profesional' => ['nullable', 'string', 'max:50'],
                'lugar_atencion' => ['nullable', 'string', 'max:150'],
                'modalidades_permitidas' => ['nullable', 'string', 'max:100'],
            ]);
        }

        DB::transaction(function () use ($request, $usuario) {
            $usuario->nombre = trim($request->input('nombre'));
            $usuario->apellido_paterno = trim($request->input('apellido_paterno'));
            $usuario->apellido_materno = $request->filled('apellido_materno') ? trim($request->input('apellido_materno')) : null;
            $usuario->email = strtolower(trim($request->input('email')));
            $usuario->telefono = $request->filled('telefono') ? trim($request->input('telefono')) : null;
            $usuario->estado = $request->input('estado');

            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->input('password'));
            }

            $usuario->save();

            // Actualización segura de modelos especializados sin destruir relaciones existentes
            if ($usuario->estudiante) {
                $estudianteUpdate = [
                    'matricula' => strtoupper(trim($request->input('matricula'))),
                    'programa_id' => $request->input('programa_id'),
                    'cuatrimestre' => (int) $request->input('cuatrimestre'),
                    'grupo' => $request->filled('grupo') ? strtoupper(trim($request->input('grupo'))) : null,
                ];

                if (Schema::hasColumn('estudiantes', 'plan_id')) {
                    if ($request->filled('plan_id')) {
                        $estudianteUpdate['plan_id'] = (int) $request->input('plan_id');
                    } elseif (empty($usuario->estudiante->plan_id)) {
                        $plan = $usuario->estudiante->resolverPlanEstudio();
                        if ($plan) {
                            $estudianteUpdate['plan_id'] = $plan->plan_id;
                        }
                    }
                }

                $usuario->estudiante->update($estudianteUpdate);
            }

            if ($usuario->docente) {
                $usuario->docente->update([
                    'numero_empleado' => strtoupper(trim($request->input('numero_empleado'))),
                    'puesto' => $request->filled('puesto') ? trim($request->input('puesto')) : $usuario->docente->puesto,
                    'especialidad' => $request->filled('especialidad') ? trim($request->input('especialidad')) : null,
                    'cedula_profesional' => $request->filled('cedula_profesional') ? trim($request->input('cedula_profesional')) : null,
                ]);
            }

            if ($usuario->citaProfesional) {
                $modalidad = strtoupper($request->input('modalidades_permitidas', $usuario->citaProfesional->modalidades_permitidas));
                if (!in_array($modalidad, ['PRESENCIAL', 'VIRTUAL', 'AMBAS'], true)) {
                    $modalidad = $usuario->citaProfesional->modalidades_permitidas;
                }

                $usuario->citaProfesional->update([
                    'cargo' => trim($request->input('cargo', $usuario->citaProfesional->cargo)),
                    'cedula_profesional' => $request->filled('cedula_profesional') ? trim($request->input('cedula_profesional')) : null,
                    'lugar_atencion' => $request->filled('lugar_atencion') ? trim($request->input('lugar_atencion')) : null,
                    'modalidades_permitidas' => $modalidad,
                    'permite_citas' => $request->has('permite_citas') ? $request->boolean('permite_citas') : $usuario->citaProfesional->permite_citas,
                ]);
            }
        });

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario [{$usuario->nombre} {$usuario->apellido_paterno}] actualizado correctamente.");
    }

    /**
     * Cambia de forma directa y atómica el estado operativo de un usuario (ACTIVO, INACTIVO, BLOQUEADO, PENDIENTE, RECHAZADO).
     *
     * @param Request $request Petición con el nuevo estado validado.
     * @param int $usuarioId Identificador del usuario.
     * @return RedirectResponse|JsonResponse Redirección o respuesta JSON si es solicitada vía AJAX.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el usuario no existe.
     */
    public function cambiarEstado(Request $request, int $usuarioId): RedirectResponse|JsonResponse
    {
        $request->validate([
            'estado' => ['required', 'string', Rule::in(['ACTIVO', 'INACTIVO', 'BLOQUEADO', 'PENDIENTE', 'RECHAZADO'])],
        ]);

        $usuario = User::with('rol')->findOrFail($usuarioId);
        $nuevoEstado = $request->input('estado');

        // Protección contra auto-bloqueo o auto-desactivación del propio admin
        if (auth()->id() === $usuario->usuario_id && in_array($nuevoEstado, ['INACTIVO', 'BLOQUEADO', 'RECHAZADO'], true)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No puedes cambiar el estado de tu propia cuenta de Administrador.'], 422);
            }
            return back()->with('error', 'No puedes modificar el estado de tu propia cuenta de Administrador.');
        }

        // Protección contra dejar el sistema sin administradores activos
        if ($usuario->rol?->nombre === 'Administrador' && $nuevoEstado !== 'ACTIVO') {
            $otrosAdmins = User::whereHas('rol', fn ($r) => $r->where('nombre', 'Administrador'))
                ->where('estado', 'ACTIVO')
                ->where('usuario_id', '!=', $usuario->usuario_id)
                ->count();

            if ($otrosAdmins === 0) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'No es posible desactivar o bloquear al único Administrador activo del sistema.'], 422);
                }
                return back()->with('error', 'No es posible desactivar o bloquear al único Administrador activo del sistema.');
            }
        }

        $usuario->estado = $nuevoEstado;
        $usuario->save();

        $mensaje = "El estado del usuario [{$usuario->nombre} {$usuario->apellido_paterno}] fue cambiado a {$nuevoEstado}.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'nuevoEstado' => $nuevoEstado,
            ]);
        }

        return back()->with('success', $mensaje);
    }

    /**
     * Restablece la contraseña de acceso de un usuario, ya sea con una personalizada o generada al azar.
     *
     * @param Request $request Petición con posible custom_password y bandera de desbloqueo.
     * @param int $usuarioId Identificador del usuario.
     * @return RedirectResponse Redirección con flash informativo de la nueva clave.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el usuario no existe.
     */
    public function resetPassword(Request $request, int $usuarioId): RedirectResponse
    {
        $usuario = User::findOrFail($usuarioId);

        // Si se envió contraseña manual, validarla; de lo contrario generar una temporal aleatoria
        if ($request->filled('custom_password')) {
            $request->validate([
                'custom_password' => ['required', 'string', 'min:8'],
            ]);
            $nuevaPassword = $request->input('custom_password');
        } else {
            $nuevaPassword = 'Upmr!' . Str::random(8) . rand(10, 99);
        }

        $usuario->password = Hash::make($nuevaPassword);
        // Si estaba bloqueado, reactivarlo opcionalmente
        if ($request->boolean('desbloquear_cuenta') && $usuario->estado === 'BLOQUEADO') {
            $usuario->estado = 'ACTIVO';
        }
        $usuario->save();

        return back()->with('success_password_reset', [
            'nombre' => "{$usuario->nombre} {$usuario->apellido_paterno}",
            'email' => $usuario->email,
            'password' => $nuevaPassword,
        ]);
    }

    /**
     * Ejecuta la eliminación física o rechaza la baja si existen registros históricos institucionales.
     *
     * Audita dependencias en evaluaciones, bitácora emocional, citas clínicas,
     * asignaciones docentes, inscripciones y recompensas para preservar la confidencialidad
     * y trazabilidad institucional.
     *
     * @param int $usuarioId Identificador del usuario a retirar.
     * @return RedirectResponse Redirección con mensaje de resultado.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el usuario no existe.
     */
    public function destroy(int $usuarioId): RedirectResponse
    {
        $usuario = User::with([
            'rol',
            'estudiante',
            'docente',
            'citaProfesional',
        ])->findOrFail($usuarioId);

        // 1. Protección contra auto-eliminación
        if (auth()->id() === $usuario->usuario_id) {
            return back()->with('error', 'Por seguridad, no puedes eliminar tu propia cuenta de Administrador.');
        }

        // 2. Protección contra eliminar al último administrador
        if ($usuario->rol?->nombre === 'Administrador') {
            $otrosAdmins = User::whereHas('rol', fn ($r) => $r->where('nombre', 'Administrador'))
                ->where('usuario_id', '!=', $usuario->usuario_id)
                ->count();
            if ($otrosAdmins === 0) {
                return back()->with('error', 'No es posible eliminar al único Administrador activo del sistema.');
            }
        }

        // 3. Auditoría exhaustiva de dependencias históricas críticas
        $dependencias = [];

        if (EvaluacionIntento::where('usuario_id', $usuario->usuario_id)->exists()) {
            $dependencias[] = 'evaluaciones psicopedagógicas realizadas';
        }

        if (BitacoraEmocional::where('usuario_id', $usuario->usuario_id)->exists()) {
            $dependencias[] = 'entradas en la bitácora emocional';
        }

        if (CitaOrientacion::where('estudiante_id', $usuario->usuario_id)->exists()) {
            $dependencias[] = 'citas de orientación solicitadas como alumno';
        }

        if ($usuario->citaProfesional) {
            if (CitaOrientacion::where('profesional_id', $usuario->citaProfesional->profesional_id)->exists()) {
                $dependencias[] = 'citas u orientaciones psicológicas brindadas como orientador';
            }
            if (CitaDisponibilidad::where('profesional_id', $usuario->citaProfesional->profesional_id)->exists()) {
                $dependencias[] = 'horarios de disponibilidad clínica registrados';
            }
        }

        if (DocenteAsignacion::where('docente_id', $usuario->usuario_id)->exists()) {
            $dependencias[] = 'asignaturas o grupos asignados a su carga docente';
        }

        if (EstudianteInscripcion::where('estudiante_id', $usuario->usuario_id)->exists()) {
            $dependencias[] = 'inscripciones curriculares en grupos y materias';
        }

        if (RecompensaCanje::where('estudiante_id', $usuario->usuario_id)
            ->orWhere('resuelto_por', $usuario->usuario_id)
            ->orWhere('utilizado_por', $usuario->usuario_id)
            ->exists()) {
            $dependencias[] = 'canjes o validaciones en el catálogo de recompensas';
        }

        // Si existen dependencias, denegar eliminación física para proteger la trazabilidad clínica y académica
        if (!empty($dependencias)) {
            Log::warning("Intento de eliminación física denegado para usuario #{$usuario->usuario_id} ({$usuario->email}) por dependencias históricas: " . implode(', ', $dependencias));
            return back()->with('error', "No se puede eliminar físicamente la cuenta de [{$usuario->nombre} {$usuario->apellido_paterno}] porque conserva registros históricos (" . implode(', ', $dependencias) . "). Para resguardar la confidencialidad y la trazabilidad institucional, modifica su estado a INACTIVO o BLOQUEADO.");
        }

        // Si no existen dependencias históricas, eliminación física segura
        DB::transaction(function () use ($usuario) {
            if ($usuario->estudiante) {
                $usuario->estudiante->delete();
            }
            if ($usuario->docente) {
                $usuario->docente->delete();
            }
            if ($usuario->citaProfesional) {
                $usuario->citaProfesional->delete();
            }
            $usuario->roles()->detach();
            $usuario->delete();
        });

        Log::info("Usuario #{$usuarioId} ({$usuario->email}) eliminado de forma segura por el administrador #" . auth()->id());

        return redirect()->route('admin.usuarios.index')
            ->with('success', "La cuenta de [{$usuario->nombre} {$usuario->apellido_paterno}] sin dependencias históricas fue eliminada permanentemente del sistema.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterAlumnoRequest;
use App\Models\Estudiante;
use App\Models\ProgramaEducativo;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\NuevoAlumnoRegistradoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controlador de Registro Público de Aspirantes y Alumnos.
 *
 * Administra el formulario público de registro de aspirantes estudiantiles,
 * validación estricta de programas vigentes, asignación automática de plan
 * de estudios según cohorte y notificación a los administradores institucionales.
 */
class RegisterController extends Controller
{
    /**
     * Claves de las carreras permitidas en el registro público de alumnos.
     *
     * @var array<int, string>
     */
    public const CARRERAS_PERMITIDAS = ['IBT', 'IAS', 'IID', 'IFI', 'IIN', 'LAD', 'ISE'];

    /**
     * Muestra el formulario público para el registro de nuevos alumnos.
     *
     * @return View Vista con los programas educativos oficiales autorizados para admisión.
     */
    public function create(): View
    {
        $programas = ProgramaEducativo::query()
            ->where('activo', true)
            ->where('archivado', false)
            ->whereIn('clave', self::CARRERAS_PERMITIDAS)
            ->orderBy('nombre')
            ->get(['programa_id', 'nombre', 'clave']);

        return view('auth.register', ['programas' => $programas]);
    }

    /**
     * Procesa la solicitud de registro del alumno en estado PENDIENTE y notifica a los administradores.
     *
     * @param RegisterAlumnoRequest $request Form Request con validaciones de matrícula, correo institucional y carrera.
     * @return RedirectResponse Redirección al login con mensaje flash informativo.
     */
    public function store(RegisterAlumnoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rolAlumno = Rol::query()->where('nombre', 'Alumno')->where('activo', true)->firstOrFail();

        DB::transaction(function () use ($validated, $rolAlumno) {
            $user = User::create([
                'rol_id' => $rolAlumno->rol_id,
                'nombre' => $validated['nombre'],
                'apellido_paterno' => $validated['apellido_paterno'],
                'apellido_materno' => $validated['apellido_materno'] ?? null,
                'email' => $validated['email'],
                'password' => $validated['password'],
                'estado' => 'PENDIENTE',
            ]);

            $estudianteData = [
                'usuario_id' => $user->usuario_id,
                'programa_id' => $validated['programa_id'],
                'matricula' => $validated['matricula'],
                'cuatrimestre' => $validated['cuatrimestre'],
                'grupo' => $validated['grupo'],
                'permite_visualizacion' => false,
            ];

            // Determinación del plan de estudio correspondiente a su cohorte y programa sin ambigüedad
            $dummy = new Estudiante($estudianteData);
            $plan = $dummy->resolverPlanEstudio();
            if ($plan && Schema::hasColumn('estudiantes', 'plan_id')) {
                $estudianteData['plan_id'] = $plan->plan_id;
            }

            Estudiante::create($estudianteData);

            try {
                $programa = ProgramaEducativo::find($validated['programa_id']);
                $programaNombre = $programa ? "{$programa->nombre} ({$programa->clave})" : 'Programa Institucional';
                $rolAdmin = Rol::where('nombre', 'Administrador')->first();
                $admins = User::where('estado', 'ACTIVO')
                    ->where(function ($q) use ($rolAdmin) {
                        if ($rolAdmin) {
                            $q->where('rol_id', $rolAdmin->rol_id);
                        }
                        $q->orWhereHas('roles', fn ($r) => $r->where('nombre', 'Administrador'));
                    })
                    ->get();

                foreach ($admins as $admin) {
                    $admin->notify(new NuevoAlumnoRegistradoNotification(
                        alumnoNombre: "{$user->nombre} {$user->apellido_paterno}",
                        matricula: $validated['matricula'],
                        programaNombre: $programaNombre
                    ));
                }
            } catch (\Throwable $e) {
                Log::warning("No se pudo notificar a administradores sobre nuevo registro: {$e->getMessage()}");
            }
        });

        return redirect()->route('login')->with(
            'success',
            'Tu solicitud de registro fue recibida. Un administrador revisará tu información para activar tu cuenta.'
        );
    }
}

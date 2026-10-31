<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterAlumnoRequest;
use App\Models\Rol;
use App\Models\Estudiante;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /**
     * Claves de las carreras permitidas en el registro público de alumnos.
     */
    public const CARRERAS_PERMITIDAS = ['IBT', 'IAS', 'IID', 'IFI', 'LAD', 'ISE'];

    public function create()
    {
        $programas = \App\Models\ProgramaEducativo::query()
            ->where('activo', true)
            ->whereIn('clave', self::CARRERAS_PERMITIDAS)
            ->orderBy('nombre')
            ->get(['programa_id', 'nombre', 'clave']);

        return view('auth.register', ['programas' => $programas]);
    }

    public function store(RegisterAlumnoRequest $request)
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

            Estudiante::create([
                'usuario_id' => $user->usuario_id,
                'programa_id' => $validated['programa_id'],
                'matricula' => $validated['matricula'],
                'cuatrimestre' => $validated['cuatrimestre'] ?? null,
                'grupo' => $validated['grupo'] ?? null,
                'permite_visualizacion' => false,
            ]);
        });

        return redirect()->route('login')->with(
            'success',
            'Tu solicitud fue recibida. Serás notificado cuando un administrador la apruebe.'
        );
    }
}

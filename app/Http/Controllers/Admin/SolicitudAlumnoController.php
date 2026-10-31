<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SolicitudAlumnoController extends Controller
{
    private const ESTADOS = ['PENDIENTE', 'ACTIVO', 'RECHAZADO'];

    public function index(Request $request)
    {
        $estado = $request->query('estado', 'PENDIENTE');
        if (! in_array($estado, self::ESTADOS, true)) {
            $estado = 'PENDIENTE';
        }
        $buscar = trim((string) $request->query('buscar', ''));

        $solicitudes = User::query()
            ->with('estudiante.programa')
            ->whereHas('rol', fn ($query) => $query->where('nombre', 'Alumno'))
            ->whereHas('estudiante')
            ->where('estado', $estado)
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%'.addcslashes($buscar, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('nombre', 'like', $like)
                        ->orWhere('apellido_paterno', 'like', $like)
                        ->orWhere('apellido_materno', 'like', $like)
                        ->orWhereHas('estudiante', fn ($estudiante) => $estudiante->where('matricula', 'like', $like));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('usuario_id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.solicitudes.index', [
            'solicitudes' => $solicitudes,
            'estado' => $estado,
            'buscar' => $buscar,
            'estados' => self::ESTADOS,
        ]);
    }

    public function aprobar(Request $request, int $usuario)
    {
        return $this->cambiarEstado($request, $usuario, 'ACTIVO', 'La solicitud fue aprobada. El alumno ya puede iniciar sesión.');
    }

    public function rechazar(Request $request, int $usuario)
    {
        return $this->cambiarEstado($request, $usuario, 'RECHAZADO', 'La solicitud fue rechazada.');
    }

    private function cambiarEstado(Request $request, int $usuarioId, string $nuevoEstado, string $mensaje)
    {
        if ($usuarioId === $request->user()->usuario_id) {
            return back()->with('error', 'No puedes modificar tu propia cuenta desde este módulo.');
        }

        // La condición en el UPDATE hace atómica la transición PENDIENTE -> nuevo estado.
        $afectados = User::query()
            ->whereKey($usuarioId)
            ->where('estado', 'PENDIENTE')
            ->whereHas('rol', fn ($query) => $query->where('nombre', 'Alumno'))
            ->whereHas('estudiante')
            ->update(['estado' => $nuevoEstado]);

        if ($afectados === 0) {
            return back()->with('error', 'La solicitud no existe o ya fue atendida. No se realizaron cambios.');
        }

        return back()->with('success', $mensaje);
    }
}

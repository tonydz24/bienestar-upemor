<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\CitaOrientacion;
use App\Models\InstrumentoEvaluacion;
use App\Models\RecompensaCanje;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Dashboard principal del Alumno (Pantalla de Inicio).
     */
    public function index(Request $request)
    {
        $alumno = Auth::user()->load('estudiante.programa');
        $usuarioId = $alumno->usuario_id;

        $bitacoraDisponible = false;
        $registroHoy = null;
        $totalRegistrosEmocionales = 0;

        try {
            if (Schema::hasTable('bitacoras_emocionales') && Schema::hasTable('emociones')) {
                $bitacoraDisponible = true;
                $registroHoy = Auth::user()->bitacoras()
                    ->with('emocion')
                    ->whereDate('fecha', now()->toDateString())
                    ->first();
                $totalRegistrosEmocionales = Auth::user()->bitacoras()->count();
            }
        } catch (QueryException $e) {
            if ($e->getCode() === '42S02' || (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1146)) {
                $bitacoraDisponible = false;
                $registroHoy = null;
            } else {
                throw $e;
            }
        }

        // Próxima cita confirmada o solicitada
        $proximaCita = null;
        try {
            if (Schema::hasTable('citas_orientacion')) {
                $proximaCita = CitaOrientacion::with(['profesional.usuario'])
                    ->where('estudiante_id', $usuarioId)
                    ->where('estado', CitaOrientacion::ESTADO_CONFIRMADA)
                    ->where('fecha_cita', '>=', now()->toDateString())
                    ->orderBy('fecha_cita', 'asc')
                    ->orderBy('hora_inicio', 'asc')
                    ->first();

                if (!$proximaCita) {
                    $proximaCita = CitaOrientacion::with(['profesional.usuario'])
                        ->where('estudiante_id', $usuarioId)
                        ->where('estado', CitaOrientacion::ESTADO_SOLICITADA)
                        ->where('fecha_cita', '>=', now()->toDateString())
                        ->orderBy('fecha_cita', 'asc')
                        ->orderBy('hora_inicio', 'asc')
                        ->first();
                }
            }
        } catch (\Throwable) {
            $proximaCita = null;
        }

        // Evaluaciones disponibles para contestar (activas, con versión publicada y sin bloqueo de espera)
        $evaluacionesDisponiblesConteo = 0;
        try {
            if (Schema::hasTable('evaluaciones_instrumentos')) {
                $instrumentos = InstrumentoEvaluacion::where('activo', true)
                    ->whereHas('versiones', fn ($v) => $v->where('estado', 'PUBLICADA'))
                    ->get();

                foreach ($instrumentos as $inst) {
                    if ($inst->diasRestantesParaUsuario($usuarioId) === 0) {
                        $evaluacionesDisponiblesConteo++;
                    }
                }
            }
        } catch (\Throwable) {
            $evaluacionesDisponiblesConteo = 0;
        }

        // Beneficios digitales pendientes por utilizar (cupones activos)
        $beneficiosPendientesConteo = 0;
        try {
            if (Schema::hasTable('recompensas_canjes')) {
                $beneficiosPendientesConteo = RecompensaCanje::where('estudiante_id', $usuarioId)
                    ->where('estado', RecompensaCanje::ESTADO_CANJEADA)
                    ->count();
            }
        } catch (\Throwable) {
            $beneficiosPendientesConteo = 0;
        }

        $saldoPuntos = (int) ($alumno->estudiante?->saldo_puntos ?? 0);

        // Materias académicas inscritas aprobadas
        $materiasInscritasConteo = 0;
        try {
            if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
                $materiasInscritasConteo = \App\Models\EstudianteAsignaturaInscripcion::where('estudiante_id', $usuarioId)
                    ->where('estado', 'APROBADA')
                    ->count();
            }
        } catch (\Throwable) {
            $materiasInscritasConteo = 0;
        }

        return view('alumno.dashboard', [
            'alumno' => $alumno,
            'registroHoy' => $registroHoy,
            'totalRegistrosEmocionales' => $totalRegistrosEmocionales,
            'bitacoraDisponible' => $bitacoraDisponible,
            'proximaCita' => $proximaCita,
            'evaluacionesDisponiblesConteo' => $evaluacionesDisponiblesConteo,
            'beneficiosPendientesConteo' => $beneficiosPendientesConteo,
            'materiasInscritasConteo' => $materiasInscritasConteo,
            'saldoPuntos' => $saldoPuntos,
        ]);
    }

    /**
     * Vista informativa de Evaluaciones disponibles (Próximamente).
     */
    public function evaluaciones(Request $request)
    {
        $alumno = Auth::user()->load('estudiante.programa');

        $evaluaciones = [
            [
                'codigo' => 'GAD-7',
                'nombre' => 'Escala de Ansiedad Generalizada',
                'descripcion' => 'Instrumento breve de 7 preguntas para identificar síntomas de ansiedad en las últimas dos semanas.',
                'duracion' => '3-5 min',
                'preguntas' => 7,
                'categoria' => 'Ansiedad',
            ],
            [
                'codigo' => 'PHQ-9',
                'nombre' => 'Cuestionario de Salud del Paciente',
                'descripcion' => 'Herramienta de 9 reactivos orientada a reconocer el estado anímico y síntomas de depresión.',
                'duracion' => '4-6 min',
                'preguntas' => 9,
                'categoria' => 'Estado de ánimo',
            ],
            [
                'codigo' => 'PSS-10',
                'nombre' => 'Escala de Estrés Percibido',
                'descripcion' => 'Evaluación de 10 reactivos sobre el nivel de sobrecarga y control de situaciones cotidianas.',
                'duracion' => '5-7 min',
                'preguntas' => 10,
                'categoria' => 'Estrés',
            ],
            [
                'codigo' => 'WHO-5',
                'nombre' => 'Índice de Bienestar General (OMS)',
                'descripcion' => 'Medida positiva y respetuosa del bienestar psicológico y la vitalidad general.',
                'duracion' => '2-3 min',
                'preguntas' => 5,
                'categoria' => 'Bienestar general',
            ],
        ];

        return view('alumno.evaluaciones', [
            'alumno' => $alumno,
            'evaluaciones' => $evaluaciones,
        ]);
    }
}

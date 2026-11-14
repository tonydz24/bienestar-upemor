<?php

namespace App\Services\DatosSinteticos;

use App\Models\BitacoraEmocional;
use App\Models\CitaOrientacion;
use App\Models\Estudiante;
use App\Models\EstudianteAsignaturaInscripcion;
use App\Models\EstudianteInscripcion;
use App\Models\EstudiantePlanHistorial;
use App\Models\EvaluacionConsentimiento;
use App\Models\EvaluacionIntento;
use App\Models\EvaluacionRespuesta;
use App\Models\LoginAcceso;
use App\Models\RecompensaCanje;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LimpiezaIncremento22Service
{
    public const CORREO_PATTERN = 'demo.i22.%@upemor.test';
    public const IP_DEMO = '127.0.0.22';

    /**
     * Remueve exclusivamente los datos sintéticos creados por el Incremento 22.
     * Preserva íntegramente las 22 cuentas base y los registros históricos.
     */
    public function ejecutarLimpieza(): array
    {
        return DB::transaction(function () {
            // 1. Identificar usuarios sintéticos de Incremento 22
            $demoUsers = User::where('email', 'like', self::CORREO_PATTERN)->get();
            $demoUserIds = $demoUsers->pluck('usuario_id')->toArray();

            // Identificar también por matrícula exclusiva de Incremento 22
            $demoEstudiantes = Estudiante::where('matricula', 'like', 'SYNTH22_%')->pluck('usuario_id')->toArray();
            $demoUserIds = array_values(array_unique(array_merge($demoUserIds, $demoEstudiantes)));

            $conteoEliminados = [
                'usuarios' => count($demoUserIds),
                'estudiantes' => 0,
                'bitacoras' => 0,
                'citas' => 0,
                'canjes' => 0,
                'evaluaciones_respuestas' => 0,
                'evaluaciones_intentos' => 0,
                'evaluaciones_consentimientos' => 0,
                'inscripciones' => 0,
                'asignaturas_inscripciones' => 0,
                'historial_planes' => 0,
                'login_accesos' => 0,
            ];

            if (!empty($demoUserIds)) {
                // Evaluaciones
                $intentosIds = EvaluacionIntento::whereIn('usuario_id', $demoUserIds)->pluck('intento_id')->toArray();
                if (!empty($intentosIds)) {
                    $conteoEliminados['evaluaciones_respuestas'] = EvaluacionRespuesta::whereIn('intento_id', $intentosIds)->delete();
                }
                $conteoEliminados['evaluaciones_intentos'] = EvaluacionIntento::whereIn('usuario_id', $demoUserIds)->delete();
                $conteoEliminados['evaluaciones_consentimientos'] = EvaluacionConsentimiento::whereIn('usuario_id', $demoUserIds)->delete();

                // Bitácoras y Citas
                $conteoEliminados['bitacoras'] = BitacoraEmocional::whereIn('usuario_id', $demoUserIds)->delete();
                $conteoEliminados['citas'] = CitaOrientacion::whereIn('estudiante_id', $demoUserIds)->delete();

                // Recompensas
                $conteoEliminados['canjes'] = RecompensaCanje::whereIn('estudiante_id', $demoUserIds)->delete();

                // Inscripciones y planes
                if (\Illuminate\Support\Facades\Schema::hasTable('estudiante_asignatura_inscripciones')) {
                    $conteoEliminados['asignaturas_inscripciones'] = EstudianteAsignaturaInscripcion::whereIn('estudiante_id', $demoUserIds)->delete();
                }
                $conteoEliminados['inscripciones'] = EstudianteInscripcion::whereIn('estudiante_id', $demoUserIds)->delete();

                if (\Illuminate\Support\Facades\Schema::hasTable('estudiante_plan_historial')) {
                    $conteoEliminados['historial_planes'] = EstudiantePlanHistorial::whereIn('estudiante_id', $demoUserIds)->delete();
                }

                // Expedientes y usuarios
                $conteoEliminados['estudiantes'] = Estudiante::whereIn('usuario_id', $demoUserIds)->delete();
                User::whereIn('usuario_id', $demoUserIds)->delete();
            }

            // Accesos de auditoría de prueba
            $conteoEliminados['login_accesos'] = LoginAcceso::where('ip_address', self::IP_DEMO)
                ->orWhere('email', 'like', self::CORREO_PATTERN)
                ->delete();

            Log::info('Limpieza de datos sintéticos Incremento 22 ejecutada.', $conteoEliminados);

            return $conteoEliminados;
        });
    }
}

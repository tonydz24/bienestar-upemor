<?php

namespace App\Services\Academico;

use App\Models\PeriodoAcademico;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;

class PeriodoOperativoService
{
    /**
     * Resuelve el periodo operativo activo para el flujo institucional actual.
     * Aplica una jerarquía determinista y libre de ambigüedad, eliminando consultas ciegas como `where('activo', true)->first()`.
     */
    public function resolverPeriodoOperativo(?int $periodoId = null): ?PeriodoAcademico
    {
        // 1. Si se provee un ID explícito (vía request o parámetro), resolverlo si existe
        if ($periodoId) {
            $periodo = PeriodoAcademico::find($periodoId);
            if ($periodo) {
                return $periodo;
            }
        }

        // 2. Si el usuario seleccionó un periodo operativo en la sesión activa
        $sessionPeriodoId = Session::get('periodo_operativo_id');
        if ($sessionPeriodoId) {
            $periodo = PeriodoAcademico::find($sessionPeriodoId);
            if ($periodo && $periodo->activo) {
                return $periodo;
            }
        }

        // 3. Resolución determinista entre periodos marcados con `activo = 1`
        $periodosActivos = PeriodoAcademico::where('activo', true)->get();

        if ($periodosActivos->count() === 1) {
            return $periodosActivos->first();
        }

        if ($periodosActivos->count() > 1) {
            // REGLA INSTITUCIONAL DETERMINISTA:
            // 1. Si existe un periodo activo que está VIGENTE por calendario Y registra actividad académica real,
            // ese periodo es indiscutiblemente el oficial operativo.
            $vigenteConActividad = $periodosActivos->first(function ($p) {
                return $p->estaVigentePorCalendario() && ($p->grupos()->exists() || $p->asignaciones()->exists() || $p->inscripciones()->exists());
            });

            if ($vigenteConActividad) {
                return $vigenteConActividad;
            }

            // 2. Si el vigente por calendario no tiene actividad pero otro periodo activo sí (desfase histórico de carga inicial),
            // priorizar determinísticamente el que tiene actividad para no romper flujos en entornos de demostración.
            $conActividad = $periodosActivos->first(function ($p) {
                return $p->grupos()->exists() || $p->asignaciones()->exists() || $p->inscripciones()->exists();
            });

            if ($conActividad) {
                return $conActividad;
            }

            // 3. Si ninguno tiene actividad, priorizar el vigente por calendario
            $vigenteSinActividad = $periodosActivos->first(function ($p) {
                return $p->estaVigentePorCalendario();
            });

            if ($vigenteSinActividad) {
                return $vigenteSinActividad;
            }

            // 4. Si todos o ninguno tienen actividad y ninguno coincide con hoy, seleccionar determinísticamente por fecha_inicio ascendente
            return $periodosActivos->sortBy('fecha_inicio')->first();
        }

        // 4. Si ningún periodo tiene activo = 1, buscar el vigente por calendario institucional (hoy)
        $vigente = PeriodoAcademico::obtenerVigente();
        if ($vigente) {
            return $vigente;
        }

        // 5. Último recurso determinista: el periodo más reciente por fecha de inicio
        return PeriodoAcademico::orderByDesc('fecha_inicio')->first();
    }

    /**
     * Retorna todos los periodos activos ordenados cronológicamente.
     */
    public function getPeriodosActivos(): Collection
    {
        return PeriodoAcademico::where('activo', true)->orderBy('fecha_inicio')->get();
    }

    /**
     * Retorna los periodos consultables (históricos y activos), ordenados del más reciente al más antiguo.
     */
    public function getPeriodosConsultables(): Collection
    {
        return PeriodoAcademico::orderByDesc('fecha_inicio')->get();
    }

    /**
     * Retorna los periodos disponibles para administración, enriquecidos con métricas de actividad.
     */
    public function getPeriodosAdministrables(): Collection
    {
        return PeriodoAcademico::withCount(['grupos', 'asignaciones', 'inscripciones'])
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    /**
     * Determina si un periodo específico es el que actualmente opera como el oficial activo.
     */
    public function esOperativo(PeriodoAcademico $periodo): bool
    {
        $operativo = $this->resolverPeriodoOperativo();
        return $operativo && $operativo->periodo_id === $periodo->periodo_id;
    }

    /**
     * Permite fijar un periodo operativo específico en la sesión de navegación del usuario.
     */
    public function seleccionarPeriodo(int $periodoId): ?PeriodoAcademico
    {
        $periodo = PeriodoAcademico::findOrFail($periodoId);
        Session::put('periodo_operativo_id', $periodo->periodo_id);
        return $periodo;
    }

    /**
     * Limpia la selección explícita de periodo en sesión, retornando a la resolución automática.
     */
    public function limpiarPeriodoSeleccionado(): void
    {
        Session::forget('periodo_operativo_id');
    }
}


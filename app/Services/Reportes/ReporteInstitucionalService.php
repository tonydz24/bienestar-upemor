<?php

namespace App\Services\Reportes;

use App\Models\BitacoraEmocional;
use App\Models\CitaOrientacion;
use App\Models\Emocion;
use App\Models\Estudiante;
use App\Models\EvaluacionIntento;
use App\Models\InstrumentoEvaluacion;
use App\Models\LoginAcceso;
use App\Models\ProgramaEducativo;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Models\RecompensaCatalogo;
use App\Models\Rol;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Servicio Central de Generación y Agregación de Reportes Institucionales.
 *
 * Consolida los 6 reportes analíticos de Bienestar UPEMOR:
 * 1. Emociones registradas (barras y circular).
 * 2. Citas atendidas por profesional/mes (barras comparativas y dona).
 * 3. Alumnos evaluados con semaforización diagnóstica (barras y pastel).
 * 4. Canje de beneficios por asignatura y categoría (barras horizontales y dona).
 * 5. Usuarios activos vs inactivos por carrera y rol (barras y dona institucional).
 * 6. Historial de accesos por día y rol (líneas cronológicas y barras de éxito/fallo).
 */
class ReporteInstitucionalService
{
    /**
     * Inicializa el servicio inyectando el generador de gráficas estadísticas.
     *
     * @param ReporteGraficaService|null $graficaService Servicio SVG/PNG de renderizado gráfico institucional.
     */
    public function __construct(
        protected ?ReporteGraficaService $graficaService = null
    ) {
        $this->graficaService = $graficaService ?? new ReporteGraficaService();
    }

    /**
     * REPORTE 1: Número y porcentaje de emociones registradas.
     * Exportación: PDF (Tabla + Gráfica de barras + Gráfica circular).
     */
    /**
     * REPORTE 1: Número y porcentaje de emociones registradas (FN.15).
     * Garantiza catálogo institucional completo de 6 emociones (Alegría, Calma, Ansiedad, Estrés, Tristeza, Enojo).
     * Las emociones con 0 registros conservan total=0, 0% e intensidad_promedio=null («Sin registros»).
     */
    public function reporteEmocionesRegistradas(array $filtros): array
    {
        // 1. Obtener catálogo institucional de emociones activas
        $emocionesQuery = Emocion::activas();
        if (!empty($filtros['emocion_id'])) {
            $emocionesQuery->where('emocion_id', $filtros['emocion_id']);
        }
        $catalogoEmociones = $emocionesQuery->get();

        // 2. Construir consulta con filtros sobre bitácoras emocionales
        $query = BitacoraEmocional::query();

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('bitacoras_emocionales.fecha', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('bitacoras_emocionales.fecha', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['emocion_id'])) {
            $query->where('bitacoras_emocionales.emocion_id', $filtros['emocion_id']);
        }

        if (!empty($filtros['programa_id'])) {
            $query->join('estudiantes', 'bitacoras_emocionales.usuario_id', '=', 'estudiantes.usuario_id')
                  ->where('estudiantes.programa_id', $filtros['programa_id']);
        }

        $totalRegistros = (clone $query)->count();

        // Agrupar conteos e intensidad por emoción
        $agrupados = $query->select(
                'bitacoras_emocionales.emocion_id',
                DB::raw('COUNT(bitacoras_emocionales.bitacora_id) as total'),
                DB::raw('ROUND(AVG(bitacoras_emocionales.intensidad), 2) as intensidad_promedio')
            )
            ->groupBy('bitacoras_emocionales.emocion_id')
            ->get()
            ->keyBy('emocion_id');

        // 3. Mapear garantizando todas las emociones del catálogo
        $datos = $catalogoEmociones->map(function ($emocion) use ($agrupados, $totalRegistros) {
            $registro = $agrupados->get($emocion->emocion_id);
            $total = $registro ? (int) $registro->total : 0;
            $porcentaje = ($totalRegistros > 0 && $total > 0) ? round(($total / $totalRegistros) * 100, 2) : 0.0;
            $intensidad = ($registro && $total > 0 && $registro->intensidad_promedio !== null)
                ? round((float) $registro->intensidad_promedio, 2)
                : null; // null representa explícitamente "Sin registros"

            $obj = new \stdClass();
            $obj->emocion_id = $emocion->emocion_id;
            $obj->emocion_nombre = $emocion->nombre;
            $obj->codigo_color = Emocion::colorInstitucional($emocion->nombre);
            $obj->total = $total;
            $obj->porcentaje = $porcentaje;
            $obj->intensidad_promedio = $intensidad;
            $obj->es_positiva = in_array(mb_strtolower($emocion->nombre), ['alegría', 'alegria', 'calma', 'gratitud', 'entusiasmo', 'tranquilidad']);

            return $obj;
        })
        ->sort(function ($a, $b) {
            if ($a->total === $b->total) {
                return strcmp($a->emocion_nombre, $b->emocion_nombre);
            }
            return $b->total <=> $a->total;
        })
        ->values();

        $emocionesObservadas = $datos->where('total', '>', 0)->count();
        $emocionPreponderante = $datos->firstWhere('total', '>', 0)?->emocion_nombre ?? 'Sin registros';

        // Generar gráficas vectoriales del lado del servidor para PDF e interfaz
        // Gráfica 1: Dona institucional de distribución porcentual
        $svgDonut = $this->graficaService->generarDonutSvg($datos, 190);
        // Gráfica 2: Barras de intensidad media por emoción (escala 1 a 5)
        $svgIntensidad = $this->graficaService->generarBarrasIntensidadSvg($datos, 540, 32);
        // Compatibilidad previa
        $svgBarras = $this->graficaService->generarBarrasHorizontalesSvg($datos, 520, 30);

        return [
            'tipo' => 'emociones_registradas',
            'titulo' => 'Número y Porcentaje de Emociones Registradas',
            'total_general' => $totalRegistros,
            'emociones_totales' => $datos->count(),
            'emociones_observadas' => $emocionesObservadas,
            'emocion_preponderante' => $emocionPreponderante,
            'datos' => $datos,
            'svg_donut' => $svgDonut,
            'svg_donut_data' => $this->graficaService->svgDataUri($svgDonut),
            'svg_intensidad' => $svgIntensidad,
            'svg_intensidad_data' => $this->graficaService->svgDataUri($svgIntensidad),
            'svg_barras' => $svgBarras,
            'svg_barras_data' => $this->graficaService->svgDataUri($svgBarras),
            'filtros' => $filtros,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * REPORTE 2: Emociones por Programa Educativo.
     * Exportación: PDF y Excel (Tabla comparativa + Gráfica barras agrupadas + Gráfica circular).
     */
    public function reporteEmocionesPorPrograma(array $filtros): array
    {
        $query = BitacoraEmocional::query()
            ->join('emociones', 'bitacoras_emocionales.emocion_id', '=', 'emociones.emocion_id')
            ->join('estudiantes', 'bitacoras_emocionales.usuario_id', '=', 'estudiantes.usuario_id')
            ->join('programas_educativos', 'estudiantes.programa_id', '=', 'programas_educativos.programa_id');

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('bitacoras_emocionales.fecha', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('bitacoras_emocionales.fecha', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['programa_id'])) {
            $query->where('programas_educativos.programa_id', $filtros['programa_id']);
        }

        if (!empty($filtros['emocion_id'])) {
            $query->where('emociones.emocion_id', $filtros['emocion_id']);
        }

        $totalRegistros = (clone $query)->count();

        $desglose = $query->select(
                'programas_educativos.programa_id',
                'programas_educativos.nombre as programa_nombre',
                'programas_educativos.clave as siglas',
                'emociones.emocion_id',
                'emociones.nombre as emocion_nombre',
                'emociones.color_hex as codigo_color',
                DB::raw('COUNT(bitacoras_emocionales.bitacora_id) as total')
            )
            ->groupBy(
                'programas_educativos.programa_id',
                'programas_educativos.nombre',
                'programas_educativos.clave',
                'emociones.emocion_id',
                'emociones.nombre',
                'emociones.color_hex'
            )
            ->orderBy('programas_educativos.nombre')
            ->orderBy('total', 'desc')
            ->get();

        // Agrupación por programa educativo con subtotales
        $programas = [];
        foreach ($desglose as $item) {
            $pId = $item->programa_id;
            $color = Emocion::colorInstitucional($item->emocion_nombre);
            if (!isset($programas[$pId])) {
                $programas[$pId] = [
                    'programa_id' => $pId,
                    'nombre' => $item->programa_nombre,
                    'siglas' => $item->siglas,
                    'total_programa' => 0,
                    'emociones' => [],
                ];
            }
            $programas[$pId]['total_programa'] += $item->total;
            $programas[$pId]['emociones'][] = [
                'emocion' => $item->emocion_nombre,
                'codigo_color' => $color,
                'es_positiva' => in_array(mb_strtolower($item->emocion_nombre), ['alegría', 'alegria', 'calma', 'gratitud', 'entusiasmo', 'tranquilidad']),
                'cantidad' => $item->total,
                'porcentaje_programa' => 0, // Se calcula abajo
            ];
        }

        foreach ($programas as &$p) {
            foreach ($p['emociones'] as &$e) {
                $e['porcentaje_programa'] = $p['total_programa'] > 0
                    ? round(($e['cantidad'] / $p['total_programa']) * 100, 1)
                    : 0;
            }
            $p['porcentaje_global'] = $totalRegistros > 0
                ? round(($p['total_programa'] / $totalRegistros) * 100, 1)
                : 0;

            $itemsGrafica = array_map(function ($e) {
                return [
                    'emocion_nombre' => $e['emocion'],
                    'total' => $e['cantidad'],
                    'porcentaje' => $e['porcentaje_programa'],
                    'codigo_color' => $e['codigo_color'],
                ];
            }, $p['emociones']);

            $p['svg_barras'] = $this->graficaService->generarBarrasHorizontalesSvg($itemsGrafica, 650, 26);
            $p['svg_barras_data'] = $this->graficaService->svgDataUri($p['svg_barras']);
        }

        $programasList = array_values($programas);
        // Gráfica 1: Barras agrupadas comparativas por programa educativo
        $svgAgrupadas = $this->graficaService->generarBarrasAgrupadasProgramaSvg($programasList, 680, 260);
        // Gráfica 2: Barras apiladas 100% de distribución interna por programa
        $svgApiladas = $this->graficaService->generarBarrasApiladasSvg($programasList, 680, 36);

        return [
            'tipo' => 'emociones_por_programa',
            'titulo' => 'Emociones Registradas por Programa Educativo',
            'total_general' => $totalRegistros,
            'programas' => $programasList,
            'desglose' => $desglose,
            'svg_agrupadas' => $svgAgrupadas,
            'svg_agrupadas_data' => $this->graficaService->svgDataUri($svgAgrupadas),
            'svg_apiladas' => $svgApiladas,
            'svg_apiladas_data' => $this->graficaService->svgDataUri($svgApiladas),
            'filtros' => $filtros,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * REPORTE 3: Historial de bitácoras y citas por alumno.
     * Exportación: PDF y Excel (Tabla individual cronológica).
     */
    public function reporteHistorialAlumno(array $filtros): array
    {
        $estudiante = null;
        $historial = collect();

        if (!empty($filtros['estudiante_id'])) {
            $estudiante = Estudiante::with(['usuario', 'programaEducativo'])->find($filtros['estudiante_id']);
            if (!$estudiante) {
                // Intentar buscar por usuario_id
                $estudiante = Estudiante::with(['usuario', 'programaEducativo'])
                    ->where('usuario_id', $filtros['estudiante_id'])
                    ->first();
            }
            if (!$estudiante) {
                // Buscar usuario con rol Alumno (rol_id = 3 o con rol Alumno)
                $u = User::where(function ($q) {
                    $q->where('rol_id', 3)->orWhereHas('roles', fn ($r) => $r->where('nombre', 'Alumno'));
                })->find($filtros['estudiante_id']);

                if ($u) {
                    $estudiante = new Estudiante([
                        'usuario_id' => $u->usuario_id,
                        'matricula' => 'S/M',
                    ]);
                    $estudiante->setRelation('usuario', $u);
                }
            }
        }

        if ($estudiante) {
            $usuarioId = $estudiante->usuario_id;
            $tipoRegistro = $filtros['tipo_registro'] ?? 'TODOS';

            // 1. Bitácoras
            if (in_array($tipoRegistro, ['TODOS', 'BITACORA'])) {
                $bitacoraQuery = BitacoraEmocional::with('emocion')
                    ->where('usuario_id', $usuarioId);

                if (!empty($filtros['fecha_inicio'])) {
                    $bitacoraQuery->whereDate('fecha', '>=', $filtros['fecha_inicio']);
                }
                if (!empty($filtros['fecha_fin'])) {
                    $bitacoraQuery->whereDate('fecha', '<=', $filtros['fecha_fin']);
                }

                $bitacoras = $bitacoraQuery->get()->map(function ($b) use ($estudiante) {
                    $permiteVisualizacion = (bool) ($estudiante?->permite_visualizacion);
                    $detalle = "Intensidad: {$b->intensidad}/5";
                    if ($b->descripcion) {
                        $detalle .= $permiteVisualizacion
                            ? " — {$b->descripcion}"
                            : " — [Contenido protegido por privacidad del estudiante]";
                    }
                    return [
                        'fecha_raw' => $b->fecha ? Carbon::parse($b->fecha)->toIso8601String() : $b->created_at->toIso8601String(),
                        'fecha_formateada' => $b->fecha ? Carbon::parse($b->fecha)->format('d/m/Y') : $b->created_at->format('d/m/Y'),
                        'tipo' => 'Bitácora Emocional',
                        'emocion_evento' => $b->emocion?->nombre ?? 'No especificada',
                        'detalle' => $detalle,
                        'estado' => 'Registrada',
                        'observaciones' => $b->observaciones ?: 'Sin observaciones adicionales',
                        'intensidad' => (float) $b->intensidad,
                    ];
                });

                $historial = $historial->merge($bitacoras);
            }

            // 2. Citas
            $citasCollection = collect();
            if (in_array($tipoRegistro, ['TODOS', 'CITA'])) {
                $citasQuery = CitaOrientacion::with('profesional')
                    ->where('estudiante_id', $usuarioId);

                if (!empty($filtros['fecha_inicio'])) {
                    $citasQuery->whereDate('fecha_cita', '>=', $filtros['fecha_inicio']);
                }
                if (!empty($filtros['fecha_fin'])) {
                    $citasQuery->whereDate('fecha_cita', '<=', $filtros['fecha_fin']);
                }
                if (!empty($filtros['estado_cita'])) {
                    $citasQuery->where('estado', $filtros['estado_cita']);
                }

                $citasCollection = $citasQuery->get()->map(function ($c) {
                    $profesionalNombre = $c->profesional ? ($c->profesional->nombre . ' ' . $c->profesional->primer_apellido) : 'Orientador Asignado';
                    return [
                        'fecha_raw' => $c->fecha_cita ? Carbon::parse($c->fecha_cita)->toIso8601String() : $c->created_at->toIso8601String(),
                        'fecha_formateada' => $c->fecha_cita ? Carbon::parse($c->fecha_cita)->format('d/m/Y') . ' (' . substr($c->hora_inicio, 0, 5) . ')' : $c->created_at->format('d/m/Y'),
                        'tipo' => 'Cita de Orientación',
                        'emocion_evento' => "Modalidad: {$c->modalidad}",
                        'detalle' => "Atendido por: {$profesionalNombre}" . ($c->motivo_consulta ? " — {$c->motivo_consulta}" : ''),
                        'estado' => $c->estado,
                        'observaciones' => $c->lugar_o_enlace ?: 'Ubicación estándar de orientación',
                    ];
                });

                $historial = $historial->merge($citasCollection);
            }

            // Ordenar cronológicamente descendente
            $historial = $historial->sortByDesc('fecha_raw')->values();

            // Gráfica 1: Línea temporal de intensidad emocional (cronológica ascendente)
            $puntosIntensidad = (isset($bitacoras) ? $bitacoras : collect())
                ->sortBy('fecha_raw')
                ->values()
                ->map(fn($b) => ['fecha' => $b['fecha_formateada'], 'valor' => $b['intensidad']]);
            $svgLineaTiempo = $this->graficaService->generarLineaTemporalSvg($puntosIntensidad, 'Evolución de Intensidad Emocional', 'Intensidad', 1, 5, 640, 220);

            // Gráfica 2: Barras de citas de orientación por estado
            $conteoCitasEstados = $citasCollection->countBy('estado')->toArray();
            if (empty($conteoCitasEstados)) {
                $conteoCitasEstados = ['PROGRAMADA' => 0, 'CONFIRMADA' => 0, 'ATENDIDA' => 0, 'CANCELADA' => 0];
            }
            $svgBarrasCitas = $this->graficaService->generarBarrasCitasSvg($conteoCitasEstados, 480, 200);
        } else {
            $svgLineaTiempo = $this->graficaService->generarLineaTemporalSvg([], 'Evolución de Intensidad Emocional', 'Intensidad', 1, 5, 640, 220);
            $svgBarrasCitas = $this->graficaService->generarBarrasCitasSvg([], 480, 200);
        }

        return [
            'tipo' => 'historial_alumno',
            'titulo' => 'Historial Individual de Bitácoras y Citas',
            'estudiante' => $estudiante,
            'historial' => $historial,
            'total_registros' => $historial->count(),
            'svg_linea_tiempo' => $svgLineaTiempo,
            'svg_linea_tiempo_data' => $this->graficaService->svgDataUri($svgLineaTiempo),
            'svg_barras_citas' => $svgBarrasCitas,
            'svg_barras_citas_data' => $this->graficaService->svgDataUri($svgBarrasCitas),
            'filtros' => $filtros,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * REPORTE 4: Premios más canjeados.
     * Exportación: PDF y Excel (Tabla con canjes, puntos, estados y %).
     */
    public function reportePremiosCanjeados(array $filtros): array
    {
        $query = RecompensaCanje::query()
            ->join('recompensas_activadas', 'recompensas_canjes.activacion_id', '=', 'recompensas_activadas.activacion_id')
            ->join('recompensas_catalogo', 'recompensas_activadas.recompensa_id', '=', 'recompensas_catalogo.recompensa_id');

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('recompensas_canjes.created_at', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('recompensas_canjes.created_at', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['recompensa_id'])) {
            $query->where('recompensas_catalogo.recompensa_id', $filtros['recompensa_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('recompensas_canjes.estado', $filtros['estado']);
        }

        $totalCanjes = (clone $query)->count();

        $datos = $query->select(
                'recompensas_catalogo.recompensa_id',
                'recompensas_catalogo.nombre as recompensa_titulo',
                'recompensas_catalogo.costo_puntos_base',
                DB::raw('COUNT(recompensas_canjes.canje_id) as total_canjes'),
                DB::raw("SUM(CASE WHEN recompensas_canjes.estado IN ('UTILIZADA', 'APROBADO', 'CANJEADA') THEN 1 ELSE 0 END) as canjes_exitosos"),
                DB::raw("SUM(CASE WHEN recompensas_canjes.estado IN ('RECHAZADO', 'ANULADA', 'CANCELADO') THEN 1 ELSE 0 END) as canjes_rechazados"),
                DB::raw("SUM(CASE WHEN recompensas_canjes.estado IN ('SOLICITADO') THEN 1 ELSE 0 END) as canjes_pendientes"),
                DB::raw('SUM(recompensas_canjes.puntos_canjeados) as puntos_totales_canjeados')
            )
            ->groupBy(
                'recompensas_catalogo.recompensa_id',
                'recompensas_catalogo.nombre',
                'recompensas_catalogo.costo_puntos_base'
            )
            ->orderBy('total_canjes', 'desc')
            ->get()
            ->map(function ($item) use ($totalCanjes) {
                $item->porcentaje_uso = $totalCanjes > 0
                    ? round(($item->total_canjes / $totalCanjes) * 100, 2)
                    : 0;
                return $item;
            });

        $premiosGrafica = $datos->map(function ($p, $idx) {
            $colores = ['#004d35', '#7c3aed', '#2563eb', '#f59e0b', '#10b981', '#ec4899', '#6366f1'];
            return [
                'emocion_nombre' => $p->recompensa_titulo ?? $p->nombre ?? 'Recompensa',
                'total' => $p->total_canjes,
                'porcentaje' => $p->porcentaje_uso,
                'codigo_color' => $colores[$idx % count($colores)],
            ];
        });
        $svgDonut = $this->graficaService->generarDonutSvg($premiosGrafica, 200);
        $svgBarras = $this->graficaService->generarBarrasHorizontalesSvg($premiosGrafica, 540, 32);

        return [
            'tipo' => 'premios_canjeados',
            'titulo' => 'Premios y Recompensas Más Canjeados',
            'total_general' => $totalCanjes,
            'puntos_totales' => $datos->sum('puntos_totales_canjeados'),
            'datos' => $datos,
            'svg_donut' => $svgDonut,
            'svg_donut_data' => $this->graficaService->svgDataUri($svgDonut),
            'svg_barras' => $svgBarras,
            'svg_barras_data' => $this->graficaService->svgDataUri($svgBarras),
            'filtros' => $filtros,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * REPORTE 5: Sesiones registradas por día.
     * Exportación: PDF y Excel (Tabla cronológica de accesos/logins).
     */
    /**
     * REPORTE 5: Sesiones registradas por día (FN.15).
     * Métricas interpretables: Total accesos, Usuarios únicos del periodo, Días con actividad, Promedio diario.
     * Gráfica temporal cronológica ascendente y tabla condicional por rol.
     */
    public function reporteSesionesPorDia(array $filtros, bool $limitarDetalle = true): array
    {
        $query = LoginAcceso::query();

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_acceso', '>=', $filtros['fecha_inicio']);
        } else {
            // Por defecto últimos 30 días
            $query->whereDate('fecha_acceso', '>=', now()->subDays(30));
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_acceso', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['rol_nombre'])) {
            $query->where('rol_nombre', $filtros['rol_nombre']);
        }

        if (!empty($filtros['rol_id'])) {
            $query->where('rol_id', $filtros['rol_id']);
        }

        if (!empty($filtros['q'])) {
            $term = trim($filtros['q']);
            $query->where(function ($q) use ($term) {
                $q->where('nombre_usuario', 'LIKE', "%{$term}%")
                  ->orWhere('email', 'LIKE', "%{$term}%");
            });
        }

        $totalSesiones = (clone $query)->count();
        // Usuarios únicos del periodo completo (desduplicados en todo el rango filtrado)
        $totalUsuariosUnicos = (clone $query)->distinct('usuario_id')->count('usuario_id');

        $hasExitosoCol = \Illuminate\Support\Facades\Schema::hasColumn('login_accesos', 'exitoso');
        $selectExitosos = $hasExitosoCol
            ? "SUM(CASE WHEN exitoso = 1 OR exitoso IS NULL THEN 1 ELSE 0 END) as accesos_exitosos"
            : "COUNT(id) as accesos_exitosos";
        $selectFallidos = $hasExitosoCol
            ? "SUM(CASE WHEN exitoso = 0 THEN 1 ELSE 0 END) as accesos_fallidos"
            : "0 as accesos_fallidos";

        // Resumen agrupado por fecha
        $resumenDiarioDesc = (clone $query)
            ->select(
                DB::raw('DATE(fecha_acceso) as fecha'),
                DB::raw('COUNT(id) as total_accesos'),
                DB::raw("COUNT(DISTINCT usuario_id) as usuarios_unicos"),
                DB::raw($selectExitosos),
                DB::raw($selectFallidos),
                DB::raw("SUM(CASE WHEN rol_nombre = 'Alumno' THEN 1 ELSE 0 END) as accesos_alumnos"),
                DB::raw("SUM(CASE WHEN rol_nombre = 'Docente' THEN 1 ELSE 0 END) as accesos_docentes"),
                DB::raw("SUM(CASE WHEN rol_nombre = 'Orientador' THEN 1 ELSE 0 END) as accesos_orientadores"),
                DB::raw("SUM(CASE WHEN rol_nombre = 'Administrador' THEN 1 ELSE 0 END) as accesos_admins")
            )
            ->groupBy(DB::raw('DATE(fecha_acceso)'))
            ->orderBy('fecha', 'desc')
            ->get();

        // Serie temporal en orden cronológico ascendente para gráficos de tendencia
        $serieTemporalAsc = $resumenDiarioDesc->sortBy('fecha')->values();

        $diasConActividad = $resumenDiarioDesc->count();
        $promedioDiario = $diasConActividad > 0 ? round($totalSesiones / $diasConActividad, 1) : 0.0;

        // Gráfica 1: Línea temporal de volumen de accesos por día
        $puntosAccesos = $serieTemporalAsc->map(fn($d) => [
            'fecha' => \Carbon\Carbon::parse($d->fecha)->format('d/m'),
            'valor' => (int) $d->total_accesos,
        ]);
        $svgLineaTiempo = $this->graficaService->generarLineaTemporalSvg($puntosAccesos, 'Volumen Diario de Accesos al Sistema', 'Accesos', 0, null, 640, 220);

        // Gráfica 2: Barras apiladas de accesos exitosos vs intentos fallidos por día
        $svgApiladasAccesos = $this->graficaService->generarBarrasApiladasAccesosSvg($serieTemporalAsc, 640, 220);

        // Detalle de accesos individuales (últimos 100 para interfaz web interactiva, o completo para exportaciones PDF/XLSX)
        $detalleQuery = (clone $query)->orderBy('fecha_acceso', 'desc');
        if ($limitarDetalle) {
            $detalleQuery->take(100);
        }
        $detalleAccesos = $detalleQuery->get();

        return [
            'tipo' => 'sesiones_por_dia',
            'titulo' => 'Sesiones y Accesos Registrados por Día',
            'total_general' => $totalSesiones,
            'total_usuarios_unicos' => $totalUsuariosUnicos,
            'dias_con_actividad' => $diasConActividad,
            'promedio_diario' => $promedioDiario,
            'resumen_diario' => $resumenDiarioDesc,
            'serie_temporal' => $serieTemporalAsc,
            'detalle_accesos' => $detalleAccesos,
            'svg_linea_tiempo' => $svgLineaTiempo,
            'svg_linea_tiempo_data' => $this->graficaService->svgDataUri($svgLineaTiempo),
            'svg_apiladas_accesos' => $svgApiladasAccesos,
            'svg_apiladas_accesos_data' => $this->graficaService->svgDataUri($svgApiladasAccesos),
            'filtros' => $filtros,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * REPORTE 6: Resultados generales de los tests aplicados.
     * Exportación: PDF y Excel (Tabla tamizaje emocional, puntuaciones y clasificaciones).
     */
    public function reporteResultadosTests(array $filtros): array
    {
        $query = EvaluacionIntento::query()
            ->with(['version.instrumento', 'usuario', 'estudiante.programaEducativo'])
            ->where('evaluaciones_intentos.estado', EvaluacionIntento::ESTADO_FINALIZADO);

        if (!empty($filtros['fecha_inicio'])) {
            $query->whereDate('fecha_finalizacion', '>=', $filtros['fecha_inicio']);
        }

        if (!empty($filtros['fecha_fin'])) {
            $query->whereDate('fecha_finalizacion', '<=', $filtros['fecha_fin']);
        }

        if (!empty($filtros['instrumento_id'])) {
            $query->whereHas('version', function ($q) use ($filtros) {
                $q->where('instrumento_id', $filtros['instrumento_id']);
            });
        }

        if (!empty($filtros['programa_id'])) {
            $query->whereHas('estudiante', function ($q) use ($filtros) {
                $q->where('programa_id', $filtros['programa_id']);
            });
        }

        if (!empty($filtros['nivel_resultado'])) {
            $query->where('nivel_resultado', $filtros['nivel_resultado']);
        }

        if (!empty($filtros['alerta_critica'])) {
            $query->where('tiene_alerta_critica', true);
        }

        $totalEvaluaciones = (clone $query)->count();

        // Distribución por nivel de resultado
        $distribucionNiveles = (clone $query)
            ->select('nivel_resultado', DB::raw('COUNT(intento_id) as total'))
            ->groupBy('nivel_resultado')
            ->orderBy('total', 'desc')
            ->pluck('total', 'nivel_resultado')
            ->toArray();

        $totalAlertasCriticas = (clone $query)->where('tiene_alerta_critica', true)->count();

        $intentos = $query->orderBy('fecha_finalizacion', 'desc')->get()->map(function ($intento) {
            $inst = $intento->version?->instrumento;
            $est = $intento->estudiante;
            $pe = $est?->programaEducativo;

            return [
                'intento_id' => $intento->intento_id,
                'fecha_finalizacion' => $intento->fecha_finalizacion ? $intento->fecha_finalizacion->format('d/m/Y H:i') : '-',
                'instrumento_nombre' => $inst?->nombre ?? 'Test Psicométrico',
                'siglas' => $inst?->codigo ?? 'TEST',
                'matricula' => $est?->matricula ?? 'N/A',
                'estudiante_nombre' => $intento->usuario ? ($intento->usuario->nombre . ' ' . $intento->usuario->primer_apellido) : 'Anónimo',
                'programa_nombre' => $pe?->nombre ?? 'Sin Programa',
                'puntuacion_total' => $intento->puntuacion_total,
                'nivel_resultado' => $intento->nivel_resultado ?: 'Evaluado',
                'tiene_alerta_critica' => (bool)$intento->tiene_alerta_critica,
            ];
        });

        $testsGrafica = [];
        $coloresNivel = [
            'Mínimo' => '#10b981',
            'Leve' => '#3b82f6',
            'Moderado' => '#f59e0b',
            'Severo' => '#ef4444',
            'Sin riesgo' => '#10b981',
            'Riesgo bajo' => '#3b82f6',
            'Riesgo moderado' => '#f59e0b',
            'Riesgo severo' => '#ef4444',
            'Hábitos consolidados' => '#10b981',
            'Hábitos intermedios' => '#6366f1',
            'Hábitos en desarrollo' => '#f59e0b',
        ];
        foreach ($distribucionNiveles as $nivel => $tot) {
            $testsGrafica[] = [
                'emocion_nombre' => $nivel ?: 'Sin clasificar',
                'total' => $tot,
                'porcentaje' => $totalEvaluaciones > 0 ? round(($tot / $totalEvaluaciones) * 100, 1) : 0,
                'codigo_color' => $coloresNivel[$nivel] ?? '#6366f1',
            ];
        }
        $svgBarras = $this->graficaService->generarBarrasHorizontalesSvg($testsGrafica, 600, 32);

        // Agrupación de aplicaciones por instrumento psicométrico
        $porInstrumento = $intentos->countBy('instrumento_nombre')
            ->map(fn($tot, $nom) => (object)['nombre' => $nom, 'total' => $tot])
            ->values();

        // Gráfica 1: Barras de distribución por nivel interpretativo
        $svgBarrasNiveles = $svgBarras;
        // Gráfica 2: Barras de aplicaciones por instrumento psicométrico
        $svgBarrasInstrumentos = $this->graficaService->generarBarrasPorInstrumentoSvg($porInstrumento, 600, 32);

        return [
            'tipo' => 'resultados_tests',
            'titulo' => 'Resultados Generales de Instrumentos y Tests Psicométricos',
            'total_general' => $totalEvaluaciones,
            'total_alertas_criticas' => $totalAlertasCriticas,
            'distribucion_niveles' => $distribucionNiveles,
            'por_instrumento' => $porInstrumento,
            'svg_barras' => $svgBarrasNiveles,
            'svg_barras_data' => $this->graficaService->svgDataUri($svgBarrasNiveles),
            'svg_barras_niveles' => $svgBarrasNiveles,
            'svg_barras_niveles_data' => $this->graficaService->svgDataUri($svgBarrasNiveles),
            'svg_barras_instrumentos' => $svgBarrasInstrumentos,
            'svg_barras_instrumentos_data' => $this->graficaService->svgDataUri($svgBarrasInstrumentos),
            'intentos' => $intentos,
            'filtros' => $filtros,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Generador de exportación Excel / CSV institucional con protección contra inyección de fórmulas.
     */
    public function exportarExcel(string $nombreArchivo, array $encabezados, array $filas): StreamedResponse
    {
        $callback = function () use ($encabezados, $filas) {
            $handle = fopen('php://output', 'w');

            // Insertar Byte Order Mark (BOM) UTF-8 para apertura correcta en Microsoft Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Escribir encabezados
            fputcsv($handle, $encabezados);

            // Sanitizar y escribir filas
            foreach ($filas as $fila) {
                $sanitizada = array_map(function ($valor) {
                    if (is_string($valor) && strlen($valor) > 0) {
                        // Prevenir Inyección de Fórmulas CSV (=, +, -, @, \t, \r)
                        if (in_array($valor[0], ['=', '+', '-', '@'])) {
                            return "'" . $valor;
                        }
                    }
                    return $valor;
                }, $fila);

                fputcsv($handle, $sanitizada);
            }

            fclose($handle);
        };

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generador de PDF institucional utilizando Barryvdh DomPDF.
     */
    public function exportarPdf(string $vista, array $datos, string $nombreArchivo, string $orientacion = 'portrait'): Response
    {
        $pdf = Pdf::loadView($vista, $datos)
            ->setPaper('letter', $orientacion)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->download("{$nombreArchivo}.pdf");
    }
}

<?php

namespace Database\Seeders;

use App\Models\Asignatura;
use App\Models\PlanEstudio;
use App\Models\ProgramaEducativo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InstitucionalCurricularSeeder extends Seeder
{
    /**
     * Catálogo curricular oficial de los 6 programas educativos y sus 10 cuatrimestres
     * según los planes de estudio proporcionados por la institución.
     */
    public function run(): void
    {
        $programasData = [
            [
                'clave' => 'IID',
                'clave_historica' => 'ITI',
                'nombre' => 'Ingeniería en Tecnologías de la Información e Innovación Digital',
                'nombre_historico' => 'Ingeniería en Tecnologías de la Información',
                'descripcion' => 'Formación de ingenieros en desarrollo de software multiplataforma, infraestructura de redes, ciberseguridad e inteligencia artificial.',
                'codigo_plan' => 'IID-2024',
                'cuatrimestres' => [
                    1 => ['Inglés I', 'Desarrollo Humano y Valores', 'Fundamentos Matemáticos', 'Fundamentos de Redes', 'Física', 'Fundamentos de Programación', 'Comunicación y Habilidades Digitales'],
                    2 => ['Inglés II', 'Habilidades Socioemocionales y Manejo de Conflictos', 'Cálculo Diferencial', 'Conmutación y Enrutamiento de Redes', 'Probabilidad y Estadística', 'Programación Estructurada', 'Sistemas Operativos'],
                    3 => ['Inglés III', 'Desarrollo del Pensamiento y Toma de Decisiones', 'Cálculo Integral', 'Tópicos de Calidad para el Diseño de Software', 'Bases de Datos', 'Programación Orientada a Objetos', 'Proyecto Integrador I'],
                    4 => ['Inglés IV', 'Ética Profesional', 'Cálculo de Varias Variables', 'Aplicaciones Web', 'Estructura de Datos', 'Desarrollo de Aplicaciones Móviles', 'Análisis y Diseño de Software'],
                    5 => ['Inglés V', 'Liderazgo de Equipos de Alto Desempeño', 'Ecuaciones Diferenciales', 'Aplicaciones Web Orientadas a Servicios', 'Bases de Datos Avanzadas', 'Estándares y Métricas para el Desarrollo de Software', 'Proyecto Integrador II'],
                    6 => ['Estadía Técnico Superior Universitario en Desarrollo de Software Multiplataforma'],
                    7 => ['Inglés VI', 'Habilidades Gerenciales', 'Formulación de Proyectos de Tecnología', 'Fundamentos de Inteligencia Artificial', 'Ética y Legislación en Tecnologías de la Información', 'Optativa I', 'Seguridad Informática'],
                    8 => ['Inglés VII', 'Electrónica Digital', 'Gestión de Proyectos de Tecnología', 'Programación para Inteligencia Artificial', 'Administración de Servidores', 'Optativa II', 'Informática Forense'],
                    9 => ['Inglés VIII', 'Internet de las Cosas', 'Evaluación de Proyectos de Tecnología', 'Ciencia de Datos', 'Tecnologías Disruptivas', 'Optativa III', 'Proyecto Integrador III'],
                    10 => ['Estadía Licenciatura en Ingeniería en Tecnologías de la Información e Innovación Digital'],
                ],
            ],
            [
                'clave' => 'IFI',
                'clave_historica' => null,
                'nombre' => 'Ingeniería Financiera',
                'nombre_historico' => null,
                'descripcion' => 'Formación de profesionistas especializados en valuación financiera, gestión de riesgos, portafolios de inversión y modelos econométricos.',
                'codigo_plan' => 'IFI-2024',
                'cuatrimestres' => [
                    1 => ['Inglés I', 'Desarrollo Humano y Valores', 'Álgebra Lineal', 'Microeconomía', 'Introducción a las Finanzas', 'Contabilidad Financiera', 'Comunicación y Habilidades Digitales'],
                    2 => ['Inglés II', 'Habilidades Socioemocionales y Manejo de Conflictos', 'Cálculo Diferencial', 'Macroeconomía', 'Marco Jurídico de las Finanzas', 'Contabilidad de Costos', 'Fundamentos de Programación'],
                    3 => ['Inglés III', 'Desarrollo del Pensamiento y Toma de Decisiones', 'Cálculo Integral', 'Economía Internacional', 'Administración Financiera y Estratégica', 'Presupuestos', 'Proyecto Integrador I'],
                    4 => ['Inglés IV', 'Ética Profesional', 'Investigación de Operaciones', 'Sistemas Financieros', 'Planeación Financiera', 'Contribuciones Fiscales', 'Matemáticas Financieras'],
                    5 => ['Inglés V', 'Liderazgo de Equipos de Alto Desempeño', 'Ecuaciones Diferenciales', 'Ingeniería de Procesos y Logística', 'Formulación y Evaluación de Proyectos', 'Probabilidad y Estadística', 'Proyecto Integrador II'],
                    6 => ['Estadía Técnico Superior Universitario en Gestión Financiera'],
                    7 => ['Inglés VI', 'Habilidades Gerenciales', 'Minería de Datos', 'Econometría', 'Finanzas Corporativas', 'Sistemas de Control Administrativo', 'TIC\'s Financieras'],
                    8 => ['Inglés VII', 'Finanzas Públicas', 'Consultoría Financiera', 'Control de Riesgos', 'Portafolios e Instrumentos de Inversión', 'Finanzas Internacionales', 'Análisis de Mercados Financieros'],
                    9 => ['Inglés VIII', 'Valuación de Empresas', 'Mercado de Derivados', 'Construcción de Modelos y Escenarios Financieros', 'Programación Financiera', 'Mercados Financieros Digitales', 'Proyecto Integrador III'],
                    10 => ['Estadía Licenciatura en Ingeniería Financiera'],
                ],
            ],
            [
                'clave' => 'IBT',
                'clave_historica' => null,
                'nombre' => 'Ingeniería en Biotecnología',
                'nombre_historico' => null,
                'descripcion' => 'Especialistas en bioprocesos, ingeniería genética, biotecnología ambiental, médico-farmacéutica y agropecuaria.',
                'codigo_plan' => 'IBT-2024',
                'cuatrimestres' => [
                    1 => ['Inglés I', 'Desarrollo Humano y Valores', 'Fundamentos Matemáticos', 'Química Inorgánica', 'Química Orgánica', 'Biotecnología y Desarrollo Sostenible', 'Comunicación y Habilidades Digitales'],
                    2 => ['Inglés II', 'Habilidades Socioemocionales y Manejo de Conflictos', 'Cálculo Diferencial', 'Física', 'Probabilidad y Estadística', 'Química Analítica', 'Biología y Fisiología Celular'],
                    3 => ['Inglés III', 'Desarrollo del Pensamiento', 'Cálculo Integral', 'Bioquímica', 'Microbiología', 'Termodinámica y Fisicoquímica', 'Proyecto Integrador I'],
                    4 => ['Inglés IV', 'Ética Profesional', 'Cálculo de Varias Variables', 'Balance de Materia y Energía', 'Biología Molecular', 'Inmunología', 'Tecnología Enzimática'],
                    5 => ['Inglés V', 'Liderazgo de Equipos de Alto Desempeño', 'Ecuaciones Diferenciales', 'Bioseguridad e Higiene', 'Fundamentos de Bioprocesos', 'Tópicos de Biotecnología', 'Proyecto II'],
                    6 => ['Estadía Técnico Superior Universitario en Biotecnología'],
                    7 => ['Inglés VI', 'Habilidades Gerenciales', 'Control de Calidad', 'Operaciones Unitarias', 'Fenómenos de Transporte', 'Ingeniería Genética', 'Diseño de Experimentos y Control Estadístico'],
                    8 => ['Inglés VII', 'Bioinformática', 'Biotecnología Agropecuaria', 'Biotecnología Ambiental', 'Ingeniería de Bioprocesos', 'Ingeniería de Biorreactores', 'Ingeniería de Proyectos'],
                    9 => ['Inglés VIII', 'Biotecnología de Alimentos', 'Biotecnología Médico-Farmacéutica', 'Control de Procesos', 'Mejora de Bioprocesos', 'Metabolismo Secundario', 'Proyecto Integrador III'],
                    10 => ['Estadía Licenciatura en Ingeniería en Biotecnología'],
                ],
            ],
            [
                'clave' => 'IIN',
                'clave_historica' => null,
                'nombre' => 'Ingeniería Industrial',
                'nombre_historico' => null,
                'descripcion' => 'Optimización de procesos productivos, cadena de suministro, calidad, manufactura esbelta y ergonomía industrial.',
                'codigo_plan' => 'IIN-2024',
                'cuatrimestres' => [
                    1 => ['Inglés I', 'Desarrollo Humano y Valores', 'Fundamentos Matemáticos', 'Dibujo Industrial', 'Química Básica', 'Metrología', 'Comunicación y Habilidades Digitales'],
                    2 => ['Inglés II', 'Habilidades Socioemocionales y Manejo de Conflictos', 'Cálculo Diferencial', 'Probabilidad y Estadística', 'Seguridad, Higiene y Medio Ambiente', 'Costos de Producción'],
                    3 => ['Inglés III', 'Desarrollo del Pensamiento y Toma de Decisiones', 'Cálculo Integral', 'Control de Calidad', 'Procesos de Fabricación', 'Estudio del Trabajo', 'Proyecto Integrador I'],
                    4 => ['Inglés IV', 'Ética Profesional', 'Cálculo de Varias Variables', 'Ingeniería de Planta y Estudio del Trabajo', 'Administración y Control de la Calidad', 'Tecnologías de Transformación de Materiales', 'Ingeniería Económica'],
                    5 => ['Inglés V', 'Liderazgo de Equipos de Alto Desempeño', 'Ecuaciones Diferenciales', 'Administración y Control de Operaciones', 'Gestión Ambiental en Procesos Industriales', 'Sistema de Manufactura Aplicada', 'Proyecto Integrador II'],
                    6 => ['Estadía Técnico Superior Universitario en Procesos Productivos'],
                    7 => ['Inglés VI', 'Habilidades Gerenciales', 'Ingeniería de Planta', 'Investigación de Operaciones I', 'Manufactura Esbelta', 'Automatización y Control de Procesos', 'Tópicos de Nuevas Tecnologías de Manufactura'],
                    8 => ['Inglés VII', 'Sistemas de Gestión de la Calidad', 'Logística', 'Investigación de Operaciones II', 'Six Sigma', 'Diseño de Producto', 'Evaluación y Administración de Proyectos'],
                    9 => ['Inglés VIII', 'Legislación Industrial', 'Administración Industrial y de Servicios', 'Simulación de Procesos', 'Administración de Mantenimiento', 'Manufactura Integrada por Computadora', 'Proyecto Integrador III'],
                    10 => ['Estadía Licenciatura en Ingeniería Industrial'],
                ],
            ],
            [
                'clave' => 'IAS',
                'clave_historica' => 'ITA',
                'nombre' => 'Ingeniería Ambiental y Sustentabilidad',
                'nombre_historico' => 'Ingeniería en Tecnología Ambiental',
                'descripcion' => 'Desarrollo de proyectos de sustentabilidad, mitigación del cambio climático, tratamiento de aguas y energías renovables.',
                'codigo_plan' => 'IAS-2024',
                'cuatrimestres' => [
                    1 => ['Inglés I', 'Desarrollo Humano y Valores', 'Fundamentos Matemáticos', 'Legislación Ambiental', 'Química Inorgánica', 'Metodología de la Investigación', 'Comunicación y Habilidades Digitales'],
                    2 => ['Inglés II', 'Habilidades Socioemocionales', 'Cálculo Diferencial', 'Física', 'Probabilidad y Estadística', 'Química Analítica', 'Química Orgánica'],
                    3 => ['Inglés III', 'Desarrollo del Pensamiento', 'Cálculo Integral', 'Bioquímica', 'Microbiología Ambiental', 'Química Ambiental', 'Proyecto Integrador I'],
                    4 => ['Inglés IV', 'Ética Profesional', 'Cálculo de Varias Variables', 'Gestión de Recursos Hídricos', 'GestIntegral de Residuos', 'Seguridad Laboral y Salud', 'Sistemas de Gestión Ambiental'],
                    5 => ['Inglés V', 'Liderazgo de Equipos de Alto Desempeño', 'Ecuaciones Diferenciales', 'Gestión de la Calidad Aire', 'Evaluación de Impacto Ambiental', 'Manejo y Conservación de Suelo', 'Proyecto Integrador II'],
                    6 => ['Estadía Técnico Superior Universitario en Gestión Ambiental'],
                    7 => ['Inglés VI', 'Habilidades Gerenciales', 'Gestión y Auditoría Ambiental y Laboral', 'Operaciones Unitarias I', 'Producción Sustentable', 'Termodinámica', 'Sistemas de Información Geográfica y Ordenamiento Territorial'],
                    8 => ['Inglés VII', 'Diseño de Experimentos', 'Estrategias Regionales para la Sustentabilidad', 'Ingeniería Económica y Evaluación de Proyectos Ambientales', 'Mecánica de Fluidos e Hidráulica', 'Operaciones Unitarias II', 'Proceso de Adaptación al Cambio Climático'],
                    9 => ['Inglés VIII', 'Bioprocesos Ambientales', 'Energías Alternativas', 'Estrategias Regionales para la Sustentabilidad', 'Evaluación de Riesgo', 'Tecnología para el Tratamiento de Agua', 'Proyecto Integrador III'],
                    10 => ['Estadía para la Ingeniería Ambiental y Sustentabilidad'],
                ],
            ],
            [
                'clave' => 'ISE',
                'clave_historica' => 'IET',
                'nombre' => 'Ingeniería en Sistemas Electrónicos',
                'nombre_historico' => 'Ingeniería en Electrónica y Telecomunicaciones',
                'descripcion' => 'Sistemas embebidos, telecomunicaciones, internet de las cosas, circuitos lógicos y electrónica de potencia.',
                'codigo_plan' => 'ISE-2024',
                'cuatrimestres' => [
                    1 => ['Inglés I', 'Desarrollo Humano y Valores', 'Fundamentos Matemáticos', 'Metrología Aplicada a la Electrónica', 'Física', 'Programación Estructurada', 'Comunicación y Habilidades Digitales'],
                    2 => ['Inglés II', 'Habilidades Socioemocionales y Manejo de Conflictos', 'Cálculo Diferencial', 'Circuitos de Corriente Alterna y Directa', 'Probabilidad y Estadística', 'Programación Orientada a Objetos', 'Estructura y Propiedades de los Materiales'],
                    3 => ['Inglés III', 'Desarrollo del Pensamiento y Toma de Decisiones', 'Cálculo Integral', 'Fundamentos de Electromagnetismo', 'Circuitos Lógicos', 'Análisis de Dispositivos Electrónicos', 'Proyecto Integrador I'],
                    4 => ['Inglés IV', 'Ética Profesional', 'Cálculo de Varias Variables', 'Sensores y Actuadores', 'Amplificadores Operacionales y de Potencia', 'Mantenimiento Electrónico', 'Filtros Analógicos y Digitales'],
                    5 => ['Inglés V', 'Liderazgo de Equipos de Alto Desempeño', 'Ecuaciones Diferenciales', 'Modulaciones Analógicas y Digitales', 'Sistemas de Telefonía Fija y Celular', 'Redes LAN-WAN', 'Proyecto Integrador II'],
                    6 => ['Estadía Técnico Superior Universitario en Redes de Comunicación'],
                    7 => ['Inglés VI', 'Habilidades Gerenciales', 'Señales y Sistemas Lineales', 'Fundamentos de Control', 'Antena y Guías de Onda', 'Amplificadores Electrónicos', 'Diseño Asistido por Computadora'],
                    8 => ['Inglés VII', 'Gestión de Mantenimiento', 'Control Digital', 'Microprocesadores y Microcontroladores', 'Dispositivos Lógicos Programables', 'Procesamiento Digital de Señales', 'Sistemas de Televisión y Radiodifusión'],
                    9 => ['Inglés VIII', 'Gestión Empresarial de Proyectos', 'Comunicaciones Electrónicas', 'Internet de las Cosas', 'Electrónica de Potencia', 'Sistemas Embebidos', 'Proyecto Integrador III'],
                    10 => ['Estadía Licenciatura en Ingeniería en Sistemas Electrónicos'],
                ],
            ],
        ];

        foreach ($programasData as $pData) {
            // 1. Programa Educativo (idempotente)
            $programa = ProgramaEducativo::updateOrCreate(
                ['clave' => $pData['clave']],
                [
                    'clave_historica' => $pData['clave_historica'],
                    'nombre' => $pData['nombre'],
                    'nombre_historico' => $pData['nombre_historico'],
                    'descripcion' => $pData['descripcion'],
                    'activo' => true,
                    'archivado' => false,
                ]
            );

            // 2. Plan de Estudio (idempotente)
            $plan = PlanEstudio::updateOrCreate(
                [
                    'programa_id' => $programa->programa_id,
                    'codigo' => $pData['codigo_plan'],
                    'version' => '1.0',
                ],
                [
                    'nombre' => "Plan de Estudios Oficial ({$pData['clave']})",
                    'anio_aprobacion' => 2024,
                    'vigencia_inicio' => '2024-01-01',
                    'estado' => PlanEstudio::ESTADO_VIGENTE,
                    'es_vigente' => true,
                    'total_cuatrimestres' => 10,
                    'descripcion' => "Catálogo curricular oficial suministrado para {$pData['nombre']}.",
                ]
            );

            // 3. Asignaturas por cuatrimestre
            foreach ($pData['cuatrimestres'] as $cuatrimestre => $materias) {
                foreach ($materias as $index => $materiaNombre) {
                    $materiaNombreLimpio = trim($materiaNombre);
                    $esEstadia = str_contains(mb_strtolower($materiaNombreLimpio), 'estadía') ||
                                 str_contains(mb_strtolower($materiaNombreLimpio), 'estadia');
                    $esOptativa = str_starts_with($materiaNombreLimpio, 'Optativa');
                    $esProyecto = str_starts_with($materiaNombreLimpio, 'Proyecto');

                    $tipo = Asignatura::TIPO_ORDINARIA;
                    if ($esEstadia) {
                        $tipo = Asignatura::TIPO_ESTADIA;
                    } elseif ($esOptativa) {
                        $tipo = Asignatura::TIPO_OPTATIVA;
                    } elseif ($esProyecto) {
                        $tipo = Asignatura::TIPO_PROYECTO_INTEGRADOR;
                    }

                    // Clave identificadora estandarizada
                    $numeroOrden = str_pad($index + 1, 2, '0', STR_PAD_LEFT);
                    $claveGenerada = "{$pData['clave']}-C{$cuatrimestre}-{$numeroOrden}";

                    // Observación de posible duplicidad en IAS cuatrimestres 8 y 9
                    if ($pData['clave'] === 'IAS' && $materiaNombreLimpio === 'Estrategias Regionales para la Sustentabilidad') {
                        $claveGenerada = "{$pData['clave']}-C{$cuatrimestre}-ERS";
                    }

                    Asignatura::updateOrCreate(
                        [
                            'programa_id' => $programa->programa_id,
                            'clave' => $claveGenerada,
                        ],
                        [
                            'plan_id' => $plan->plan_id,
                            'nombre' => $materiaNombreLimpio,
                            'cuatrimestre' => $cuatrimestre,
                            'tipo' => $tipo,
                            'es_estadia' => $esEstadia,
                            'es_optativa' => $esOptativa,
                            'orden' => $index + 1,
                            'activo' => true,
                        ]
                    );
                }
            }
        }
    }
}


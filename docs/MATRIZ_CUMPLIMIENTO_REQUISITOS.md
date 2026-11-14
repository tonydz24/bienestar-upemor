# MATRIZ DE CUMPLIMIENTO DE REQUISITOS INSTITUCIONALES
## Plataforma Universitaria Bienestar UPEMOR
**Documento base:** Memoria de Estadía Profesional (Capítulos 2 y 3)  
**Proyecto:** Plataforma Web Integral Bienestar UPEMOR  
**Fecha de corte:** Cierre Definitivo del Incremento 24 — Auditoría Final de Calidad de Código, Documentación Integral (PHPDoc 100%), Arquitectura MVC, Inventario de 509 Archivos y Censo de 50 Tablas en MariaDB (14 de noviembre de 2026)  
**Versión:** 11.0 — Matriz Maestra de Cierre Definitivo y Entrega Institucional (522 pruebas automatizadas aprobadas al 100%, 2,605 aserciones, 0 fallos, 0 errores, 730 métodos documentados)

---

## RESUMEN EJECUTIVO DE CUMPLIMIENTO

| Categoría | Requisitos Totales | Implementados y Verificados | Porcentaje de Cumplimiento | Cobertura en Tests / Calidad |
| :--- | :---: | :---: | :---: | :---: |
| **Requisitos Funcionales (FN.1 – FN.16)** | 16 | 16 | **100%** | 522 tests (2,605 aserciones) |
| **Requisitos No Funcionales / Calidad (FF.1 – FF.6)** | 6 | 6 | **100%** | Integrados en arquitectura, MVC y QA |
| **Calidad Interna y Documentación (PHPDoc / JSDoc)** | 730 métodos | 730 métodos | **100%** | 100% de métodos propios documentados |
| **Integridad de Base de Datos (MariaDB 3308)** | 50 tablas | 50 tablas | **100%** | 100% InnoDB, 0 tablas obsoletas |
| **Total Global** | **22** | **22** | **100%** | **Suite completa en verde (522/522)** |

---

## 1. REQUISITOS FUNCIONALES ORIGINALES (FN.1 A FN.16)

### FN.1 — Inicio de sesión y recuperación de contraseña
* **Definición de la Memoria:** El sistema permitirá el acceso a la aplicación mediante correo electrónico institucional y contraseña registrados previamente. Validará credenciales para conceder acceso según el perfil (Administrador, Docente, Alumno u Orientador). Incluye recuperación de contraseña mediante correo electrónico con token seguro, cierre de sesión manual y cierre automático por 15 minutos continuos de inactividad del usuario.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/AuthController.php`
  - `app/Http/Controllers/Auth/PasswordResetController.php`
  - `app/Http/Middleware/CheckSessionTimeout.php` (Timeout estricto de 15 minutos; el sondeo periódico de notificaciones en segundo plano `X-Background-Polling: true` no renueva el tiempo; retorna 401 JSON para AJAX y modal no descartable).
  - `app/Notifications/RestablecerPasswordNotification.php` (Plantilla HTML institucional UPEMOR).
  - Vistas: `resources/views/auth/login.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php`.
* **Pruebas Automatizadas:** `tests/Feature/AuthTest.php` (25 pruebas), `tests/Feature/PasswordResetFlowTest.php` (8 pruebas), `tests/Feature/Incremento17Test.php`.
* **Evidencia:** Redirección a dashboard de cada rol; rate limiting al 6.º intento fallido consecutivo; token criptográfico con tiempo de expiración y consumo único; invalidación de sesiones previas en logout.

---

### FN.2 — Gestión de Usuarios
* **Definición de la Memoria:** El sistema permitirá al Administrador administrar los usuarios registrados mediante operaciones de alta, consulta, actualización y desactivación lógica de cuentas. Podrá asignar los perfiles de Administrador, Docente, Alumno y Orientador, controlar sus permisos de acceso y mantener actualizada la información de cada usuario.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Admin/UsuarioAdminController.php`
  - `app/Http/Controllers/RegisterController.php` (Registro público de estudiantes con matrícula institucional de 10 caracteres y carrera validada).
  - `app/Http/Controllers/Admin/SolicitudAlumnoController.php` (Aprobación/Rechazo de registros estudiantiles).
  - Modelos: `User.php`, `Estudiante.php`, `Docente.php`, `CitaProfesional.php`.
  - Vistas: `resources/views/admin/usuarios/*`, `resources/views/admin/solicitudes/*`.
* **Pruebas Automatizadas:** `tests/Feature/AdminSolicitudesTest.php` (19 pruebas), `tests/Feature/RegisterTest.php` (15 pruebas), `tests/Feature/UsuarioAdminTest.php`.
* **Evidencia:** Separación estricta entre Docente curricular y Orientador psicoemocional; safe delete con bloqueo si existen citas o evaluaciones históricas; generación automática de identificadores de empleado.

---

### FN.3 — Gestión de Tests (Evaluaciones Psicométricas)
* **Definición de la Memoria:** El sistema permitirá administrar los tests de tamizaje emocional utilizados para evaluar el bienestar de los estudiantes. El administrador podrá registrar, consultar, actualizar y supervisar cuestionarios, mientras que los alumnos podrán responderlos y el sistema almacenará automáticamente sus respuestas y resultados para consultas posteriores.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Admin/TestAdminController.php` (CRUD administrativo, consulta de versiones, preguntas, reactivos y baremos normativos).
  - `app/Http/Controllers/Alumno/EvaluacionController.php` (Catálogo, instrucciones con consentimiento informado, aplicación reactivo por reactivo con autosave y cálculo determinista de puntaje).
  - Instrumentos estandarizados cargados: GAD-7 (Ansiedad), PHQ-9 (Depresión), PSS-10 (Estrés percibido con reactivos invertidos), WHO-5 (Bienestar).
  - Aviso deontológico y metodológico uniforme: Herramientas de tamizaje orientador preventivo, sin sustitución de diagnóstico clínico presencial.
  - Vistas: `resources/views/admin/tests/*`, `resources/views/alumno/evaluaciones/*`.
* **Pruebas Automatizadas:** `tests/Feature/EvaluacionCatalogoTest.php`, `EvaluacionCuestionarioTest.php`, `EvaluacionScoringTest.php`, `Incremento15Test.php`, `Incremento17Test.php`.

---

### FN.4 — Bitácora Emocional
* **Definición de la Memoria:** El sistema permitirá a los alumnos registrar periódicamente su estado de ánimo seleccionando entre las opciones establecidas (Felicidad, Tristeza, Enojo, Ansiedad, Calma, Miedo), indicando el nivel de intensidad, contexto y notas opcionales. El alumno podrá consultar su historial confidencial.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Alumno/BitacoraController.php`
  - Modelos: `BitacoraEmocional.php`, `Emocion.php`.
  - Confidencialidad: Ni docentes curriculares ni administradores tienen acceso a las bitácoras privadas individuales de los estudiantes.
  - Vistas: `resources/views/alumno/bitacora/index.blade.php`, `historial.blade.php`.
* **Pruebas Automatizadas:** `tests/Feature/BitacoraEmocionalTest.php` (13 pruebas).

---

### FN.5 — Catálogo y Canje de Recompensas
* **Definición de la Memoria:** El sistema dispondrá de un catálogo de incentivos y reconocimientos académicos y extracurriculares gestionados por docentes y administradores. Los alumnos podrán solicitar el canje de sus puntos acumulados por beneficios vigentes.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Services/Recompensas/RecompensaService.php` (Transacciones atómicas con decremento de saldo y cupo disponible, emisión de código digital único `BEN-XXXXX`).
  - `app/Http/Controllers/Admin/RecompensaAdminController.php` (Catálogo base institucional).
  - `app/Http/Controllers/Docente/RecompensaDocenteController.php` (Activación en asignaturas específicas).
  - `app/Http/Controllers/Alumno/RecompensaAlumnoController.php` (Consulta y canje exclusivo para estudiantes inscritos en la materia o grupo).
* **Pruebas Automatizadas:** `tests/Feature/RecompensasTest.php`, `Incremento17Test.php`.

---

### FN.6 — Meditación y Relajación Guiada
* **Definición de la Memoria:** El sistema ofrecerá ejercicios interactivos y recursos multimedia de relajación, respiración consciente y mindfulness orientados a reducir el estrés estudiantil.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Alumno/MeditacionController.php` (Reproductor interactivo, temporizador visual, integración de YouTube API sin cookies, acreditación controlada de +2 pts por sesión).
  - `app/Http/Controllers/Admin/MeditacionAdminController.php` (CRUD y gestión multimedia).
  - Seeder multimedia: `database/seeders/MeditacionesVideosSeeder.php` con video institucional 1 (Respiración 4-7-8, `https://www.youtube.com/watch?v=EGO5m_DBzF8`) y video 2.
  - Píldoras visuales de filtro por categoría estilizadas en CSS.
* **Pruebas Automatizadas:** `tests/Feature/MeditacionVideoInteractivoTest.php`, `MeditacionesTest.php`.

---

### FN.7 — Citas de Orientación Educativa y Psicológica
* **Definición de la Memoria:** El sistema permitirá a los alumnos solicitar citas de atención con los orientadores educativos disponibles, seleccionando fecha y horario. Los orientadores podrán gestionar su disponibilidad y atender solicitudes.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Alumno/CitaController.php` (Solicitud, consulta de horarios disponibles vía AJAX, límite de solicitudes activas, cancelación autónoma).
  - `app/Http/Controllers/Docente/CitaGestionController.php` (Panel del orientador, confirmación, reprogramación, finalización y visualización confidencial de tamizajes con consentimiento).
  - Concurrencia: Bloqueos a nivel base de datos para prevenir duplicidad o doble reserva en el mismo horario.
* **Pruebas Automatizadas:** `tests/Feature/CitasSolicitudTest.php`, `CitaConcurrenciaTest.php`, `DisponibilidadSolapamientoTest.php`.

---

### FN.8 — Encuestas Institucionales de Satisfacción
* **Definición de la Memoria:** El sistema permitirá diseñar y aplicar encuestas de evaluación sobre los servicios universitarios, recopilando retroalimentación de los estudiantes de forma confidencial o anónima.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Admin/EncuestaAdminController.php` (Diseño de preguntas, opciones, control de publicación y estadísticas agregadas).
  - `app/Http/Controllers/Alumno/EncuestaAlumnoController.php` (Respuesta de cuestionarios y acreditación de puntos).
  - Garantía de anonimato: Disociación de identidad en encuestas configuradas como anónimas.
* **Pruebas Automatizadas:** `tests/Feature/EncuestasAdminTest.php` (12 pruebas).

---

### FN.9 — Dashboard del Alumno
* **Definición de la Memoria:** Panel inicial personalizado para el estudiante con saludo, resumen de estado emocional, balance de puntos, accesos directos a evaluaciones, citas, meditaciones y materias inscritas.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Alumno/DashboardController.php`.
  - Menú lateral con enlace directo a "Mis Asignaturas" (`alumno.academico.index`).
  - Tarjetas de cuatrimestre y grupo nominal oficial.
* **Pruebas Automatizadas:** `tests/Feature/DashboardTest.php`, `Incremento17Test.php`.

---

### FN.10 — Dashboard del Docente
* **Definición de la Memoria:** Panel inicial para el personal académico con métricas de asignaturas a cargo, total de alumnos atendidos, recompensas habilitadas y solicitudes de cátedra.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Docente/DashboardController.php`.
  - Consolidación por asignatura con desglose en subgrupos.
* **Pruebas Automatizadas:** `tests/Feature/DashboardTest.php`, `AcademicoDocenteTest.php`.

---

### FN.11 — Puntos de Bienestar y Gamificación
* **Definición de la Memoria:** Sistema de estímulos que otorga puntos a los alumnos por actividades de bienestar (bitácoras diarias, meditaciones, encuestas, evaluaciones) para canje por incentivos académicos.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Services/Puntos/PuntoService.php` (Libro mayor contable auditado `estudiante_puntos_movimientos`, topes diarios anti-inflación, neutralidad ética que no premia un puntaje psicológico sobre otro).
* **Pruebas Automatizadas:** `tests/Feature/Incremento11Test.php`, `Incremento14ConsolidacionTest.php`.

---

### FN.12 — Gestión Curricular (Carreras, Planes y Periodos)
* **Definición de la Memoria:** El sistema permitirá al Administrador gestionar las carreras, planes de estudio, asignaturas y periodos cuatrimestrales de la UPEMOR.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/Admin/CarreraController.php`
  - `app/Http/Controllers/Admin/PlanEstudioController.php`
  - `app/Http/Controllers/Admin/PeriodoAcademicoController.php`
* **Pruebas Automatizadas:** `tests/Feature/GestionAcademicaCurricularTest.php` (14 pruebas).

---

### FN.13 — Asignaciones Docentes e Inscripción de Materias
* **Definición de la Memoria:** Administración de materias impartidas por docentes por grupo y cuatrimestre, permitiendo la inscripción formal de los estudiantes con aprobación docente y control de duplicidad.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Services/Academico/PeriodoOperativoService.php` (Resolución determinista del periodo activo sin ambigüedades).
  - `app/Services/Academico/EstudianteGrupoResolverService.php` (Resolución canónica de grupo y conciliación automática de dobles adscripciones).
  - `app/Services/Academico/CatedraAcademicaService.php` (Agrupación de catálogo estudiantil por Asignatura curricular con docentes disponibles, validación estricta de carrera/cuatrimestre/sección, transacciones atómicas con `lockForUpdate()`, autorización atómica por el Administrador).
  - `app/Http/Controllers/Docente/AcademicoController.php` (Solicitud de cátedra con exclusión de materias ya solicitadas; visualización unificada por materia con acordeón/pestañas de subgrupos; aprobación e idempotencia).
  - `app/Http/Controllers/Alumno/AcademicoController.php` (Catálogo agrupado por Asignatura; botón con protección JavaScript anti-doble clic; soporte para alcances `TODOS` y `ESPECIFICO`).
* **Pruebas Automatizadas:** `tests/Feature/AcademicoDocenteTest.php`, `Incremento16Test.php`, `Incremento17Test.php`, `Incremento18AcademicoArquitecturaTest.php` (12 pruebas), `Incremento19IntegracionFinalTest.php` (5 pruebas), `Incremento20CierreDefinitivoTest.php` (10 pruebas).
* **Validación en MariaDB Temporal / Staging:** Verificado al 100% en `bienestar_upemor_validacion_i20` con 34 usuarios sintéticos (11 docentes y 16 alumnos cubriendo las 4 cohortes institucionales: 2026–2029 1°, 2025–2028 4° Manuel Antonio, 2024–2027 7° y 2023–2026 10°). Visualización informativa de materias del plan curricular sin oferta docente implementada conforme a Sección 6.3.

---

### FN.14 — Notificaciones Internas y Alertas en Vivo
* **Definición de la Memoria:** Notificación inmediata al usuario de eventos relevantes (cambios en citas, solicitudes de inscripción, recompensas, avisos de bienestar) dentro del sistema y por correo institucional.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Http/Controllers/NotificationController.php` (Marcar como leída sin salir de la bandeja, sondeo ligero asíncrono con `X-Background-Polling: true`).
  - Notificaciones en vivo con Toast flotante de 6 segundos en pantalla.
  - Campana interactiva con contador y previsualización de mensajes recientes.
* **Pruebas Automatizadas:** `tests/Feature/NotificationNavigationTest.php`, `Incremento17Test.php`.

---

### FN.15 — Reportes Institucionales y Analítica
* **Definición de la Memoria:** Generación de 6 reportes institucionales (emociones globales, por programa, historial individual del estudiante, premios canjeados, sesiones de usuario y resultados de tests) en formatos web, PDF imprimible y hoja de cálculo Excel.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Services/Reportes/ReporteInstitucionalService.php` (Cálculo de métricas y renderizado de gráficos vectoriales SVG embebidos como data-URIs para compatibilidad DomPDF).
  - `app/Services/Reportes/ExcelReporteService.php` (Generación nativa OpenXML `.xlsx` con paleta verde institucional `#004D35`, sanitización contra inyección de fórmulas y **generación de gráficos nativos DrawingML en OpenXML** `xl/charts/chart1.xml` y `xl/drawings/drawing1.xml` para gráficos de dona y barras cuando existen datos tabulares).
  - Selector de estudiante en historial individual con soporte integral para `usuario_id` y nombres completos.
* **Pruebas Automatizadas:** `tests/Feature/ReportesInstitucionalesTest.php` (10 pruebas), `tests/Feature/Incremento20CierreDefinitivoTest.php` (10 pruebas), `Incremento17Test.php`.
* **Evidencia Técnica de Gráficos:** Inspección directa del ZIP OpenXML confirma la existencia de `xl/drawings/drawing1.xml`, `xl/charts/chart1.xml`, `<drawing r:id="rId1"/>` en hojas de cálculo y relaciones en `_rels`. Diferenciación estricta entre Web (Chart.js interactivo), PDF (SVG vectorial embebido) y Excel (OpenXML DrawingML nativo).

---

### FN.16 — Respaldos de Base de Datos y Recuperación Segura
* **Definición de la Memoria:** Funcionalidad para generar copias de seguridad de la base de datos MariaDB/MySQL, verificar su integridad criptográfica y gestionar procedimientos controlados de recuperación.
* **Estado:** **100% IMPLEMENTADO Y AUDITADO**.
* **Arquitectura y Componentes:**
  - `app/Services/Respaldos/RespaldoDatabaseService.php`.
  - Distinción nítida en interfaz: "Verificar respaldo" (inspección estática de hash SHA-256 y sintaxis) vs "Preparar recuperación" (dry-run seguro y reporte de consistencia).
  - Restauración física mediante PDO nativo (`ejecutarSqlDumpPdo`), superando definitivamente el error de socket Winsock 10106 de `mysql.exe` y filtrando consultas vacías post-strip de comentarios condicionales MariaDB.
  - Salvaguarda inquebrantable del sandbox: La sobreescritura física directa vía web permanece desactivada (`ALLOW_WEB_RESTORE=false`), protegiendo de cualquier daño a la base real en puerto 3308.
  - Sincronización horaria exacta con `America/Mexico_City` (`dd/mm/aaaa HH:mm:ss`).
* **Pruebas Automatizadas:** `tests/Feature/RespaldosAdminTest.php` (10 pruebas), `Incremento17Test.php`, `Incremento19IntegracionFinalTest.php`, `Incremento20CierreDefinitivoTest.php`.
* **Validación Empírica de Recuperación y Resiliencia en MariaDB Temporal (I20.1):** Ciclo completo probado en base temporal aislada `bienestar_upemor_test_compat_restore`: respaldo inicial (45 tablas) -> migración (47 tablas) -> respaldo post-migración -> simulación de contingencia -> reversión y restauración íntegra a 45 tablas -> comprobación de compatibilidad y resiliencia de código Laravel (Dual-Schema Tolerance). Base protegida `bienestar_upemor_sandbox` preservada al 100% intacta.

---

## 2. REQUISITOS NO FUNCIONALES Y DE CALIDAD (FF.1 A FF.6)

| Código | Requisito | Estado | Mecanismo de Implementación y Aseguramiento |
| :--- | :--- | :---: | :--- |
| **FF.1** | **Diseño Institucional y Usabilidad** | **CUMPLIDO** | Bootstrap 5 nativo compilado con Vite. Paleta oficial UPEMOR (Verde institucional `#004D35`, acentos violeta y azul pizarra). Interfaz limpia sin etiquetas técnicas visibles (`FN.*` eliminadas de la vista del usuario). |
| **FF.2** | **Rendimiento y Escalabilidad** | **CUMPLIDO** | Consultas optimizadas con `with()` para evitar problemas N+1; paginación server-side en listados y reportes; índices relacionales compuestos en MariaDB; ejecución transaccional atómica. |
| **FF.3** | **Exportación e Interoperabilidad** | **CUMPLIDO** | Generación de PDFs letter vectoriales con DomPDF y gráficos SVG autónomos. Exportación XLSX nativa sin dependencias externas pesadas, validada para Excel, LibreOffice y Google Sheets. |
| **FF.4** | **Seguridad y Control de Acceso** | **CUMPLIDO** | Timeout de 15 minutos exactos por inactividad real del usuario; modal no descartable que redirige a `/login`; protección contra session fixation y open redirect; hashes bcrypt con costo 12; sincronización multi-pestaña por `localStorage`. |
| **FF.5** | **Confiabilidad y Tolerancia a Fallos** | **CUMPLIDO** | Entorno de pruebas 100% desacoplado en SQLite memoria (`php artisan test`). Manejo defensivo en controladores ante tablas opcionales o transitorias sin provocar error 500. Plantillas de correo transaccionales con fallback. |
| **FF.6** | **Privacidad Ética y Deontológica** | **CUMPLIDO** | Cumplimiento con principios de NOM-035 y ética psicológica: segregación absoluta entre docentes curriculares y expedientes clínicos; consentimientos informados explícitos; avisos de tamizaje orientador sin diagnósticos automáticos. |

---

## 3. AUDITORÍA DE MIGRACIONES EN EL ENTORNO PROTEGIDO Y TEMPORAL

1. **Migración 000032 (`create_solicitudes_academicas_tables`):**
   - Incorpora `docente_asignaciones.estado`, `docente_asignaciones.solicitado_en` y la tabla `estudiante_asignatura_inscripciones`.
   - **Validada empíricamente en `bienestar_upemor_validacion_i19`** (Lote 16). 0 errores.
2. **Migración 000033 (`create_estudiante_puntos_movimientos_table`):**
   - Crea el libro mayor contable auditado de puntos y ejecuta apertura contable (`SALDO_INICIAL`) para estudiantes con saldos históricos.
   - **Validada empíricamente en `bienestar_upemor_validacion_i19`** (Lote 16). 3 aperturas conciliadas exitosamente (incluyendo los 42 puntos de Manuel Antonio).
3. **Migración 000034 (`enhance_academic_architecture_and_catedras`):**
   - Incorpora `modalidad_alcance` (`ESPECIFICO`, `TODOS`) e índices optimizados en `docente_asignaciones` y `estudiante_asignatura_inscripciones`.
   - **Validada empíricamente en `bienestar_upemor_validacion_i19`** (Lote 16). 0 errores.
4. **Estado en `bienestar_upemor_sandbox` (Puerto 3308) — Incremento 23:**
   - **Esquema Relacional:** Exactamente 50 tablas auditadas y clasificadas (34 operativas, 5 históricas, 3 de auditoría y 8 de soporte Laravel). 0 tablas obsoletas comprobadas. Cero sentencias destructivas (`DROP TABLE` o `TRUNCATE`) ejecutadas.
   - **Comunidad Universitaria:** 32 usuarios (3 administradores, 8 docentes, 17 alumnos, 4 orientadores), 17 expedientes de estudiantes y 42 migraciones registradas.
   - **Trayectoria Académica:** Clasificación en 5 categorías de planes, alerta formal ante programas de plan único (IID), comparativa reactiva con JavaScript, motivo obligatorio $\ge 10$ caracteres, confirmación expresa y registro en `estudiante_plan_historial`.
   - **Asignaciones Docentes y Oferta Alumno:** Elegibilidad y resolución aislada por grupo específico sin colisión con aperturas generales (`TODOS`), desduplicación por `(asignatura_id, docente_id)` priorizando grupo oficial del alumno y prevención de doble clic.
   - **Exportaciones Excel OpenXML:** Corrección de tag mismatch `</c:strRef></c:tx>`, acceso seguro a datos agregados, 2 visualizaciones DrawingML en los 6 reportes institucionales y validación XML estricta con `DOMDocument::loadXML()` con 0 errores de sintaxis.
   - **Documentación y Arquitectura:** Cobertura de bloques PHPDoc y apego a PSR-12 en servicios y controladores; arquitectura MVC limpia sin SQL en plantillas Blade.
   - **Suites Automatizadas del Incremento 23:** `Incremento23AcademicTest` (5 tests, 27 aserciones) y `Incremento23ExcelTest` (8 tests, 165 aserciones) superadas al 100%.

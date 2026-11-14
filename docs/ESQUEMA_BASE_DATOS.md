# Esquema y Diccionario de Base de Datos
## Plataforma Bienestar UPEMOR

Este documento describe la estructura relacional, tablas, restricciones de integridad referencial y llaves del motor de base de datos de **Bienestar UPEMOR** (MariaDB 10.4+ / MySQL 8.0+, motor InnoDB, cotejamiento `utf8mb4_unicode_ci`).

El esquema consta de **50 tablas relacionales** organizadas en 8 subsistemas funcionales.

---

## 1. Subsistema de Identidad, Seguridad y Control de Acceso

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`roles`** | `rol_id` | — | Catálogo de roles institucionales (`ADMIN`, `DOCENTE`, `ALUMNO`, `ORIENTADOR`). |
| **`users`** | `usuario_id` | `rol_id` → `roles` | Cuentas institucionales (`nombre`, `apellidos`, `email` único `@upemor.edu.mx`, `password`, `estado`: `ACTIVO`, `PENDIENTE`, `BLOQUEADO`, `INACTIVO`, `ultimo_acceso`). |
| **`login_accesos`** | `acceso_id` | `usuario_id` → `users` | Registro de auditoría de intentos de inicio de sesión (`ip`, `user_agent`, `exitoso`, `created_at`). |
| **`password_reset_tokens`** | `email` | — | Tokens temporales para restablecimiento seguro de credenciales. |
| **`sessions`** | `id` (string) | `user_id` → `users` | Sesiones web activas con soporte para driver `database`. |

---

## 2. Subsistema Académico y Trayectoria Curricular

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`programas_educativos`** | `programa_id` | — | Carreras universitarias de UPEMOR (`clave`, `nombre`, `descripcion`, `activo`). |
| **`planes_estudio`** | `plan_id` | `programa_id` → `programas_educativos` | Planes curriculares por programa (`clave`, `nombre`, `anio_inicio`, `activo`). |
| **`asignaturas`** | `asignatura_id` | `plan_id` → `planes_estudio` | Materias del plan (`clave`, `nombre`, `cuatrimestre`, `creditos`, `horas_semana`). |
| **`periodos_academicos`** | `periodo_id` | — | Ciclos cuatrimestrales (`codigo` ej. 2026-3, `fecha_inicio`, `fecha_fin`, `activo`). |
| **`periodos_parciales`** | `parcial_id` | `periodo_id` → `periodos_academicos` | Cortes de evaluación parcial dentro de cada periodo. |
| **`grupos`** | `grupo_id` | `periodo_id`, `programa_id`, `plan_id` | Grupos escolares por generación y cuatrimestre. |
| **`docentes`** | `usuario_id` | `usuario_id` → `users` | Perfil del docente (`numero_empleado`, `especialidad`, `cedula_profesional`). |
| **`docentes_asignaciones`** | `asignacion_id` | `usuario_id`, `asignatura_id`, `grupo_id`, `periodo_id` | Cátedras autorizadas a cada docente en un periodo. |
| **`estudiantes`** | `usuario_id` | `usuario_id` → `users`, `programa_id`, `plan_id` | Perfil del alumno (`matricula`, `cuatrimestre`, `grupo`, `saldo_puntos`). |
| **`estudiante_inscripciones`** | `inscripcion_id` | `usuario_id`, `periodo_id`, `grupo_id` | Inscripción cuatrimestral del estudiante. |
| **`estudiante_asignatura_inscripciones`** | `id` | `inscripcion_id`, `asignatura_id`, `docente_asignacion_id` | Carga de materias por alumno y estatus de acreditación. |
| **`estudiante_asignatura_inscripcion_historial`** | `id` | `estudiante_asignatura_inscripcion_id` | Trazabilidad histórica de cambios en la inscripción de materias. |
| **`estudiante_plan_historial`** | `id` | `usuario_id`, `plan_anterior_id`, `plan_nuevo_id`, `autorizado_por` | Bitácora de cambios excepcionales de plan de estudio. |
| **`solicitudes_academicas`** | `solicitud_id` | `usuario_id`, `periodo_id` | Solicitudes y trámites académicos del estudiante. |

---

## 3. Subsistema Socioemocional y Bitácora

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`emociones`** | `emocion_id` | — | Catálogo de emociones básicas (`nombre`, `color_hex`, `icono`, `categoria`, `activo`). |
| **`bitacoras_emocionales`** | `bitacora_id` | `usuario_id` → `users`, `emocion_id` → `emociones` | Registros emocionales (`fecha`, `nivel_intensidad`, `nota_reflexiva`, `privado`). |

---

## 4. Subsistema de Evaluaciones Psicométricas

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`evaluaciones_instrumentos`** | `instrumento_id` | — | Instrumentos clínicos validados (`codigo` DASS-21, `nombre`, `descripcion`, `activo`). |
| **`evaluaciones_versiones`** | `version_id` | `instrumento_id` | Versiones publicadas de cada instrumento de evaluación. |
| **`evaluaciones_preguntas`** | `pregunta_id` | `version_id` | Reactivos o preguntas del cuestionario. |
| **`escalas_opciones`** | `opcion_id` | `instrumento_id` / `version_id` | Escalas de respuesta tipo Likert con valor numérico. |
| **`baremos_reglas`** | `regla_id` | `instrumento_id` / `version_id` | Rangos de puntuación y niveles de severidad (Normal, Leve, Moderado, Severo). |
| **`evaluaciones_consentimientos`** | `consentimiento_id` | `usuario_id`, `instrumento_id` | Registro legal de consentimiento informado digital aceptado por el alumno. |
| **`evaluaciones_intentos`** | `intento_id` | `usuario_id`, `version_id` | Aplicación del test con puntaje total y severidad obtenida. |
| **`evaluaciones_respuestas`** | `respuesta_id` | `intento_id`, `pregunta_id`, `opcion_id` | Respuestas reactivo por reactivo del estudiante. |

---

## 5. Subsistema de Citas de Orientación Psicológica

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`citas_profesionales`** | `profesional_id` | `usuario_id` → `users` | Psicólogos u orientadores autorizados para atención clínica. |
| **`citas_disponibilidad`** | `disponibilidad_id` | `profesional_id` | Bloques semanales de atención (día de la semana, hora inicio, hora fin). |
| **`citas_orientacion`** | `cita_id` | `estudiante_id`, `profesional_id` | Citas agendadas (`fecha_hora`, `estado`, `motivo`, `notas_seguimiento`). |
| **`citas_reprogramacion_historial`** | `historial_id` | `cita_id`, `solicitado_por` | Historial de reprogramaciones horarias y motivos registrados. |

---

## 6. Subsistema de Recursos Multimedia y Meditación

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`meditaciones`** | `meditacion_id` | — | Sesiones guiadas (`titulo`, `categoria`, `duracion_segundos`, `url_multimedia`, `puntos`). |
| **`meditacion_sesiones`** | `sesion_id` | `usuario_id`, `meditacion_id` | Historial de reproducción, pausas y porcentaje completado. |

---

## 7. Subsistema de Gamificación y Recompensas

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`recompensas_catalogo`** | `recompensa_id` | — | Catálogo de beneficios institucionales (`titulo`, `costo_puntos`, `stock`, `activo`). |
| **`recompensas_activadas`** | `activacion_id` | `recompensa_id`, `grupo_id` / `programa_id` | Regulaciones activadas por docentes o administración. |
| **`recompensas_canjes`** | `canje_id` | `usuario_id`, `recompensa_id` | Cupones canjeados por el estudiante (`codigo_canje`, `estado`). |
| **`estudiante_puntos_movimientos`** | `movimiento_id` | `usuario_id` → `users` | Libro mayor de puntos (abonos por bitácora/meditación, cargos por canje). |

---

## 8. Subsistema de Encuestas y Sistema Base

| Tabla | Llave Primaria (PK) | Llaves Foráneas (FK) | Propósito y Columnas Principales |
|---|---|---|---|
| **`encuestas`** | `encuesta_id` | — | Instrumentos de clima institucional y opinión estudiantil. |
| **`encuesta_preguntas`** | `pregunta_id` | `encuesta_id` | Preguntas del instrumento de opinión. |
| **`encuesta_respuestas`** | `respuesta_id` | `encuesta_id`, `usuario_id` | Envío consolidado de la encuesta por usuario. |
| **`encuesta_respuesta_detalles`** | `detalle_id` | `respuesta_id`, `pregunta_id` | Respuestas específicas a cada pregunta de la encuesta. |
| **`cache`, `cache_locks`** | `key` | — | Almacenamiento en caché de consultas y bloqueos atómicos de Laravel. |
| **`jobs`, `job_batches`, `failed_jobs`** | `id` | — | Colas de trabajo en segundo plano para notificaciones y tareas pesadas. |
| **`migrations`** | `id` | — | Registro de versiones y migraciones aplicadas de Laravel. |


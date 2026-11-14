# Manual de Usuario del Sistema Bienestar UPEMOR
## Guía Operativa por Perfiles y Módulos Institucionales

La plataforma **Bienestar UPEMOR** es un entorno institucional diseñado para brindar acompañamiento socioemocional, promover hábitos saludables y dar seguimiento formativo y académico a la comunidad universitaria.

Este manual describe el funcionamiento detallado de los módulos de la aplicación clasificados por perfil de usuario.

---

## 1. Módulos Comunes y Autenticación

### 1.1 Inicio de Sesión Institucional
- **Ruta de Acceso:** `/login`
- **Requisitos:** Correo institucional (`@upemor.edu.mx`) y contraseña registrada.
- **Seguridad Integrada:**
  - Control de fuerza bruta: tras 5 intentos fallidos se bloquea el acceso temporalmente por dirección IP y cuenta de correo.
  - Cierre automático de sesión tras 15 minutos continuos de inactividad.
  - Validación del estado de la cuenta (`ACTIVO`). Las cuentas en estado `PENDIENTE`, `BLOQUEADO` o `INACTIVO` reciben el mensaje correspondiente con instrucciones institucionales.

### 1.2 Registro de Alumnos
- **Ruta de Acceso:** `/registro`
- **Condiciones:** Exclusivo para alumnos con matrícula y correo válido del dominio `@upemor.edu.mx`.
- **Procedimiento:** El alumno ingresa sus nombres, apellidos, correo institucional, matrícula, programa educativo y contraseña. Al completar el formulario, la cuenta queda en estado `PENDIENTE` hasta su revisión y aprobación por la Dirección de Asuntos Estudiantiles.

---

## 2. Perfil Alumno

El portal del alumno (`/alumno/dashboard`) ofrece acceso a herramientas socioemocionales, formativas y académicas:

### 2.1 Bitácora Emocional (`/alumno/bitacora`)
Permite registrar y reflexionar sobre el estado anímico cotidiano:
- **Modos de Visualización:** Calendario con vista mensual, vista semanal interactiva y detalle diario.
- **Registro de Emociones:** Selección de estados emocionales primarios representados mediante esferas dinámicas codificadas por colores, indicando intensidad (1 al 5) y nota contextual opcional.
- **Historial Formativo:** Gráfica de evolución emocional que permite al estudiante identificar patrones de estrés o bienestar en periodos de exámenes y entregas de proyectos.

### 2.2 Evaluaciones Psicométricas (`/alumno/evaluaciones`)
Tamizaje estandarizado de salud mental estudiantil:
- **Consentimiento Informado:** Todo test psicológico requiere la lectura y aceptación digital de los términos éticos de confidencialidad institucional antes de comenzar.
- **Instrumentos Disponibles:** DASS-21 (Depresión, Ansiedad y Estrés) y escalas de bienestar universitario.
- **Interpretación Segura:** Al concluir el cuestionario, el sistema calcula de forma inmediata la puntuación mediante baremos clínicos estandarizados y muestra una semaforización orientativa con recomendaciones de autocuidado y opción de solicitar cita si se detecta riesgo moderado o severo.

### 2.3 Citas de Orientación Psicológica (`/alumno/citas`)
- **Solicitud de Cita:** Permite seleccionar la fecha y horario disponible del equipo de psicología institucional.
- **Gestión de Citas:** Visualización del estado de la cita (`PENDIENTE`, `CONFIRMADA`, `ATENDIDA`, `CANCELADA`, `REPROGRAMADA`).
- **Reprogramación Justificada:** El estudiante puede solicitar cambio de horario con al menos 24 horas de anticipación, registrando el motivo correspondiente.

### 2.4 Meditaciones Interactivas (`/alumno/meditaciones`)
- **Catálogo Multimedia:** Biblioteca de sesiones guiadas en audio y video enfocadas en respiración consciente, relajación muscular y concentración académica.
- **Reproductor Interactivo:** Permite pausar, reanudar y registrar el tiempo completado, acumulando puntos de bienestar para el sistema de recompensas.

### 2.5 Sistema de Recompensas (`/alumno/recompensas`)
- **Saldo de Puntos:** Los puntos se obtienen mediante la constancia en bitácoras, meditaciones y asistencia a citas de orientación.
- **Catálogo de Beneficios:** Cupones de descuento en cafetería, acceso preferente a talleres formativos y reconocimientos institucionales.
- **Canje:** Generación de código único de canje para su validación en los puntos autorizados del campus.

### 2.6 Trayectoria Académica del Alumno (`/alumno/academico`)
- Consulta del plan de estudios vigente asignado, materias cursadas en el cuatrimestre, estatus regular o irregular y avance curricular.

---

## 3. Perfil Docente

El portal docente (`/docente/dashboard`) está diseñado para el acompañamiento académico y formativo:

### 3.1 Cátedras y Grupos Asignados (`/docente/academico`)
- Visualización de la lista de grupos y asignaturas autorizadas por la dirección académica para el periodo activo.
- Consulta de la lista de estudiantes inscritos por grupo y cuatrimestre.

### 3.2 Aprobación Cuatrimestral de Asignaturas
- Formulario de validación académica que permite al docente confirmar la acreditación o el estatus de las materias correspondientes a su grupo en el cierre de ciclo institucional.

---

## 4. Perfil Orientador / Psicólogo

El portal de orientación (`/docente/citas` y módulos clínicos especializados) garantiza la atención confidencial:

### 4.1 Agenda y Disponibilidad Horaria
- Definición de bloques de atención semanal (días, horas de inicio y fin, duración de sesión y cupos).
- Prevención algorítmica de citas empalmadas o solapamientos de horario.

### 4.2 Gestión de Citas y Expedientes
- Bandeja de solicitudes entrantes para confirmación o reprogramación asistida.
- Registro de notas de seguimiento y asistencia posterior a cada sesión, almacenadas con aislamiento estricto de privacidad (no accesibles para docentes ni público general).

---

## 5. Perfil Administrador

El panel administrativo (`/admin/dashboard`) centraliza la gobernanza integral de la institución:

### 5.1 Gestión de Usuarios y Solicitudes (`/admin/solicitudes`, `/admin/usuarios`)
- Aprobación o rechazo de solicitudes de registro de alumnos con verificación de matrícula y programa educativo.
- Administración del catálogo de usuarios: activación, inactivación o bloqueo de cuentas por razones disciplinarias o administrativas.

### 5.2 Estructura Académica (`/admin/carreras`, `/admin/planes`, `/admin/periodos`, `/admin/grupos`)
- Catálogo de Programas Educativos (Ingeniería en Tecnologías de la Información, Biotecnología, etc.).
- Gestión de Planes de Estudio por generación y periodo cuatrimestral.
- Asignación de docentes a materias y grupos.

### 5.3 Ficha Administrativa de Trayectoria Académica (`/admin/trayectoria`)
- Expediente de trayectoria por estudiante: historial de asignaturas, cuatrimestre cursado, condición regular o irregular.
- Procedimiento formal de cambio excepcional de plan de estudios con registro de justificación y bitácora de auditoría.

### 5.4 Reportes Institucionales Analíticos (`/admin/reportes`)
La plataforma genera 6 reportes institucionales automatizados:
1. **R1: Participación y Uso de la Plataforma** (volumen de accesos, actividad por carrera).
2. **R2: Evaluaciones Psicométricas y Tamizaje** (distribución de niveles de estrés, ansiedad y depresión).
3. **R3: Citas de Orientación y Cobertura Psicológica** (atención por periodo, tasas de asistencia y motivos).
4. **R4: Uso de Meditaciones y Recursos Multimedia** (minutos consumidos, recursos más reproducidos).
5. **R5: Recompensas y Gamificación Estudiantil** (puntos otorgados, beneficios canjeados por cuatrimestre).
6. **R6: Trayectoria Académica y Estatus Curricular** (alumnos regulares vs irregulares por plan y generación).

**Capacidades de los Reportes:**
- Visualización interactiva en pantalla con **exactamente dos gráficas analíticas por reporte**.
- Exportación formal en **PDF** con cabecera y membrete institucional UPEMOR.
- Exportación nativa en **Excel** (OpenXML ISO/IEC 29500) con tablas de datos estructuradas.

### 5.5 Respaldos del Sistema (`/admin/respaldos`)
- Generación de respaldos completos de la base de datos MariaDB/MySQL mediante comando criptográfico seguro.
- Cálculo automático de huella digital **SHA-256** para certificar la integridad del archivo.
- Descarga segura y protocolo de restauración verificado.


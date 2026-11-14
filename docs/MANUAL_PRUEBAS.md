# Manual de Pruebas Automatizadas y Calidad de Software
## Plataforma Bienestar UPEMOR

Este documento describe la suite de aseguramiento de calidad (QA), arquitectura de pruebas automatizadas y criterios de validación de la plataforma **Bienestar UPEMOR**.

---

## 1. Resumen Ejecutivo de la Suite de Pruebas

El sistema cuenta con una cobertura integral de pruebas que garantiza la robustez de las reglas de negocio, la integridad de los datos clínicos, la concurrencia en la asignación de citas y la correcta exportación de reportes institucionales.

| Métrica | Valor Certificado |
|---|---|
| **Total de Pruebas Automatizadas** | **522 pruebas** |
| **Total de Aserciones Verificadas** | **2,605 aserciones** |
| **Tasa de Aprobación** | **100.0% (0 errores, 0 fallos)** |
| **Entorno de Pruebas** | PHPUnit 11.x sobre SQLite en memoria |
| **Tiempo de Ejecución Típico** | ~2 minutos |

---

## 2. Organización y Cobertura de las Suites (`tests/`)

Las pruebas se dividen en pruebas de integración/funcionales (`tests/Feature/`) y pruebas unitarias (`tests/Unit/`):

### 2.1 Seguridad, Autenticación y Autorización
- `AuthTest`: Flujo de inicio de sesión, credenciales incorrectas, protección contra fuerza bruta (bloqueo tras 5 intentos fallidos), verificación de estado de cuenta (`ACTIVO`, `PENDIENTE`, `BLOQUEADO`, `INACTIVO`).
- `RegisterTest`: Registro público de alumnos con correo institucional `@upemor.edu.mx`, validación de matrícula única y asignación a programas educativos válidos.
- `AdminSolicitudesTest`: Aprobación y rechazo administrativo de solicitudes de aspirantes.
- `MultiRolesTest` y `OrientadorSeparacionTest`: Verificación de aislamiento estricto de roles (RBAC) y prevención de acceso cruzado entre perfiles docente, administrador y orientador.
- `PasswordResetFlowTest`: Flujo seguro de restablecimiento de contraseña mediante tokens temporales.

### 2.2 Módulo Socioemocional y Evaluaciones Psicométricas
- `BitacoraTest`: Registro de emociones cotidianas, validación de intensidad (escala 1 a 5), filtros por fecha y consistencia en vistas diaria, semanal y mensual.
- `EvaluacionModelTest`, `EvaluacionPolicyTest`, `EvaluacionCatalogoTest`: Permisos de acceso confidencial sobre cuestionarios clínicos.
- `EvaluacionCuestionarioTest` y `EvaluacionHistorialTest`: Flujo de consentimiento informado digital, captura de respuestas y consulta de historial.
- `EvaluacionScoringTest`: Verificación de baremos y algoritmos de cálculo de severidad (Normal, Leve, Moderado, Severo).

### 2.3 Citas de Orientación y Concurrencia
- `CitaSolicitudTest` y `CitaGestionTest`: Ciclo de vida completo de la cita (`PENDIENTE`, `CONFIRMADA`, `ATENDIDA`, `CANCELADA`, `REPROGRAMADA`).
- `DisponibilidadSolapamientoTest`: Validación algorítmica para impedir bloques de disponibilidad duplicados o empalmados.
- `CitaConcurrenciaTest`: Simulación de solicitudes simultáneas en el mismo horario para verificar prevención de sobrecupos y bloqueos pesimistas.

### 2.4 Trayectoria Académica y Gobernanza Curricular
- `Incremento18AcademicoArquitecturaTest`: Estructura curricular, planes de estudio y cátedras docentes.
- `AcademicoDocenteTest`: Validación y aprobación de materias por parte de los docentes titulares.
- `Incremento22TrayectoriaYAdversasTest`: Casos de borde en trayectoria, alumnos con asignaturas reprobadas y determinación de estatus regular vs irregular.
- `Incremento23AcademicTest`: Cambio excepcional de plan de estudios con bitácora de auditoría y elegibilidad cuatrimestral de asignaturas.

### 2.5 Gamificación, Recompensas y Meditaciones
- `RecompensasTest` y `CurricularYRecompensasTest`: Saldo de puntos, abonos por actividades socioemocionales y deducción transaccional en canjes.
- `MeditacionesTest` y `MeditacionVideoInteractivoTest`: Catálogo multimedia, registro de tiempo de reproducción y pausas.

### 2.6 Reportes Institucionales, Exportaciones y Respaldos
- `ReportesInstitucionalesTest`: Extracción de métricas de los 6 reportes institucionales, generación de vistas HTML y compilación a PDF con DomPDF.
- `Incremento23ExcelTest`: Validación de generación de hojas de cálculo OpenXML (ISO/IEC 29500), sintaxis XML válida y estructuras de tabla consistentes.
- `ReporteGraficaServiceTest` (Unit): Verificación de que cada reporte genera exactamente dos visualizaciones gráficas con series analíticas completas.
- `RespaldosAdminTest`: Ejecución del comando de respaldo, verificación del archivo SQL generado y cálculo de integridad SHA-256.

---

## 3. Instrucciones de Ejecución de Pruebas

### 3.1 Ejecución Completa de la Suite
Abra una terminal en la raíz del proyecto y ejecute:

```bash
php vendor/bin/phpunit
```
*O alternativamente con el asistente de Laravel:*
```bash
php artisan test
```

### 3.2 Ejecución de una Suite Específica
Para ejecutar únicamente las pruebas de un módulo concreto:

```bash
# Pruebas de Trayectoria Académica:
php vendor/bin/phpunit tests/Feature/Incremento23AcademicTest.php

# Pruebas de Reportes Institucionales:
php vendor/bin/phpunit tests/Feature/ReportesInstitucionalesTest.php

# Pruebas de Exportación Excel:
php vendor/bin/phpunit tests/Feature/Incremento23ExcelTest.php

# Pruebas de Concurrencia de Citas:
php vendor/bin/phpunit tests/Feature/CitaConcurrenciaTest.php
```

### 3.3 Ejecución con Modo de Detalle (Verbose)
```bash
php artisan test --testdox
```

---

## 4. Entorno de Aislamiento de Pruebas

El archivo de configuración `phpunit.xml` asegura que las pruebas se ejecuten en un entorno completamente aislado:

- **Base de Datos:** SQLite en memoria (`:memory:`).
- **Aislamiento Transaccional:** Cada prueba utiliza el trait `RefreshDatabase`, creando y destruyendo las tablas en memoria sin alterar las bases de datos locales MariaDB/MySQL.
- **Servicios Simulados:** Las notificaciones por correo y eventos externos utilizan controladores falsos (`Mail::fake()`, `Event::fake()`) para no enviar comunicaciones reales durante las pruebas.


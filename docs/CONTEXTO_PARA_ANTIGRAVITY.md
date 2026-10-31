# Contexto técnico para Antigravity — Bienestar UPEMOR

Memoria técnica de transferencia. Generada por inspección del repositorio real (no de planes). Fecha de inspección: 2026-10-30.
Leyenda de estado: **IMPLEMENTADO** (código + pruebas), **PARCIAL**, **PREPARADO** (existe estructura sin lógica), **PENDIENTE** (no existe).

> **Hallazgo importante:** el módulo de aprobación/rechazo de solicitudes de alumnos **ya está implementado** (sin commit) con 18 pruebas. Lo pendiente antes de seguir es validar y aplicar de forma segura las migraciones en MariaDB (sección J).

---

## A. Visión general

### Objetivo
Sistema de bienestar emocional para alumnos de UPEMOR, con acompañamiento por docentes/psicólogos y administración institucional. Proyecto de estadía académica.

### Alcance y requisitos identificables en el código
No hay documento de requisitos en el repositorio. Solo se infiere del código: `CheckSessionTimeout` cita los requisitos «FF.4 / RD.2» (cierre por 15 min de inactividad). Requisitos funcionales verificables: autenticación por roles, registro de alumnos con aprobación administrativa, control de estado de cuenta, protección contra fuerza bruta. Los módulos de bienestar solo se infieren por columnas preparadas (`saldo_puntos`, `permite_visualizacion`) y por el propósito; **no hay evidencia de requisitos detallados**. Pedir al usuario el documento de requisitos y los bocetos originales (no están en el repo).

### Arquitectura
- Laravel 12.69.2, PHP 8.2.12, MVC con Blade, Eloquent, sesiones web (guard `web`, provider Eloquent sobre `App\Models\User`).
- Frontend: Vite 7 + Bootstrap 5.3 (npm) con CSS propio. Tailwind 4 está instalado/registrado en `vite.config.js` pero las vistas actuales no lo usan.
- Autorización por middleware de rol; **no existen Policies ni Gates** (`app/Policies` no existe).
- Idioma de la UI y mensajes: español (mensajes escritos a mano en controladores/Form Request).

### Convenciones
- Tablas y columnas en español, PK personalizadas (`usuario_id`, `rol_id`, `programa_id`); modelos declaran `$table`/`$primaryKey`.
- Perfiles con PK = FK (`estudiantes.usuario_id`, `docentes.usuario_id`, `$incrementing = false`).
- Controladores de administración bajo `App\Http\Controllers\Admin`, vistas en `resources/views/admin/...`.
- Layouts: `layouts/auth.blade.php` y `layouts/admin.blade.php`; componente anónimo `<x-auth.illustration />`.
- Pruebas Feature con `RefreshDatabase`.
- Archivos de texto con CRLF en Windows (git avisa de conversión a LF).

---

## B. Base de datos

### Motor
- Real: `.env` local usa `DB_CONNECTION=mysql` (host local, puerto no estándar). Se asume MariaDB; **la versión exacta no está confirmada**. No se consultó la BD.
- Pruebas: SQLite `:memory:` (definido en `phpunit.xml`). `.env.example` y `config/database.php` por defecto apuntan a SQLite.
- Existe `database/database.sqlite` (ignorado por git; no usado por las pruebas).

### Tablas (según migraciones)
| Tabla | PK | Notas |
|---|---|---|
| `roles` | `rol_id` | `nombre` (40), `descripcion`, `activo`; **único en `nombre` solo tras migración 000006** |
| `programas_educativos` | `programa_id` | `clave` única (20), `nombre`, `descripcion`, `activo` |
| `users` | `usuario_id` | `rol_id` FK→roles (cascade), nombre, apellidos, `email` único, `password`, `telefono`, `estado`, `email_verified_at`, `ultimo_acceso`, `remember_token` |
| `estudiantes` | `usuario_id` (también FK→users, cascade) | `programa_id` FK→programas_educativos (cascade), `matricula` única (25), `cuatrimestre`, `grupo`, `fecha_ingreso`, `saldo_puntos` (0), `permite_visualizacion` (false) |
| `docentes` | `usuario_id` (FK→users, cascade) | `numero_empleado` único, `puesto`, `especialidad`, `cedula_profesional`, `activo` |
| `password_reset_tokens`, `sessions` | — | Creadas en migración de users; sin uso de recuperación aún |
| `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | — | Migraciones estándar de Laravel |

Relaciones Eloquent: `User` belongsTo `Rol`; `User` hasOne `Estudiante` y `Docente`; `Estudiante` belongsTo `User` y `ProgramaEducativo`; `ProgramaEducativo` hasMany `Estudiante`; `Rol` hasMany `User`; `Docente` belongsTo `User`.

### Estados de usuario (`users.estado`)
`ACTIVO`, `INACTIVO`, `BLOQUEADO` (migración original) + `PENDIENTE`, `RECHAZADO` (migración 000006, que además cambia el default de `ACTIVO` a `PENDIENTE`). Solo `ACTIVO` puede iniciar sesión.

### Migraciones: preparadas vs aplicadas
- Preparadas en el repositorio: las 8 de `database/migrations/`. La `2026_01_01_000006_add_account_statuses_and_unique_role_name.php` está **sin commit**.
- Aplicadas en MariaDB real: **NO VERIFICADO** (no se consultó la tabla `migrations`; prohibido tocar la BD). Debe confirmarlo el usuario. Tampoco se sabe si 000001–000005 están aplicadas.
- La 000006 en MariaDB hace `ALTER TABLE users MODIFY estado ENUM(...) NOT NULL DEFAULT 'PENDIENTE'` y `unique('nombre')` en `roles`. El `unique` **fallará si ya hay nombres de rol duplicados**. Su `down()` lanza excepción si existen cuentas PENDIENTE/RECHAZADO.
- La rama MariaDB de esta migración **no está probada** automáticamente (solo la rama sqlite).

### Seeders
- `DatabaseSeeder`: inserta roles con IDs fijos 1=Administrador, 2=Docente, 3=Alumno; seis programas históricos con IDs fijos 1–6 (`ITI`, `IFI`, `IET`, `IBT`, `ITE`, `INDUSTRIAL`); un administrador y un docente de prueba con contraseñas tomadas de `INITIAL_ADMIN_PASSWORD` / `INITIAL_DOCENTE_PASSWORD` (≥12 caracteres, aborta si faltan). **No es idempotente** (insert directo con IDs: falla si se repite). Ejecución real: **desconocida**; el `.env` actual no contiene esas variables, lo que sugiere que no se ejecutó con esta versión.
- `ProgramasEducativosRegistroSeeder`: idempotente (`updateOrInsert` por `clave`). Ejecución real: **no verificada; se asume pendiente**. Ver sección E.
- No se ejecutó ningún seeder durante esta documentación.

### Diferencias SQLite (pruebas) vs MariaDB (real)
- `enum` en SQLite es `varchar` + `CHECK`; el cambio se hace con `->change()`; en MariaDB con `ALTER ... MODIFY`. Solo la primera ruta está cubierta por pruebas.
- Colación: MariaDB es insensible a mayúsculas por defecto (unicidad y `LIKE`); SQLite distingue mayúsculas en comparaciones de igualdad/UNIQUE. Una prueba en SQLite no demuestra que `Alumno@upemor.edu.mx` y `alumno@upemor.edu.mx` choquen igual en MariaDB.
- Cumplimiento de FKs, longitudes de `varchar` y modo estricto difieren (SQLite no impone longitud).
- Cache/sesión: pruebas usan `array`; local real usa sesión `file`.

### Riesgos de integridad
1. `users.rol_id` y `estudiantes/docentes` usan `ON DELETE CASCADE`: borrar un rol, programa o usuario elimina en cascada perfiles y datos futuros ligados.
2. `AuthController::redirectUserByRole()` usa IDs de rol fijos (1/2/3), mientras la autorización usa nombres: inconsistente si los IDs reales difieren del seeder.
3. Default `estado='PENDIENTE'` afecta cualquier `INSERT` futuro que omita el campo (p. ej. altas de docentes/administradores deben fijar estado explícitamente).
4. Unicidad de correo/matrícula validada en Form Request y respaldada por UNIQUE; una carrera concurrente duplicada produciría error de BD (500) sin captura específica.
5. Programas `ITI`, `IET`, `ITE`, `INDUSTRIAL` quedan activos si el seeder histórico se ejecutó; el filtro por clave protege el registro, pero siguen en BD.
6. IDs de programas reales pueden diferir de los del seeder histórico; por eso la validación usa `clave`, no `programa_id` fijo.
7. No hay auditoría de quién aprobó/rechazó ni cuándo (no existen columnas ni tabla).

---

## C. Roles y seguridad

### Roles
`Administrador`, `Docente`, `Alumno` (tabla `roles`, comparados por **nombre** en el middleware `role:`).

### Autenticación (`AuthController`)
- `GET /login`, `POST /login`, `POST /logout`. Validación: `email` requerido/válido, `password` requerida.
- Intenta `Auth::attempt` con `estado = 'ACTIVO'` en las credenciales; mensaje genérico para no revelar la causa del rechazo.
- Tras éxito: `session()->regenerate()`, actualiza `ultimo_acceso`, redirige por rol a `admin.dashboard`, `docente.dashboard` o `alumno.dashboard`.
- Ya autenticado, `/login` redirige al panel.

### Autorización de rutas (`routes/web.php`)
Grupo `auth` + `account.active` + `session.timeout`; dentro, `role:Administrador`, `role:Docente`, `role:Alumno`. Alias definidos en `bootstrap/app.php`.
- `EnsureUserHasRole`: 403 si el rol no coincide.
- `EnsureUserIsActive`: si `estado !== 'ACTIVO'`, cierra sesión y redirige a login (cubre cuentas desactivadas mientras tienen sesión).

### Rate limiting
`RateLimiter` manual: clave `login|email|ip`, 5 intentos fallidos, ventana 60 s; el 6.º devuelve HTTP 429 con mensaje genérico; éxito limpia el contador. Usa el store de caché configurado. El registro (`POST /registro`) **no tiene throttle**.

### Sesiones, inactividad y Recordarme
- `CheckSessionTimeout`: 15 min de inactividad (`last_activity_time` en sesión); solo corre en rutas protegidas. `SESSION_LIFETIME` en `.env.example` es 120 min (el límite efectivo más estricto es 15 min).
- Observación: `last_activity_time` se establece en la primera petición protegida, no en el login.
- «Recordarme»: si no se marca, se elimina cookie recaller y se rota `remember_token`. Si se marca, se usa el recaller de Laravel (duración por defecto del framework); la relación exacta entre una sesión restaurada por recaller y el timeout de 15 min **no está cubierta por pruebas**. Validar con el usuario si «Recordarme» debe respetar el límite de inactividad.
- `logout` es POST con CSRF, invalida sesión y regenera token.

### Medidas implementadas
Hash de contraseña (cast `hashed`), CSRF (formularios con `@csrf`), regeneración de sesión, mensajes genéricos, estado obligatorio, rol asignado solo en servidor, transiciones de estado atómicas, `whereNumber` en rutas admin, escape Blade, bloqueo de autogestión del admin.

### Medidas pendientes
Recuperación de contraseña, verificación de correo, throttle en registro, auditoría de acciones administrativas, políticas/Gates de datos, 2FA (no solicitado), bloqueo por política de contraseñas más fuerte (solo `min:12` + confirmación), revisión de cookies/headers para producción, `APP_DEBUG` debe ser false en producción.

### Acceso a información psicológica
**No existen tablas ni código de información psicológica.** Reglas obligatorias para el futuro: el rol Docente por sí solo no da acceso; debe existir una relación/consentimiento explícito (candidato: `estudiantes.permite_visualizacion`, hoy `false` por defecto y sin lógica) más una política verificable y auditada. No inventar datos psicológicos, ni siquiera de ejemplo en seeders.

---

## D. Registro de alumnos — IMPLEMENTADO

- Rutas: `GET /registro` (`register`) → `RegisterController@create`; `POST /registro` (`register.store`) → `RegisterController@store`.
- Form Request: `App\Http\Requests\RegisterAlumnoRequest`. `authorize()` devuelve `true`.
- Vista: `resources/views/auth/register.blade.php` (layout `auth`), enlace desde login («¿Eres estudiante? Regístrate»).

### Campos y validaciones
| Campo | Reglas |
|---|---|
| `nombre`, `apellido_paterno` | requerido, string, máx 80 |
| `apellido_materno` | opcional, máx 80 |
| `email` | requerido, email, máx 150, `unique:users,email`, `ends_with:@upemor.edu.mx` |
| `matricula` | requerido, máx 25, `unique:estudiantes,matricula` |
| `programa_id` | requerido, entero, existe en `programas_educativos` con `activo=true` y `clave` en `CARRERAS_PERMITIDAS` |
| `cuatrimestre` | opcional, entero 1–10 |
| `grupo` | opcional, máx 20 (**el formulario no tiene este campo**; solo lo acepta el backend) |
| `password` | requerido, mín 12, `confirmed`; `password_confirmation` requerido |

### Correo institucional
Restricción existente: sufijo exacto `@upemor.edu.mx` vía `ends_with` (distingue mayúsculas; no hay normalización a minúsculas). **Nivel de confirmación: solo formato de texto.** No se comprueba que el buzón exista ni que sea del solicitante (sin verificación de correo), por eso la aprobación administrativa es el control real. Puntos a decidir: normalizar mayúsculas, subdominios.

### Rol, estado y creación
- El rol **no viene del request**: se busca `Rol` con nombre `Alumno` y `activo=true` (`firstOrFail`); campos extra como `rol_id` se ignoran (prueba `test_role_cannot_be_manipulated`).
- Estado fijo `PENDIENTE`; `permite_visualizacion=false`.
- `DB::transaction`: crea `User` y `Estudiante` juntos (prueba de atomicidad existe).
- Después: redirige a `login` con mensaje de éxito. **No inicia sesión.** El mensaje promete «serás notificado», pero **no existe mecanismo de notificación**.
- `fecha_ingreso` y `saldo_puntos` no se capturan.

### Pruebas (`tests/Feature/RegisterTest.php`, 14)
Formulario visible; registro válido queda PENDIENTE; matrícula/correo duplicados; contraseña débil; correo no institucional; programa inactivo, histórico e inexistente; rol manipulado; creación transaccional; cuenta pendiente no inicia sesión; enlace en login; solo seis carreras en el formulario.

---

## E. Catálogo de carreras — IMPLEMENTADO (datos reales: PENDIENTES de verificar)

Seis programas permitidos (clave — nombre):
- IFI — Ingeniería Financiera
- LAD — Licenciatura en Administración
- IID — Ingeniería en Tecnologías de la Información e Innovación Digital
- ISE — Ingeniería en Sistemas Electrónicos
- IBT — Ingeniería en Biotecnología
- IAS — Ingeniería Ambiental y Sustentabilidad

- **Filtrado en el formulario:** `RegisterController::create()` consulta `ProgramaEducativo` con `activo=true` y `whereIn('clave', CARRERAS_PERMITIDAS)`, ordenado por nombre. Constante: `RegisterController::CARRERAS_PERMITIDAS = ['IBT','IAS','IID','IFI','LAD','ISE']`.
- **Validación en servidor:** `Rule::exists` en `RegisterAlumnoRequest` con la misma restricción (un `programa_id` histórico o inactivo se rechaza).
- **Seeder idempotente:** `ProgramasEducativosRegistroSeeder` hace `updateOrInsert` por `clave` con nombre y `activo=true`; agrega las faltantes y renombra IFI e IBT si difieren. Nunca borra ni modifica otros programas.
- **Históricos conservados:** `ITI`, `IET`, `ITE`, `INDUSTRIAL` (del `DatabaseSeeder`) no se tocan; quedan fuera del registro por la lista blanca. Si algún alumno existente depende de ellos sigue intacto.
- **Datos reales:** **no hay evidencia de que el seeder se haya ejecutado en MariaDB.** Hasta que se ejecute (con autorización), el formulario real puede mostrar menos de seis carreras. Las pruebas crean los programas por fábrica.
- Detalle: `ProgramaEducativoFactory` genera claves aleatorias de 3 letras; las pruebas fijan las seis claves explícitamente.

---

## F. Interfaz y diseño

- **Identidad:** marca «Bienestar UPEMOR» (corazón ♡ + texto), estilo lavanda suave, tarjetas redondeadas, sombras ligeras. Variables CSS en `resources/css/bienestar-bootstrap.css` (527 líneas): `--bienestar-primary #6f42c1`, `-primary-dark #56329a`, `-lavender #eee7ff`, `-lavender-soft #f8f5ff`, `-background #f6f4fb`, `-text #262238`, `-border #e3dff0`; se mapean a variables `--bs-*` de Bootstrap.
- **Layout de autenticación** (`layouts/auth.blade.php`): `body.bienestar-theme.auth-page`, `main.auth-shell`; carga solo `bienestar-bootstrap.css` y `bootstrap-app.js` por `@vite`; `<meta theme-color #6f42c1>`.
- **Login** (`auth/login.blade.php`): panel de bienvenida («Bienvenido de vuelta») + ilustración + tarjeta de «Iniciar Sesión» con correo, contraseña (con control de visibilidad), «Recordarme» y enlace a registro. **No hay enlace «¿Olvidaste tu contraseña?»** porque la función no existe.
- **Registro** (`auth/register.blade.php`): panel «Crea tu cuenta» con beneficios, formulario de los campos de la sección D (sin `grupo`).
- **Ilustración:** componente `resources/views/components/auth/illustration.blade.php` (SVG inline: sol, hojas, corazón, carita).
- **Panel admin** (`layouts/admin.blade.php`): barra superior con marca, navegación («Solicitudes de alumnos») y botón cerrar sesión; vista `admin/solicitudes/index.blade.php` con filtro de estado, búsqueda, tabla, insignias de estado y confirmación JS (`data-confirm`).
- **Responsividad:** media queries en `bienestar-bootstrap.css` (≤767.98px, dos bloques) y `prefers-reduced-motion`. Resultado en dispositivos reales: **no verificado**.
- **Dashboards:** `dashboard_test.blade.php` es un placeholder con Bootstrap por CDN y estilo oscuro (**no** sigue el diseño lavanda) compartido por los tres roles. `welcome.blade.php`, `resources/css/app.css` y `resources/js/app.js` son restos del esqueleto sin uso en el flujo actual (`/` redirige a login).
- **Bocetos originales:** no están en el repositorio; no se pudo comparar boceto vs implementación.
- **Verificación visual:** en esta tarea solo se verificó que `npm run build` compila y que las pruebas renderizan las vistas; **no se inspeccionó visualmente en navegador**. Cualquier afirmación de que «se ve bien» debe contrastarse con el usuario.

---

## G. Estado de funcionalidades (según código real)

| Funcionalidad | Estado | Nota |
|---|---|---|
| Login/logout, rate limiting, timeout, estado ACTIVO | IMPLEMENTADO | Con pruebas |
| Registro público de alumnos | IMPLEMENTADO | Sección D |
| Aprobación/rechazo administrativo de alumnos | IMPLEMENTADO (sin commit) | `Admin\SolicitudAlumnoController`, rutas `admin.solicitudes.*`, 18 pruebas. Solo `PENDIENTE→ACTIVO/RECHAZADO`; sin motivo de rechazo, sin auditoría, sin notificación, un rechazado no puede reabrirse aquí |
| Alta de docentes por administrador | PENDIENTE | Tabla `docentes` lista; sin ruta ni vista |
| Alta de administradores por administrador | PENDIENTE | Sin ruta |
| Recuperación de contraseña | PENDIENTE | Solo tabla `password_reset_tokens` y config `auth.passwords`; `MAIL_MAILER=log` |
| Verificación de correo | PENDIENTE | Solo columna `email_verified_at` (el modelo no implementa `MustVerifyEmail`) |
| Dashboards diferenciados por rol | PARCIAL | Rutas por rol existen; vista placeholder única |
| Evaluaciones emocionales | PENDIENTE | Sin tablas/código |
| Bitácora emocional y consentimientos | PENDIENTE | Solo `permite_visualizacion` sin lógica |
| Citas | PENDIENTE | |
| Recursos educativos / meditación | PENDIENTE | |
| Encuestas | PENDIENTE | |
| Recompensas | PREPARADO | Solo `estudiantes.saldo_puntos` |
| Notificaciones | PENDIENTE | La tabla de jobs existe, sin notificaciones; el mensaje de registro promete una |
| Reportes | PENDIENTE | |
| Gestión de usuarios (bloquear/inactivar/editar), auditoría, perfil propio | PENDIENTE | Identificado por ausencia; no es requisito confirmado |

---

## H. Pruebas y validación

Resultado ejecutado en esta tarea: `php artisan test` → **55 aprobadas, 211 aserciones, ~33 s** (SQLite en memoria; sin tocar MariaDB). `npm run build` → exitoso (Vite 7.3.7, 118 módulos).

| Archivo | Casos | Cobertura |
|---|---|---|
| `tests/Feature/AuthTest.php` | 17 | Formulario, login activo, credenciales inválidas, límite de 5/6 intentos, independencia por correo+IP, limpieza del contador, bloqueo de INACTIVO/BLOQUEADO/PENDIENTE/RECHAZADO, redirección por rol, logout, cierre de sesión si pasa a inactivo/bloqueado, aislamiento de dashboards, invitados |
| `tests/Feature/RegisterTest.php` | 14 | Ver sección D |
| `tests/Feature/AdminSolicitudesTest.php` | 18 | Invitados, 403 a no-admin, listado y filtros, búsqueda, aprobar/rechazar, no repetir acciones, no tocar BLOQUEADO/INACTIVO/no alumnos/sin perfil, autogestión, solicitud inexistente, rechazo de GET |
| `tests/Feature/MigrationStatesTest.php` | 4 | Estados aceptados, default PENDIENTE, `roles.nombre` único, `down()` protegido |
| `ExampleTest` (Feature y Unit) | 2 | Del esqueleto |

No probado: migración en MariaDB, seeders (ninguno), timeout de 15 min y «Recordarme» con sesión restaurada, throttle de registro, CSRF, XSS en búsqueda, concurrencia, responsividad/accesibilidad, rendimiento. Limitaciones SQLite vs MariaDB: sección B.

---

## I. Estado actual de Git (al 2026-10-30)

- Rama: `master`. Último commit: `6a1ef1c Seguridad de autenticacion y autorizacion por roles` (anterior: `78f77e4 Respaldo inicial del proyecto Laravel`).
- **Modificados sin commit:** `app/Models/Docente.php`, `app/Models/Estudiante.php`, `app/Models/ProgramaEducativo.php`, `app/Models/User.php`, `package.json`, `resources/views/auth/login.blade.php`, `routes/web.php`, `tests/Feature/AuthTest.php`, `vite.config.js`; además `README.md` (reescrito en esta tarea) .
- **Nuevos sin seguimiento:** `app/Http/Controllers/Admin/`, `app/Http/Controllers/RegisterController.php`, `app/Http/Requests/`, `database/factories/ProgramaEducativoFactory.php`, la migración `2026_01_01_000006_*`, `database/seeders/ProgramasEducativosRegistroSeeder.php`, `package-lock.json`, `resources/css/bienestar-bootstrap.css`, `resources/js/bootstrap-app.js`, `resources/views/admin/`, `resources/views/auth/register.blade.php`, `resources/views/components/`, `resources/views/layouts/`, `tests/Feature/AdminSolicitudesTest.php`, `MigrationStatesTest.php`, `RegisterTest.php`, y `docs/`.
- **Todo lo anterior debe conservarse**: es el trabajo reciente (registro, aprobación, diseño, pruebas) y aún no está respaldado en un commit.
- `.env`, `*.sqlite`, `public/build` están ignorados por git.
- **No hacer commit ni push automáticamente.** El usuario decide cuándo.

---

## J. Próximos pasos recomendados (orden)

1. **Confirmar con el usuario** el estado real de MariaDB (tabla `migrations`, versión, duplicados en `roles.nombre`, programas existentes) — solo lectura y con su autorización; respaldar antes de cualquier cambio.
2. **Aplicar de forma segura la migración 000006** en MariaDB (primero en una copia/restauración del respaldo) y ejecutar `ProgramasEducativosRegistroSeeder` (idempotente) con autorización.
3. Probar manualmente en navegador: registro → solicitud pendiente → aprobación → login (y rechazo).
4. Decidir si se hace commit del trabajo actual (lo decide el usuario).
5. Cerrar huecos del módulo existente: motivo de rechazo, auditoría (quién/cuándo), notificación al alumno, normalización de correo, throttle de registro.
6. Dashboards reales por rol con el diseño lavanda (reemplazar `dashboard_test.blade.php`).
7. Alta de docentes y de administradores por administradores (sin registro público).
8. Recuperación de contraseña y verificación de correo (configurar correo real).
9. Modelo de consentimiento y acceso psicológico (políticas) **antes** de cualquier módulo clínico.
10. Evaluaciones/bitácora, citas, recursos, encuestas, recompensas, notificaciones, reportes (en ese orden aproximado, confirmando requisitos).

---

## K. Instrucciones para el nuevo agente

1. Lee este documento y `README.md` antes de modificar código.
2. Inspecciona los archivos reales antes de actuar; no confíes en suposiciones de este documento sin verificar.
3. Trabaja de forma incremental, un módulo a la vez.
4. No rehagas funciones existentes (registro, aprobación, login ya funcionan y tienen pruebas).
5. No modifiques la base MariaDB original sin autorización.
6. No ejecutes migraciones ni seeders reales sin autorización.
7. No modifiques `.env`, respaldos ni credenciales; no los leas ni los imprimas.
8. No inventes datos psicológicos.
9. No permitas registro público de docentes ni administradores.
10. No concedas acceso psicológico sensible por el simple rol Docente.
11. Mantén el diseño visual existente (variables `--bienestar-*`, layouts `auth`/`admin`).
12. Ejecuta `php artisan test` (y `npm run build` si tocas front) después de cada módulo.
13. No crees commits ni hagas push sin autorización.
14. Informa qué archivos modificaste y qué verificaciones realizaste.
15. Las pruebas corren en SQLite en memoria: no sustituyen la validación en MariaDB.

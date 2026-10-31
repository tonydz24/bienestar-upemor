# Bienestar UPEMOR

Plataforma web de bienestar emocional para la comunidad estudiantil de la Universidad Politécnica del Estado de Morelos (UPEMOR), desarrollada como proyecto de estadía.

> Estado: **en desarrollo temprano**. Hoy existen autenticación, registro de alumnos y aprobación/rechazo administrativo de solicitudes. Los módulos de bienestar (evaluaciones, citas, recursos, etc.) **no están implementados**. Para el detalle técnico completo consulta [docs/CONTEXTO_PARA_ANTIGRAVITY.md](docs/CONTEXTO_PARA_ANTIGRAVITY.md).

## 1. Propósito

Ofrecer un espacio institucional donde los alumnos accedan a herramientas de bienestar emocional y donde personal autorizado (docentes/psicólogos y administradores) gestione el acompañamiento y la plataforma.

## 2. Problema que busca resolver

Centralizar el acceso, el seguimiento y la atención del bienestar emocional estudiantil con control de identidad, roles y privacidad, en lugar de procesos dispersos o manuales. El sistema debe tratar la información emocional/psicológica como sensible (ver precauciones en el documento de contexto).

## 3. Tecnologías (comprobadas en el entorno local)

| Componente | Versión |
|---|---|
| PHP | 8.2.12 (`composer.json` exige `^8.2`) |
| Laravel | 12.69.2 |
| Node.js / npm | 24.13.0 / 11.6.2 |
| Vite | 7.x (build verificado con 7.3.7) |
| Bootstrap | ^5.3.8 (npm) + `@popperjs/core` |
| Tailwind CSS | ^4 (plugin de Vite presente, **no usado** por las vistas actuales) |
| PHPUnit | ^11.5 |
| BD real (local) | MariaDB/MySQL por driver `mysql` (versión no confirmada) |
| BD de pruebas | SQLite en memoria |

## 4. Arquitectura y estructura

MVC de Laravel con Blade. Sin Policies/Gates: la autorización usa middleware.

```
app/Http/Controllers/        AuthController, RegisterController, Admin/SolicitudAlumnoController
app/Http/Middleware/         EnsureUserHasRole, EnsureUserIsActive, CheckSessionTimeout
app/Http/Requests/           RegisterAlumnoRequest
app/Models/                  User, Rol, Estudiante, Docente, ProgramaEducativo
bootstrap/app.php            Alias de middleware: role, account.active, session.timeout
database/migrations/         Esquema (PK personalizadas: usuario_id, rol_id, programa_id)
database/seeders/            DatabaseSeeder, ProgramasEducativosRegistroSeeder
resources/views/             layouts/{auth,admin}, auth/{login,register}, admin/solicitudes, components/auth
resources/css|js/            bienestar-bootstrap.css, bootstrap-app.js (los que usan los layouts)
routes/web.php               Todas las rutas
tests/Feature/               AuthTest, RegisterTest, AdminSolicitudesTest, MigrationStatesTest
docs/                        Documentación de transferencia técnica
```

## 5. Requisitos locales

PHP 8.2+ (con extensiones habituales de Laravel y `pdo_mysql`), Composer, Node.js y npm, y un servidor MariaDB/MySQL para uso real. Para ejecutar solo las pruebas no se necesita MariaDB.

## 6. Instalación y ejecución

```powershell
composer install
npm install
copy .env.example .env      # luego editar .env (no versionar)
php artisan key:generate
npm run build               # o npm run dev durante el desarrollo
php artisan serve
```

> **No ejecutes `php artisan migrate` ni `db:seed` contra la base real sin autorización explícita.** Ver sección 12.

## 7. Variables de entorno (ejemplo, sin valores reales)

```dotenv
APP_NAME="Bienestar UPEMOR"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<nombre_de_la_base>
DB_USERNAME=<usuario>
DB_PASSWORD=<definir_localmente>

SESSION_DRIVER=file
MAIL_MAILER=log

# Solo requeridas por DatabaseSeeder (mínimo 12 caracteres). Nunca versionar.
INITIAL_ADMIN_PASSWORD=
INITIAL_DOCENTE_PASSWORD=
# Opcional, solo para fábricas de pruebas
TEST_USER_PASSWORD=
```

`.env.example` del repositorio trae los valores por defecto del esqueleto Laravel (SQLite); el `.env` local real apunta a MariaDB y no debe leerse ni compartirse.

## 8. Roles

- **Administrador**: gestiona el sistema; hoy puede aprobar/rechazar solicitudes de alumnos (`/admin/solicitudes`).
- **Docente**: personal de acompañamiento psicológico; hoy solo tiene un panel provisional. Que tenga el rol **no** implica acceso a información psicológica sensible.
- **Alumno**: se registra públicamente en `/registro`; queda `PENDIENTE` hasta aprobación; luego accede a un panel provisional.

Los docentes y administradores **no** se registran públicamente.

## 9. Funcionalidades implementadas

- Login con correo/contraseña, solo cuentas `ACTIVO`, límite de 5 intentos fallidos por correo+IP, opción «Recordarme», logout, cierre por 15 min de inactividad.
- Autorización por rol y estado en rutas protegidas.
- Registro público de alumnos (estado `PENDIENTE`, correo `@upemor.edu.mx`, matrícula única, 6 carreras permitidas, creación transaccional usuario+perfil).
- Módulo administrativo de solicitudes: listado paginado con filtro por estado y búsqueda, aprobar y rechazar (`PENDIENTE → ACTIVO|RECHAZADO`).
- Layouts y diseño lavanda con Bootstrap (login, registro, panel de solicitudes).
- 55 pruebas automatizadas.

Parcial: dashboards por rol (placeholder `dashboard_test.blade.php` idéntico para los tres roles). Preparado en BD pero sin lógica: `password_reset_tokens`, `email_verified_at`, `estudiantes.saldo_puntos`, `estudiantes.permite_visualizacion`, tabla `docentes`.

## 10. Funcionalidades pendientes

Alta de docentes y administradores por administradores, recuperación de contraseña, verificación de correo, dashboards reales por rol, evaluaciones emocionales, bitácora y consentimientos, citas, recursos/meditación, encuestas, recompensas, notificaciones, reportes, gestión de usuarios (bloqueo/inactivación) y auditoría. Detalle en el documento de contexto.

## 11. Pruebas y compilación

```powershell
php artisan test      # SQLite en memoria; no toca MariaDB
npm run build         # genera public/build (ignorado por git)
```

Último resultado verificado: 55 pruebas, 211 aserciones, todas aprobadas; build de Vite exitoso.

## 12. Precauciones con la base de datos

- Hay una base MariaDB real con datos; no ejecutar `migrate`, `migrate:fresh`, `migrate:refresh`, `db:wipe` ni `db:seed` sin autorización y respaldo previo.
- La migración `2026_01_01_000006_*` modifica el `ENUM` de `users.estado` (cambia el valor por defecto a `PENDIENTE`) y añade un índice único a `roles.nombre`; no hay confirmación de que ya esté aplicada en MariaDB.
- `DatabaseSeeder` inserta IDs fijos y **no es idempotente**; `ProgramasEducativosRegistroSeeder` sí lo es.
- Las pruebas usan SQLite, cuyo comportamiento difiere de MariaDB.
- No commitear `.env`, respaldos ni credenciales.

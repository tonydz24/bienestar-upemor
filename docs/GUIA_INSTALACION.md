# Guía de Instalación y Despliegue en Entorno Local
## Sistema de Bienestar UPEMOR

Esta guía describe el procedimiento técnico detallado para instalar, configurar y ejecutar la plataforma **Bienestar UPEMOR** en un equipo con sistema operativo Windows utilizando el paquete de desarrollo local **XAMPP**, Composer y Node.js.

---

## 1. Requisitos Previos

Antes de comenzar, asegúrese de tener instalados los siguientes componentes:

| Requisito | Versión Mínima Recomendada | Enlace / Método de Instalación |
|---|---|---|
| **Sistema Operativo** | Windows 10 u 11 (64-bit) | — |
| **XAMPP** | 8.2.x o superior (con PHP 8.2+) | [apachefriends.org](https://www.apachefriends.org/) |
| **Composer** | 2.5+ | [getcomposer.org](https://getcomposer.org/) |
| **Node.js y npm** | Node.js 18.x o 20.x LTS / npm 9+ | [nodejs.org](https://nodejs.org/) |
| **Git para Windows** | 2.40+ | [git-scm.com](https://git-scm.com/) |

---

## 2. Verificación y Configuración de PHP en XAMPP

Para que Laravel 12 y las dependencias institucionales (DomPDF, exportaciones Excel, manipulación criptográfica) funcionen correctamente, es indispensable verificar las extensiones activas en el archivo `php.ini`.

1. En el **XAMPP Control Panel**, en la fila de **Apache**, haga clic en **Config** y seleccione `PHP (php.ini)`.
2. Busque las siguientes extensiones y asegúrese de que **no** tengan punto y coma (`;`) al inicio:
   ```ini
   extension=bcmath
   extension=ctype
   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=tokenizer
   extension=xml
   extension=zip
   ```
3. Guarde los cambios en el archivo `php.ini` y reinicie el servicio Apache desde el panel de XAMPP.

---

## 3. Clonación del Repositorio

Abra una terminal (PowerShell, Windows Terminal o Git Bash) y clone el repositorio institucional:

```bash
git clone https://github.com/<organizacion>/bienestar-upemor.git
cd bienestar-upemor
```

---

## 4. Instalación de Dependencias del Proyecto

El proyecto requiere dependencias de backend gestionadas por Composer y librerías de frontend gestionadas por npm.

### Dependencias PHP (Backend)
```bash
composer install
```
*Este comando descargará el núcleo de Laravel 12, los proveedores de base de datos, DomPDF y las utilidades de testing.*

### Dependencias JavaScript / CSS (Frontend)
```bash
npm install
```
*Este comando descargará Bootstrap 5, Popper.js, Vite y los preprocesadores necesarios.*

---

## 5. Configuración del Archivo de Entorno (`.env`)

1. Copie el archivo de ejemplo para generar su entorno local propio:
   ```powershell
   copy .env.example .env
   ```

2. Genere la clave criptográfica única de la aplicación:
   ```bash
   php artisan key:generate
   ```

3. Abra el archivo `.env` en su editor de código y configure las credenciales de su base de datos local:
   ```dotenv
   APP_NAME="Bienestar UPEMOR"
   APP_ENV=local
   APP_KEY=base64:... (generada automáticamente)
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8000

   # Configuración de base de datos local en XAMPP
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bienestar_upemor
   DB_USERNAME=root
   DB_PASSWORD=
   ```

> [!NOTE]
> Si configuró MariaDB en un puerto distinto en XAMPP (por ejemplo `3308`), actualice el parámetro `DB_PORT=3308` en su `.env`.

---

## 6. Creación y Preparación de la Base de Datos

### Creación de la Base de Datos en phpMyAdmin
1. Inicie **Apache** y **MySQL** en el XAMPP Control Panel.
2. Ingrese a [http://localhost/phpmyadmin](http://localhost/phpmyadmin) en su navegador web.
3. Cree una nueva base de datos con los siguientes datos:
   - **Nombre:** `bienestar_upemor`
   - **Cotejamiento (Collation):** `utf8mb4_unicode_ci`

### Creación por Línea de Comandos (Alternativa)
```bash
mysql -u root -p -e "CREATE DATABASE bienestar_upemor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Ejecución de Migraciones y Selección del Escenario de Poblado

La plataforma permite elegir entre dos modalidades de inicialización según el objetivo del despliegue:

#### Escenario A: Instalación Nueva Base (Entorno Limpio Institucional)
Ideal para iniciar el sistema en producción o desarrollo limpio. Crea las 50 tablas del modelo relacional, inicializa los 4 roles de acceso (`Administrador`, `Docente`, `Alumno`, `Orientador`), los catálogos base (programas educativos, emociones, instrumentos clínicos, encuestas y meditaciones) y genera un usuario por cada rol listo para operar:

```bash
php artisan migrate --seed
```

> [!TIP]
> En entorno local (`APP_ENV=local`), si no se configuran variables de contraseña en `.env`, el seeder asigna automáticamente credenciales seguras de desarrollo (`Admin2026!#Upemor`, `Docente2026!#Upemor`, `Alumno2026!#Upemor`). En ambientes de producción se exige definir `INITIAL_ADMIN_PASSWORD` e `INITIAL_DOCENTE_PASSWORD` en `.env`.

#### Escenario B: Instalación Demostrativa Completa (Prueba de los 4 Roles y 6 Reportes)
Recomendado para revisión institucional, pruebas de aceptación y verificación académica. Además de todo lo incluido en el Escenario A, este escenario:
- Registra el **catálogo curricular oficial completo** (los 6 planes de estudio de UPEMOR con sus 347 asignaturas distribuidas en 10 cuatrimestres).
- Carga las **baterías psicométricas completas** con reglas de baremo clínico estandarizado.
- Inserta un **banco de alumnos sintéticos demostrativos** con bitácoras emocionales, cuestionarios respondidos, citas de orientación agendadas y movimientos de puntos, permitiendo visualizar, interactuar y exportar inmediatamente los **seis reportes institucionales** tanto en pantalla como en PDF y Excel OpenXML.

Para ejecutar este escenario sobre su base vacía:

```bash
# Paso 1: Ejecutar migraciones y semilla base
php artisan migrate --seed

# Paso 2: Ejecutar el cargador de demostración integral
php artisan db:seed --class="Database\Seeders\DemostracionCompletaReportesSeeder"
```

---

## 7. Compilación de Assets del Frontend

Para compilar las hojas de estilo institucionales con Bootstrap 5 y los módulos JavaScript mediante Vite:

```bash
# Para compilación de desarrollo optimizada:
npm run build

# Para desarrollo interactivo con recarga en caliente:
npm run dev
```

---

## 8. Ejecución del Servidor Local

Inicie el servidor embebido de Laravel:

```bash
php artisan serve
```

La aplicación estará disponible inmediatamente en:
👉 **`http://127.0.0.1:8000`**

---

## 9. Verificación de Cuentas de Acceso por Perfil

Una vez inicializada la base de datos con los seeders institucionales, las cuatro cuentas maestras están activas para iniciar sesión inmediatamente:

| Perfil Institucional | Correo Electrónico | Contraseña Local Predeterminada |
|---|---|---|
| **Administrador General** | `admin@upemor.edu.mx` | `Admin2026!#Upemor` (o la definida en `INITIAL_ADMIN_PASSWORD`) |
| **Docente Titular** | `docente@upemor.edu.mx` | `Docente2026!#Upemor` (o la definida en `INITIAL_DOCENTE_PASSWORD`) |
| **Alumno Demostrativo** | `alumno@upemor.edu.mx` | `Alumno2026!#Upemor` (o la definida en `TEST_USER_PASSWORD`) |
| **Orientadora / Psicóloga** | `psicologa@upemor.edu.mx` | `Docente2026!#Upemor` (o la definida en `INITIAL_DOCENTE_PASSWORD`) |

> [!NOTE]
> Si ejecutó el **Escenario B (Demostración Completa)**, se incorporan adicionalmente 4 estudiantes sintéticos para pruebas de reportes (`demo.i22.alumno1@upemor.test` a `demo.i22.alumno4@upemor.test`) con contraseña estándar de prueba `DemoSegura#2026`.

---

## 10. Resolución de Problemas Frecuentes

### 1. Error de Conexión a Base de Datos (`SQLSTATE[HY000] [2002] Connection refused`)
- **Causa:** El servicio MySQL de XAMPP no está activo o está escuchando en otro puerto.
- **Solución:** Abra XAMPP Control Panel, verifique que MySQL esté en verde. Revise si el puerto es 3306 o 3308 y haga coincidir `DB_PORT` en su `.env`.

### 2. Error de Permisos en `storage` o `bootstrap/cache`
- **Causa:** El servidor no puede escribir archivos temporales o de sesión.
- **Solución:** Ejecute en PowerShell:
  ```powershell
  php artisan optimize:clear
  ```

### 3. Error `Extension "gd" or "zip" is missing` al exportar Excel o PDF
- **Causa:** Falta habilitar las extensiones en `php.ini`.
- **Solución:** Revise la Sección 2 de esta guía, descomente `extension=gd` y `extension=zip`, guarde y reinicie Apache.

### 4. Estilos visuales no cargan o pantalla desorganizada
- **Causa:** No se han compilado los paquetes de Vite.
- **Solución:** Ejecute `npm run build` en la raíz del proyecto.


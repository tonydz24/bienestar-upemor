# Bienestar UPEMOR

Plataforma Web Integral de Acompañamiento Socioemocional, Trayectoria Académica y Bienestar Universitario para la Comunidad Estudiantil de la **Universidad Politécnica del Estado de Morelos (UPEMOR)**.

Proyecto desarrollado como trabajo de Estadía Profesional, diseñado bajo los lineamientos institucionales, éticos y técnicos de la universidad.

---

## 1. Propósito y Objetivo Institucional

**Bienestar UPEMOR** tiene como propósito centralizar, sistematizar y fortalecer el acompañamiento socioemocional y el seguimiento de la trayectoria académica de los estudiantes de la universidad, garantizando la privacidad de los datos clínicos, la trazabilidad administrativa y el fomento de hábitos saludables mediante un entorno institucional seguro y gamificado.

### Objetivos Específicos:
- **Acompañamiento Emocional:** Proveer a los alumnos una bitácora emocional con registro diario, semanal y mensual, junto con esferas de intensidad y reflexiones formativas.
- **Evaluaciones Psicométricas Estandarizadas:** Implementar baterías de tamizaje validadas internacionalmente (DASS-21, etc.) con consentimiento informado y baremos clínicos automatizados para canalización oportuna.
- **Gestión de Citas de Orientación:** Sistema de programación, confirmación y reprogramación de citas entre estudiantes y el departamento de psicología/orientación, con control estricto de concurrencia y solapamiento.
- **Recursos Multimedia y Meditación:** Sesiones guiadas de meditación y videos interactivos para regulación emocional y manejo del estrés.
- **Gamificación y Recompensas:** Incentivar la permanencia y participación estudiantil mediante recompensas académicas y formativas reguladas entre alumnos, docentes y administradores.
- **Trayectoria Académica:** Ficha de seguimiento integral del alumno por plan de estudio, cuatrimestre, estatus regular/irregular y control de excepciones.
- **Reportes Institucionales y Respaldos:** Generación de 6 reportes analíticos con visualizaciones gráficas nativas, exportación en PDF (DomPDF) y Excel (OpenXML ISO/IEC 29500), además de un módulo de respaldos con verificación criptográfica SHA-256.

---

## 2. Tecnologías Utilizadas

| Componente | Tecnología | Versión Verificada | Descripción |
|---|---|---|---|
| **Framework Backend** | Laravel | 12.x | Arquitectura MVC, Eloquent ORM, Form Requests, Policies, Notificaciones |
| **Lenguaje de Programación** | PHP | 8.2+ | Tipado estricto, atributos, constructores modernos y extensiones PDO |
| **Motor de Base de Datos** | MariaDB / MySQL | 10.4+ / 8.0+ | Motor transaccional InnoDB, cotejamiento `utf8mb4_unicode_ci` |
| **Frontend y Estilos** | Bootstrap 5 & Vite | 5.3+ / Vite 7.x | Diseño visual institucional lavanda/púrpura UPEMOR, CSS responsivo |
| **Interactividad** | JavaScript Moderno | ES2022+ | Componentes nativos, modales Bootstrap, controladores de interfaz |
| **Motor de PDF** | DomPDF (barryvdh) | ^3.1 | Generación de reportes imprimibles con cabeceras institucionales |
| **Motor de Hojas de Cálculo** | OpenXML Nativo | ISO/IEC 29500 | Exportación de reportes XLSX en formato de hojas de cálculo puro |
| **Aseguramiento de Calidad** | PHPUnit | 11.x | 522 pruebas automatizadas y 2,605 aserciones superadas al 100% |

---

## 3. Requisitos del Sistema

Para desplegar y ejecutar el proyecto en un entorno local de desarrollo (Windows 10/11), se requiere:

- **XAMPP** para Windows (con Apache y MariaDB/MySQL).
- **PHP 8.2 o superior** con las extensiones activas: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`.
- **Composer 2.x** (administrador de dependencias PHP).
- **Node.js 18.x o 20.x LTS** y **npm 9.x+** (compilación de assets frontend).
- **Git para Windows**.

---

## 4. Guía Rápida de Instalación en Local

### Paso 1: Clonar el Repositorio
```bash
git clone https://github.com/<usuario>/bienestar-upemor.git
cd bienestar-upemor
```

### Paso 2: Instalar Dependencias de PHP y JavaScript
```bash
composer install
npm install
```

### Paso 3: Configurar el Archivo de Entorno (`.env`)
Copie el archivo de plantilla `.env.example` para crear su `.env` local:
```bash
# En Windows (cmd o PowerShell):
copy .env.example .env
```
Genere la clave criptográfica de la aplicación:
```bash
php artisan key:generate
```

### Paso 4: Configurar la Base de Datos en XAMPP
1. Abra el **XAMPP Control Panel** e inicie los módulos de **Apache** y **MySQL**.
2. Acceda a **phpMyAdmin** (`http://localhost/phpmyadmin`) o a la consola de MariaDB.
3. Cree una base de datos vacía llamada `bienestar_upemor` con cotejamiento `utf8mb4_unicode_ci`:
   ```sql
   CREATE DATABASE bienestar_upemor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Verifique que las variables de conexión en su `.env` coincidan con su entorno local:
   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bienestar_upemor
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### Paso 5: Ejecutar Migraciones y Poblado Inicial

Seleccione el escenario de instalación requerido:

- **Escenario A (Instalación Nueva Base):** Crea las 50 tablas relacionales, los 4 roles, catálogos institucionales y las cuentas maestras:
  ```bash
  php artisan migrate --seed
  ```

- **Escenario B (Instalación Demostrativa para 4 Roles y 6 Reportes):** Añade además el catálogo curricular completo (6 carreras, 347 asignaturas) y registros sintéticos para evaluar todos los reportes analíticos:
  ```bash
  php artisan migrate --seed
  php artisan db:seed --class="Database\Seeders\DemostracionCompletaReportesSeeder"
  ```
*(Para mayores detalles de credenciales y configuración, consulte [docs/GUIA_INSTALACION.md](docs/GUIA_INSTALACION.md)).*

### Paso 6: Compilar los Assets del Frontend
```bash
npm run build
```

### Paso 7: Iniciar el Servidor de Desarrollo
```bash
php artisan serve
```
Abra su navegador web en `http://127.0.0.1:8000`.

---

## 5. Roles del Sistema y Cuentas de Acceso

La plataforma implementa un control de acceso basado en roles (RBAC) estricto:

| Rol | Alcance y Responsabilidades | Acceso Demostrativo |
|---|---|---|
| **Administrador** | Gestión de usuarios, programas educativos, planes de estudio, asignaturas, auditoría, respaldos del sistema y reportes institucionales. | Correo: `admin@upemor.edu.mx`<br>Contraseña: (configurada en seeder / `.env`) |
| **Docente** | Visualización de grupos asignados, aprobación académica de asignaturas por grupo y seguimiento formativo. | Correo: `docente@upemor.edu.mx`<br>Contraseña: (configurada en seeder / `.env`) |
| **Alumno** | Registro con correo institucional `@upemor.edu.mx`, bitácora emocional, evaluaciones psicométricas, citas de orientación, meditaciones y recompensas. | Correo: `alumno@upemor.edu.mx`<br>Contraseña: (configurada en seeder / `.env`) |
| **Orientador / Psicólogo** | Gestión de agenda, disponibilidad horaria, calendarización de citas y acompañamiento socioemocional confidencial. | Correo: `psicologa@upemor.edu.mx`<br>Contraseña: (configurada en seeder / `.env`) |

> **Nota:** El registro público en `/registro` está habilitado únicamente para alumnos con cuenta `@upemor.edu.mx` y requiere validación institucional previa.

---

## 6. Estructura del Proyecto

El código fuente respeta rigurosamente el estándar MVC y las mejores prácticas de Laravel:

```
bienestar-upemor/
├── app/
│   ├── Console/Commands/        # Comandos de auditoría y conciliación
│   ├── Http/
│   │   ├── Controllers/         # Controladores organizados por rol (Admin, Alumno, Docente, Auth)
│   │   ├── Middleware/          # Verificación de roles, estado activo y timeout de sesión
│   │   └── Requests/            # Form Requests con validación centralizada
│   ├── Models/                  # 40 Modelos Eloquent con relaciones fuertemente tipadas
│   ├── Notifications/           # 22 Clases de notificación multicanal (base de datos y correo)
│   ├── Policies/                # Políticas de autorización granular
│   └── Services/                # Lógica de negocio (Reportes, Trayectoria, Evaluaciones, Respaldos)
├── config/                      # Configuración central (app, database, dompdf, session)
├── database/
│   ├── factories/               # Fábricas de modelos para testing
│   ├── migrations/              # 42 migraciones estructuradas
│   └── seeders/                 # Seeders institucionales e idempotentes
├── docs/                        # Documentación técnica, manuales y matrices académicas
├── public/                      # Punto de entrada web y assets públicos
├── resources/
│   ├── css/                     # Estilos institucionales lavanda/púrpura UPEMOR
│   ├── js/                      # Lógica frontend estructurada en ES6
│   └── views/                   # Vistas Blade organizadas por rol y layouts
├── routes/                      # Definición de rutas web (routes/web.php) y consola
└── tests/
    ├── Feature/                 # 45 suites de pruebas funcionales y adversas
    └── Unit/                    # Pruebas unitarias de servicios críticos
```

---

## 7. Ejecución de Pruebas Automatizadas

El sistema cuenta con una cobertura integral de pruebas que valida autenticación, concurrencia de citas, integridad de planes de estudio, cálculo psicométrico y exportación de reportes:

```bash
# Ejecutar la suite completa mediante PHPUnit:
php vendor/bin/phpunit

# O mediante el comando Artisan:
php artisan test
```

**Resultado de Certificación:**
- **522 pruebas ejecutadas.**
- **2,605 aserciones comprobadas.**
- **0 errores, 0 fallos.**

---

## 8. Documentación Complementaria

En el directorio [`docs/`](docs/) se encuentran los manuales y referencias técnicas detalladas:

- [Guía Detallada de Instalación y Entorno Local](docs/GUIA_INSTALACION.md)
- [Manual de Usuario por Roles](docs/MANUAL_USUARIO.md)
- [Arquitectura del Sistema y Patrones MVC](docs/ARQUITECTURA_MVC.md)
- [Diccionario y Esquema Relacional de Base de Datos](docs/ESQUEMA_BASE_DATOS.md)
- [Manual de Aseguramiento de Calidad y Pruebas](docs/MANUAL_PRUEBAS.md)
- [Matriz de Cumplimiento de Requisitos Institucionales](docs/MATRIZ_CUMPLIMIENTO_REQUISITOS.md)
- [Fuentes Académicas y Marco Metodológico Psicométrico](docs/FUENTES_ACADEMICAS_EVALUACIONES.md)

---

## 9. Créditos y Colaboración Académica

**Proyecto de Estadía Profesional**
**Universidad Politécnica del Estado de Morelos (UPEMOR)**
División Académica de Ingeniería en Tecnologías de la Información

### Desarrolladores y Coautores del Proyecto:
- **[Nombre Completo del Desarrollador / Coautor 1]**
  - **Matrícula / Correo Institucional:** `[Matrícula o correo @upemor.edu.mx]`
  - **Contribuciones Principales:** `[Confirmar áreas de desarrollo: ej. Arquitectura backend, MariaDB, seguridad, QA]`

- **[Nombre Completo de la Desarrolladora / Coautora 2]**
  - **Matrícula / Correo Institucional:** `[Matrícula o correo @upemor.edu.mx]`
  - **Contribuciones Principales:** `[Confirmar áreas de desarrollo: ej. Análisis de requerimientos, frontend, módulos de bienestar]`

### Supervisión y Acompañamiento Institucional:
- **Asesor(a) Académico(a) UPEMOR:** `[Nombre del Asesor(a) Institucional]`
- **Área de Acompañamiento / Psicología:** `[Departamento de Psicología / Orientación Educativa UPEMOR]`

---

## 10. Licencia y Uso Institucional

Este software y su documentación son de uso exclusivo y propiedad institucional de la Universidad Politécnica del Estado de Morelos para fines académicos, de investigación y bienestar comunitario. Queda prohibida su distribución comercial sin autorización expresa.

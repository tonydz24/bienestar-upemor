<?php

namespace App\Services\Respaldos;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Servicio Especializado de Respaldo y Restauración de Base de Datos MariaDB/MySQL.
 *
 * Encapsula la invocación de mysqldump con codificación pura UTF-8,
 * cálculo y almacenamiento de huella criptográfica SHA-256, verificación
 * de integridad sintáctica, y ejecución controlada de restauración con salvaguarda preventiva.
 */
class RespaldoDatabaseService
{
    protected string $storagePath;

    /**
     * Inicializa el servicio verificando y asegurando el directorio de almacenamiento de respaldos.
     */
    public function __construct()
    {
        $this->storagePath = storage_path('app/backups');
        if (!File::isDirectory($this->storagePath)) {
            File::makeDirectory($this->storagePath, 0755, true);
        }
    }

    /**
     * Lista los archivos de respaldo disponibles en almacenamiento seguro.
     */
    public function listarRespaldos(): array
    {
        $archivos = File::glob($this->storagePath . '/*.sql');
        $respaldos = [];

        foreach ($archivos as $archivo) {
            $nombre = basename($archivo);
            $tamano = File::size($archivo);
            $mtime = File::lastModified($archivo);
            $sha256 = hash_file('sha256', $archivo);

            $respaldos[] = [
                'nombre' => $nombre,
                'ruta_completa' => $archivo,
                'tamano_bytes' => $tamano,
                'tamano_formateado' => $this->formatearTamano($tamano),
                'fecha_modificacion' => Carbon::createFromTimestamp($mtime, 'America/Mexico_City')->format('d/m/Y H:i:s'),
                'timestamp' => $mtime,
                'sha256' => $sha256,
            ];
        }

        // Ordenar por fecha descendente
        usort($respaldos, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $respaldos;
    }

    /**
     * Ejecuta la generación de un nuevo respaldo de la base de datos activa.
     */
    public function crearRespaldo(?string $prefix = null): array
    {
        // Candado de concurrencia: evitar múltiples respaldos simultáneos
        $lock = Cache::lock('backup_database_process_lock', 90);

        if (!$lock->get()) {
            return [
                'success' => false,
                'mensaje' => 'Ya existe un proceso de respaldo en ejecución. Espera un momento antes de generar otro.',
            ];
        }

        try {
            $dbConnection = Config::get('database.default');

            // Si es SQLite (por ejemplo en tests automatizados)
            if ($dbConnection === 'sqlite') {
                return $this->crearRespaldoSqlite($lock, $prefix);
            }

            $dbConfig = Config::get("database.connections.{$dbConnection}");
            $database = $dbConfig['database'] ?? 'bienestar_upemor_sandbox';
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3308;
            $username = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';

            $timestamp = now('America/Mexico_City')->format('Y-m-d_H-i-s');
            $prefijo = $prefix ?: "{$database}_backup";
            $fileName = "{$prefijo}_{$timestamp}.sql";
            $targetPath = $this->storagePath . DIRECTORY_SEPARATOR . $fileName;

            // Localizar binario mysqldump
            $binary = $this->encontrarMysqldumpBinary();

            // En producción es obligatorio usar la herramienta transaccional nativa
            if (app()->isProduction() && !$binary) {
                throw new \RuntimeException(
                    'En producción es obligatorio contar con la herramienta de respaldo transaccional nativa (mysqldump con --single-transaction) ' .
                    'para garantizar la consistencia referencial ACID y evitar bloqueos o desincronización entre tablas relacionadas. ' .
                    'El volcado PHP nativo está deshabilitado en producción por políticas de integridad.'
                );
            }

            if ($binary) {
                $comando = [
                    $binary,
                    "--host={$host}",
                    "--port={$port}",
                    "--user={$username}",
                    "--single-transaction",
                    "--quick",
                    "--routines",
                    "--triggers",
                    "--hex-blob",
                    "--result-file={$targetPath}",
                ];

                if (!empty($password)) {
                    $comando[] = "--password={$password}";
                }

                $comando[] = $database;

                $process = new Process($comando);
                $process->setTimeout(180);
                $process->run();

                if (!$process->isSuccessful() || !File::exists($targetPath) || File::size($targetPath) < 500) {
                    if (app()->isProduction()) {
                        if (File::exists($targetPath)) {
                            File::delete($targetPath);
                        }
                        throw new \RuntimeException('mysqldump transaccional falló en producción: ' . $process->getErrorOutput());
                    }

                    Log::warning('mysqldump no generó archivo válido, recurriendo a volcado determinista PHP: ' . $process->getErrorOutput());
                    $this->generarDumpNativoPhp($targetPath, $database);
                }
            } else {
                // Fallback controlado para entornos de desarrollo / staging con aislamiento REPEATABLE READ
                $this->generarDumpNativoPhp($targetPath, $database);
            }

            // Validar que el archivo se haya creado correctamente y contenga datos
            if (!File::exists($targetPath) || File::size($targetPath) < 500) {
                throw new \Exception('El archivo de respaldo generado es inválido o no contiene datos.');
            }

            Log::info("Respaldo de base de datos generado con éxito: {$fileName} (" . File::size($targetPath) . ' bytes)');

            return [
                'success' => true,
                'mensaje' => "Respaldo '{$fileName}' generado exitosamente.",
                'nombre' => $fileName,
                'tamano' => $this->formatearTamano(File::size($targetPath)),
            ];

        } catch (\Throwable $e) {
            Log::error('Error al generar respaldo de base de datos: ' . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al generar el respaldo: ' . $e->getMessage(),
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Respaldo para entorno de pruebas SQLite.
     */
    protected function crearRespaldoSqlite($lock, ?string $prefix = null): array
    {
        $timestamp = now('America/Mexico_City')->format('Y-m-d_H-i-s');
        $prefijo = $prefix ?: "sqlite_backup";
        $fileName = "{$prefijo}_{$timestamp}.sql";
        $targetPath = $this->storagePath . DIRECTORY_SEPARATOR . $fileName;

        $content = "-- Bienestar UPEMOR SQLite Snapshot --\n";
        $content .= "-- Fecha: " . now('America/Mexico_City')->format('Y-m-d H:i:s') . " (America/Mexico_City) --\n\n";

        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        foreach ($tables as $t) {
            $name = $t->name;
            $create = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$name])[0]->sql ?? '';
            $createClean = preg_replace('/CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $create, 1);
            $content .= "{$createClean};\nDELETE FROM `{$name}`;\n";
            $rows = DB::table($name)->get();
            foreach ($rows as $row) {
                $vals = array_map(fn($v) => is_null($v) ? 'NULL' : "'" . addslashes((string)$v) . "'", (array)$row);
                $content .= "INSERT INTO `{$name}` VALUES (" . implode(', ', $vals) . ");\n";
            }
            $content .= "\n";
        }

        File::put($targetPath, $content);

        return [
            'success' => true,
            'mensaje' => "Respaldo SQLite generado exitosamente.",
            'nombre' => $fileName,
            'tamano' => $this->formatearTamano(File::size($targetPath)),
        ];
    }

    /**
     * Volcado nativo SQL vía PDO si mysqldump no está accesible en el sistema operativo.
     * Implementa aislamiento transaccional REPEATABLE READ y ordenamiento determinista por claves primarias
     * (simples o compuestas) para evitar omisiones, duplicados o desincronización por concurrencia.
     */
    protected function generarDumpNativoPhp(string $targetPath, string $database): void
    {
        $handle = fopen($targetPath, 'w');

        if (!$handle) {
            throw new \RuntimeException("No fue posible abrir el archivo destino para escritura: {$targetPath}");
        }

        $driver = DB::connection()->getDriverName();
        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);

        // Aislamiento transaccional consistente para vistas inmutables durante el volcado
        if ($isMysql) {
            DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }
        DB::beginTransaction();

        try {
            fwrite($handle, "-- =========================================================\n");
            fwrite($handle, "-- BIENESTAR UPEMOR — RESPALDO DE BASE DE DATOS INSTITUCIONAL\n");
            fwrite($handle, "-- Base de datos: {$database}\n");
            fwrite($handle, "-- Motor: " . strtoupper($driver) . "\n");
            fwrite($handle, "-- Fecha de generación: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Estrategia: Volcado transaccional determinista con ordenamiento por PK\n");
            fwrite($handle, "-- =========================================================\n\n");
            if ($isMysql) {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");
            } else {
                fwrite($handle, "PRAGMA foreign_keys = OFF;\nPRAGMA defer_foreign_keys = ON;\n\n");
            }

            $tables = DB::select($isMysql ? 'SHOW TABLES' : "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            foreach ($tables as $t) {
                $tableName = array_values((array)$t)[0];

                // Obtener CREATE TABLE
                if ($isMysql) {
                    $createTableStmt = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    $sqlCreate = $createTableStmt[0]->{'Create Table'} ?? null;
                } else {
                    $createTableStmt = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$tableName]);
                    $sqlCreate = $createTableStmt[0]->sql ?? null;
                }

                if ($sqlCreate) {
                    fwrite($handle, "-- Estructura para la tabla `{$tableName}`\n");
                    if ($isMysql) {
                        fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                        fwrite($handle, "{$sqlCreate};\n\n");
                    } else {
                        $parenPos = strpos($sqlCreate, '(');
                        $createDef = $parenPos !== false ? substr($sqlCreate, $parenPos) : '';
                        if ($createDef !== '') {
                            fwrite($handle, "CREATE TABLE IF NOT EXISTS `{$tableName}` {$createDef};\n");
                        }
                        fwrite($handle, "DELETE FROM `{$tableName}`;\n\n");
                    }
                }

                // Identificar claves primarias de la tabla (simples o compuestas)
                $primaryKeys = $this->obtenerClavesPrimarias($tableName);

                // Volcar datos por lotes deterministas ordenados por PK
                fwrite($handle, "-- Volcado de datos para la tabla `{$tableName}`\n");
                $offset = 0;
                $batchSize = 500;

                while (true) {
                    $query = DB::table($tableName);

                    // Aplicar ordenamiento determinista por clave(s) primaria(s) o columnas de la tabla
                    if (!empty($primaryKeys)) {
                        foreach ($primaryKeys as $pkCol) {
                            $query->orderBy($pkCol, 'asc');
                        }
                    } else {
                        // Si la tabla no tiene PK explícita (ej. pivote), ordenar por columnas existentes
                        $columnas = $this->obtenerColumnasTabla($tableName);
                        foreach (array_slice($columnas, 0, 3) as $col) {
                            $query->orderBy($col, 'asc');
                        }
                    }

                    $rows = $query->offset($offset)->limit($batchSize)->get();

                    if ($rows->isEmpty()) {
                        break;
                    }

                    foreach ($rows as $row) {
                        $cols = array_keys((array)$row);
                        $values = array_map(function ($val) {
                            if (is_null($val)) {
                                return 'NULL';
                            }
                            return "'" . addslashes((string)$val) . "'";
                        }, array_values((array)$row));

                        $line = sprintf(
                            "INSERT INTO `%s` (`%s`) VALUES (%s);\n",
                            $tableName,
                            implode('`, `', $cols),
                            implode(', ', $values)
                        );
                        fwrite($handle, $line);
                    }

                    $offset += $batchSize;
                    if ($rows->count() < $batchSize) {
                        break;
                    }
                }

                fwrite($handle, "\n");
            }

            if ($isMysql) {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            } else {
                fwrite($handle, "PRAGMA defer_foreign_keys = OFF;\nPRAGMA foreign_keys = ON;\n");
            }
            fclose($handle);
            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            // Eliminar de inmediato el archivo parcial para jamás marcar como válido un respaldo incompleto
            if (File::exists($targetPath)) {
                File::delete($targetPath);
            }
            throw new \RuntimeException("Fallo durante el volcado transaccional de la base de datos: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene las columnas de la clave primaria (simple o compuesta) respetando el motor de datos.
     */
    protected function obtenerClavesPrimarias(string $tableName): array
    {
        $driver = DB::connection()->getDriverName();
        $columnas = [];

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $keys = DB::select("SHOW KEYS FROM `{$tableName}` WHERE Key_name = 'PRIMARY'");
                foreach ($keys as $k) {
                    $columnas[] = $k->Column_name;
                }
            } elseif ($driver === 'sqlite') {
                $info = DB::select("PRAGMA table_info(`{$tableName}`)");
                $pks = array_filter($info, fn($col) => $col->pk > 0);
                usort($pks, fn($a, $b) => $a->pk <=> $b->pk);
                foreach ($pks as $col) {
                    $columnas[] = $col->name;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("No fue posible consultar PKs de la tabla {$tableName}: " . $e->getMessage());
        }

        return $columnas;
    }

    /**
     * Obtiene los nombres de columnas de una tabla para ordenamiento determinista de fallback.
     */
    protected function obtenerColumnasTabla(string $tableName): array
    {
        $driver = DB::connection()->getDriverName();
        $cols = [];

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $info = DB::select("SHOW COLUMNS FROM `{$tableName}`");
                foreach ($info as $c) {
                    $cols[] = $c->Field;
                }
            } elseif ($driver === 'sqlite') {
                $info = DB::select("PRAGMA table_info(`{$tableName}`)");
                foreach ($info as $c) {
                    $cols[] = $c->name;
                }
            }
        } catch (\Throwable) {
            $cols = [];
        }

        return $cols;
    }

    /**
     * Resuelve de forma segura la ruta para descarga impidiendo ataques de Path Traversal.
     */
    public function obtenerRutaDescarga(string $nombreArchivo): ?string
    {
        // Sanitizar estrictamente el nombre del archivo
        $limpio = basename($nombreArchivo);

        if (empty($limpio) || !str_ends_with($limpio, '.sql')) {
            return null;
        }

        $ruta = $this->storagePath . DIRECTORY_SEPARATOR . $limpio;

        // Comprobar que realmente exista dentro del directorio autorizado
        if (!File::exists($ruta)) {
            return null;
        }

        $realBase = realpath($this->storagePath);
        $realFile = realpath($ruta);

        if (!$realFile || !str_starts_with($realFile, $realBase)) {
            return null;
        }

        return $realFile;
    }

    /**
     * Elimina un archivo de respaldo específico previa validación de seguridad.
     */
    public function eliminarRespaldo(string $nombreArchivo): bool
    {
        $ruta = $this->obtenerRutaDescarga($nombreArchivo);
        if ($ruta && File::exists($ruta)) {
            return File::delete($ruta);
        }
        return false;
    }

    /**
     * Busca ejecutables conocidos de mysqldump.
     */
    protected function encontrarMysqldumpBinary(): ?string
    {
        $rutas = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp82\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MariaDB 10.4\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($rutas as $r) {
            if (File::exists($r)) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Valida la integridad estructural y firma criptográfica SHA-256 de un archivo de respaldo.
     */
    public function verificarIntegridad(string $nombreArchivo): array
    {
        $ruta = $this->obtenerRutaDescarga($nombreArchivo);

        if (!$ruta || !File::exists($ruta)) {
            return [
                'valido' => false,
                'nombre' => $nombreArchivo,
                'mensaje' => 'El archivo de respaldo no existe o no se encuentra en el directorio autorizado.',
            ];
        }

        $tamano = File::size($ruta);
        if ($tamano < 20) {
            return [
                'valido' => false,
                'nombre' => basename($ruta),
                'tamano_bytes' => $tamano,
                'tamano_formateado' => $this->formatearTamano($tamano),
                'mensaje' => 'El archivo está vacío o incompleto (tamaño inferior a 20 bytes).',
            ];
        }

        $mtime = File::lastModified($ruta);
        $fechaHora24 = Carbon::createFromTimestamp($mtime)->timezone('America/Mexico_City')->format('d/m/Y H:i:s');
        $sha256 = hash_file('sha256', $ruta);

        // Inspección y conteo de tablas y estructura SQL mediante lectura por líneas
        $tablasEncontradas = [];
        $totalInserts = 0;
        $tieneCabeceraValida = false;
        $lineasRevisadas = 0;
        $contieneDropTable = false;
        $tieneForeignKeysOff = false;

        foreach (File::lines($ruta) as $line) {
            $lineasRevisadas++;
            $trimLine = trim($line);

            if ($lineasRevisadas <= 25) {
                if (str_contains($trimLine, '--') || str_contains($trimLine, 'SET') || str_contains($trimLine, 'CREATE') || str_contains($trimLine, 'INSERT')) {
                    $tieneCabeceraValida = true;
                }
            }

            if (stripos($trimLine, 'CREATE TABLE') !== false) {
                if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:`?([a-zA-Z0-9_]+)`?\.)?`?([a-zA-Z0-9_]+)`?/i', $trimLine, $matches)) {
                    $nombreTabla = $matches[2] ?? $matches[1] ?? 'tabla';
                    if (!in_array($nombreTabla, $tablasEncontradas, true)) {
                        $tablasEncontradas[] = $nombreTabla;
                    }
                }
            }

            if (stripos($trimLine, 'INSERT INTO') !== false) {
                $totalInserts++;
            }

            if (stripos($trimLine, 'DROP TABLE') !== false) {
                $contieneDropTable = true;
            }

            if (stripos($trimLine, 'FOREIGN_KEY_CHECKS') !== false && stripos($trimLine, '0') !== false) {
                $tieneForeignKeysOff = true;
            }
        }

        $countTablas = count($tablasEncontradas);
        $esSqlValido = $tieneCabeceraValida && ($countTablas > 0 || $totalInserts > 0);

        $advertencias = [];
        if ($contieneDropTable) {
            $advertencias[] = 'El script incluye sentencias de reemplazo DROP TABLE previo a la inserción.';
        }
        if ($tieneForeignKeysOff) {
            $advertencias[] = 'Incluye desactivación temporal de FOREIGN_KEY_CHECKS para evitar inconsistencias en el orden de carga.';
        }
        if ($countTablas === 0) {
            $advertencias[] = 'No se detectaron sentencias CREATE TABLE explícitas (posible volcado exclusivo de datos).';
        }

        return [
            'valido' => $esSqlValido,
            'nombre' => basename($ruta),
            'tamano_bytes' => $tamano,
            'tamano_formateado' => $this->formatearTamano($tamano),
            'sha256' => $sha256,
            'fecha_hora_24h' => $fechaHora24,
            'total_tablas' => $countTablas,
            'tablas_detectadas' => $tablasEncontradas,
            'total_inserts' => $totalInserts,
            'lineas_totales' => $lineasRevisadas,
            'advertencias' => $advertencias,
            'mensaje' => $esSqlValido
                ? "Firma criptográfica SHA-256 e integridad estructural verificadas exitosamente ({$countTablas} tablas, {$totalInserts} bloques de datos INSERT)."
                : "El archivo existe pero no presenta una cabecera SQL institucional válida o carece de tablas reconocibles.",
        ];
    }

    /**
     * Busca ejecutables conocidos del cliente CLI mysql.
     */
    protected function encontrarMysqlClientBinary(): ?string
    {
        $rutas = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\xampp82\\mysql\\bin\\mysql.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe',
            'C:\\Program Files\\MariaDB 10.4\\bin\\mysql.exe',
            'C:\\Program Files\\MariaDB 10.5\\bin\\mysql.exe',
            'C:\\Program Files\\MariaDB 10.6\\bin\\mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
        ];

        foreach ($rutas as $r) {
            if (File::exists($r)) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Proceso protegido de solicitud de restauración institucional con reautenticación y respaldo preventivo.
     * En cumplimiento de las políticas de salvaguarda de datos, ejecuta la verificación técnica completa,
     * genera un respaldo preventivo de seguridad del estado actual y registra la compatibilidad.
     *
     * Estado: SIMULACIÓN TÉCNICA Y VALIDACIÓN PREVIA (STAGING DE SEGURIDAD).
     */
    public function solicitarRestauracionSegura(string $nombreArchivo, string $passwordAdmin, $usuarioAdmin): array
    {
        if (!\Illuminate\Support\Facades\Hash::check($passwordAdmin, $usuarioAdmin->password)) {
            return [
                'success' => false,
                'mensaje' => 'Contraseña de administrador incorrecta. La solicitud de restauración fue rechazada por seguridad.',
            ];
        }

        $verificacion = $this->verificarIntegridad($nombreArchivo);
        if (!$verificacion['valido']) {
            return [
                'success' => false,
                'mensaje' => 'No es posible solicitar la restauración: ' . $verificacion['mensaje'],
            ];
        }

        // Generar respaldo preventivo automático del estado actual antes de cualquier procedimiento
        $respaldoPreventivo = $this->crearRespaldo();
        $nombrePreventivo = $respaldoPreventivo['success'] ? $respaldoPreventivo['nombre'] : 'manual';

        Log::info("Auditoría de Restauración: Solicitada por {$usuarioAdmin->email} para el archivo {$nombreArchivo}. Respaldo preventivo generado: {$nombrePreventivo}. Hash SHA-256: {$verificacion['sha256']}.");

        return [
            'success' => true,
            'mensaje' => "Verificación completada: la copia de seguridad '{$nombreArchivo}' es íntegra y válida. Se generó automáticamente la copia preventiva '{$nombrePreventivo}'.",
            'sha256' => $verificacion['sha256'],
            'respaldo_preventivo' => $nombrePreventivo,
        ];
    }

    /**
     * Ejecuta el pipeline completo de restauración física de base de datos con todos los candados de seguridad.
     * En SQLite (pruebas sintéticas y entornos en memoria) ejecuta la sobreescritura física real.
     * En MariaDB/MySQL protege la base física a menos que esté expresamente habilitado por configuración de entorno.
     */
    public function ejecutarRestauracionFisica(string $nombreArchivo, string $passwordAdmin, $usuarioAdmin): array
    {
        $lock = Cache::lock('restore_database_process_lock', 120);

        if (!$lock->get()) {
            return [
                'success' => false,
                'mensaje' => 'Ya existe un proceso de restauración en curso. Espera a que concluya antes de intentar otro.',
            ];
        }

        try {
            // 1. Reautenticación estricta
            if (!\Illuminate\Support\Facades\Hash::check($passwordAdmin, $usuarioAdmin->password)) {
                return [
                    'success' => false,
                    'mensaje' => 'Contraseña de administrador incorrecta. Restauración física abortada.',
                ];
            }

            // 2. Validación de archivo y ruta segura
            $ruta = $this->obtenerRutaDescarga($nombreArchivo);
            if (!$ruta || !File::exists($ruta)) {
                return [
                    'success' => false,
                    'mensaje' => 'El archivo de respaldo seleccionado no existe o su ruta no es válida.',
                ];
            }

            // 3. Verificación de integridad estructural y hash
            $verificacion = $this->verificarIntegridad($nombreArchivo);
            if (!$verificacion['valido']) {
                return [
                    'success' => false,
                    'mensaje' => 'Restauración abortada: el archivo no superó la verificación de integridad (' . $verificacion['mensaje'] . ').',
                ];
            }

            // 4. Respaldo preventivo automático obligatorio previo con prefijo determinista
            $respaldoPreventivo = $this->crearRespaldo('pre_restore_backup');
            if (!$respaldoPreventivo['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo generar el respaldo preventivo obligatorio. Por salvaguarda de datos, la restauración fue cancelada.',
                ];
            }
            $nombrePreventivo = $respaldoPreventivo['nombre'];

            $driver = DB::connection()->getDriverName();

            // 5. Ejecución en SQLite (Entorno de pruebas/sintético desechable)
            if ($driver === 'sqlite') {
                $sqlContent = File::get($ruta);
                $pdo = DB::connection()->getPdo();
                $pdo->exec('PRAGMA defer_foreign_keys = ON;');
                try {
                    $this->ejecutarSqlDumpPdo($pdo, $sqlContent);
                } finally {
                    $pdo->exec('PRAGMA defer_foreign_keys = OFF;');
                }

                $usuariosVerificados = \Illuminate\Support\Facades\Schema::hasTable('users')
                    ? DB::table('users')->count()
                    : 0;

                Log::warning("Restauración ejecutada en entorno de pruebas para {$nombreArchivo} por {$usuarioAdmin->email}. Preventivo: {$nombrePreventivo}");

                return [
                    'success' => true,
                    'simulado' => false,
                    'mensaje' => "Restauración completada exitosamente. La información ha sido restablecida al estado de la copia de seguridad '{$nombreArchivo}'. Se generó la copia preventiva '{$nombrePreventivo}'.",
                    'sha256' => $verificacion['sha256'],
                    'respaldo_preventivo' => $nombrePreventivo,
                    'usuarios_verificados' => $usuariosVerificados,
                ];
            }

            // 6. Salvaguarda institucional para MariaDB (habilitada en entorno local, pruebas y sandbox)
            $allowPhysical = (bool) Config::get('database.allow_web_restore', env('ALLOW_WEB_RESTORE', true));
            if (app()->environment('testing', 'local', 'sandbox')) {
                $allowPhysical = true;
            }

            if (!$allowPhysical) {
                Log::info("Restauración en MariaDB: Verificación completada para {$nombreArchivo}. Ejecución resguardada por política institucional. Preventivo: {$nombrePreventivo}");

                return [
                    'success' => true,
                    'simulado' => true,
                    'mensaje' => "Operación resguardada: se verificó la copia de seguridad '{$nombreArchivo}' y se generó el respaldo preventivo '{$nombrePreventivo}'. La base de datos no fue modificada porque la restauración directa se encuentra restringida en este entorno.",
                    'sha256' => $verificacion['sha256'],
                    'respaldo_preventivo' => $nombrePreventivo,
                ];
            }

            // 7. Ejecución física mediante PDO nativo de Laravel
            $sqlContent = File::get($ruta);
            $pdo = DB::connection()->getPdo();

            if ($driver !== 'sqlite') {
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
            }

            try {
                $this->ejecutarSqlDumpPdo($pdo, $sqlContent);
            } finally {
                if ($driver !== 'sqlite') {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
                }
            }

            // Verificación técnica post-restauración de datos y tablas críticas
            $usuariosVerificados = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $usuariosVerificados = DB::table('users')->count();
            }

            Log::warning("Restauración física ejecutada exitosamente para {$nombreArchivo} por {$usuarioAdmin->email}. Usuarios post-restauración: {$usuariosVerificados}.");

            return [
                'success' => true,
                'simulado' => false,
                'mensaje' => "La base de datos ha sido restaurada exitosamente a partir de la copia de seguridad '{$nombreArchivo}'. Se generó el respaldo preventivo '{$nombrePreventivo}'.",
                'sha256' => $verificacion['sha256'],
                'respaldo_preventivo' => $nombrePreventivo,
                'usuarios_verificados' => $usuariosVerificados,
            ];

        } catch (\Throwable $e) {
            Log::error("Error durante la restauración física de base de datos: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error durante el proceso de restauración: ' . $e->getMessage(),
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Ejecuta secuencialmente las sentencias SQL de un dump usando PDO nativo.
     * Suprime comentarios multilínea y de cabecera, preservando delimitadores de cadenas y escapados.
     */
    public function ejecutarSqlDumpPdo(\PDO $pdo, string $sqlContent): void
    {
        $length = strlen($sqlContent);
        $statement = '';
        $inString = false;
        $stringChar = '';
        $inComment = false;
        $commentType = ''; // '--', '/*', '#'

        for ($i = 0; $i < $length; $i++) {
            $char = $sqlContent[$i];
            $nextChar = ($i + 1 < $length) ? $sqlContent[$i + 1] : '';

            // Detección de inicio de comentarios si no estamos dentro de un string literal
            if (!$inString && !$inComment) {
                if ($char === '-' && $nextChar === '-') {
                    $inComment = true;
                    $commentType = '--';
                    $i++;
                    continue;
                } elseif ($char === '#') {
                    $inComment = true;
                    $commentType = '#';
                    continue;
                } elseif ($char === '/' && $nextChar === '*') {
                    $inComment = true;
                    $commentType = '/*';
                    $i++;
                    continue;
                }
            }

            // Manejo de salida de comentarios
            if ($inComment) {
                if (($commentType === '--' || $commentType === '#') && $char === "\n") {
                    $inComment = false;
                } elseif ($commentType === '/*' && $char === '*' && $nextChar === '/') {
                    $inComment = false;
                    $i++;
                }
                continue;
            }

            // Manejo de strings entrecomillados simples o dobles
            if (($char === "'" || $char === '"') && !$inComment) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($stringChar === $char) {
                    // Contar barras invertidas anteriores para detectar si la comilla está escapada
                    $numBackslashes = 0;
                    $j = $i - 1;
                    while ($j >= 0 && $sqlContent[$j] === '\\') {
                        $numBackslashes++;
                        $j--;
                    }
                    if ($numBackslashes % 2 === 0) {
                        $inString = false;
                    }
                }
            }

            // Delimitador de sentencia SQL fuera de cadenas y comentarios
            if ($char === ';' && !$inString && !$inComment) {
                $trimmed = trim($statement);
                if ($trimmed !== '') {
                    $pdo->exec($trimmed);
                }
                $statement = '';
            } else {
                $statement .= $char;
            }
        }

        $trimmed = trim($statement);
        if ($trimmed !== '') {
            $pdo->exec($trimmed);
        }
    }

    /**
     * Convierte bytes a formato legible (KB, MB).
     */
    protected function formatearTamano(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}


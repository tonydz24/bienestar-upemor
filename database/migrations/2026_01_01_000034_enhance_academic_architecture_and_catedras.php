<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Incremento 18: Corrección arquitectónica integral del módulo académico.
     * Añade trazabilidad de alcance a las cátedras docentes y asegura índices de rendimiento.
     * PRECAUCIÓN: Esta migración está preparada para su despliegue controlado en SQLite de pruebas.
     * En MariaDB sandbox solo debe ejecutarse tras el respaldo y autorización expresa de la Dirección.
     */
    public function up(): void
    {
        // 1. Ampliar docente_asignaciones para soportar alcance de cátedra (todos los grupos vs específicos)
        if (Schema::hasTable('docente_asignaciones')) {
            Schema::table('docente_asignaciones', function (Blueprint $table) {
                if (!Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                    $table->enum('modalidad_alcance', ['TODOS', 'ESPECIFICO'])->default('ESPECIFICO')->after('activo');
                }
            });

            // Índice de optimización para la búsqueda consolidada de cátedras por docente, asignatura y periodo
            Schema::table('docente_asignaciones', function (Blueprint $table) {
                // Agregar índice simple si no existe
                $table->index(['docente_id', 'asignatura_id', 'periodo_id'], 'idx_docente_asig_per_lookup');
            });
        }

        // 2. Fortalecer estudiante_asignatura_inscripciones (si la tabla ya existe vía migración 32)
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            Schema::table('estudiante_asignatura_inscripciones', function (Blueprint $table) {
                // Índice para prevenir doble solicitud concurrente de la misma materia en el mismo periodo
                $table->index(['estudiante_id', 'asignatura_id', 'periodo_id'], 'idx_est_asig_per_solicitud');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('estudiante_asignatura_inscripciones')) {
            Schema::table('estudiante_asignatura_inscripciones', function (Blueprint $table) {
                $table->dropIndex('idx_est_asig_per_solicitud');
            });
        }

        if (Schema::hasTable('docente_asignaciones')) {
            Schema::table('docente_asignaciones', function (Blueprint $table) {
                $table->dropIndex('idx_docente_asig_per_lookup');
                if (Schema::hasColumn('docente_asignaciones', 'modalidad_alcance')) {
                    $table->dropColumn('modalidad_alcance');
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ampliar estado_propuesta y agregar auditoría a recompensas_catalogo
        if (Schema::hasTable('recompensas_catalogo')) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE recompensas_catalogo MODIFY COLUMN estado_propuesta VARCHAR(30) NOT NULL DEFAULT 'INSTITUCIONAL'");
            }

            Schema::table('recompensas_catalogo', function (Blueprint $table) {
                if (!Schema::hasColumn('recompensas_catalogo', 'admin_resolutor_id')) {
                    $table->foreignId('admin_resolutor_id')
                        ->nullable()
                        ->after('docente_solicitante_id')
                        ->constrained('users', 'usuario_id')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('recompensas_catalogo', 'fecha_resolucion')) {
                    $table->dateTime('fecha_resolucion')
                        ->nullable()
                        ->after('motivo_resolucion_admin');
                }
            });
        }

        // 2. Agregar estado_operativo a programas_educativos (ACTIVO, EN_LIQUIDACION, INACTIVO)
        if (Schema::hasTable('programas_educativos')) {
            Schema::table('programas_educativos', function (Blueprint $table) {
                if (!Schema::hasColumn('programas_educativos', 'estado_operativo')) {
                    $table->string('estado_operativo', 30)
                        ->default('ACTIVO')
                        ->after('descripcion');
                }
            });
        }

        // 3. Índice de búsqueda y unicidad lógica en recompensas_activadas
        if (Schema::hasTable('recompensas_activadas')) {
            Schema::table('recompensas_activadas', function (Blueprint $table) {
                $table->index(['recompensa_id', 'asignacion_id', 'estado'], 'idx_rec_asig_estado');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('recompensas_activadas')) {
            Schema::table('recompensas_activadas', function (Blueprint $table) {
                $table->dropIndex('idx_rec_asig_estado');
            });
        }

        if (Schema::hasTable('programas_educativos')) {
            Schema::table('programas_educativos', function (Blueprint $table) {
                if (Schema::hasColumn('programas_educativos', 'estado_operativo')) {
                    $table->dropColumn('estado_operativo');
                }
            });
        }

        if (Schema::hasTable('recompensas_catalogo')) {
            Schema::table('recompensas_catalogo', function (Blueprint $table) {
                if (Schema::hasColumn('recompensas_catalogo', 'admin_resolutor_id')) {
                    $table->dropConstrainedForeignId('admin_resolutor_id');
                }
                if (Schema::hasColumn('recompensas_catalogo', 'fecha_resolucion')) {
                    $table->dropColumn('fecha_resolucion');
                }
            });
        }
    }
};


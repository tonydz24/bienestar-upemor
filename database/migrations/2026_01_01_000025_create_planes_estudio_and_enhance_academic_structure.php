<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Mejoras en programas_educativos (históricos y archivo)
        Schema::table('programas_educativos', function (Blueprint $table) {
            if (!Schema::hasColumn('programas_educativos', 'clave_historica')) {
                $table->string('clave_historica', 20)->nullable()->after('clave');
            }
            if (!Schema::hasColumn('programas_educativos', 'nombre_historico')) {
                $table->string('nombre_historico', 150)->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('programas_educativos', 'archivado')) {
                $table->boolean('archivado')->default(false)->after('activo');
            }
        });

        // 2. Planes de estudio y versiones curriculares
        if (!Schema::hasTable('planes_estudio')) {
            Schema::create('planes_estudio', function (Blueprint $table) {
                $table->id('plan_id');
                $table->foreignId('programa_id')->constrained('programas_educativos', 'programa_id')->cascadeOnDelete();
                $table->string('codigo', 30);
                $table->string('nombre', 150);
                $table->string('version', 20)->default('1.0');
                $table->smallInteger('anio_aprobacion')->default(2024);
                $table->date('vigencia_inicio')->nullable();
                $table->date('vigencia_fin')->nullable();
                $table->enum('estado', ['BORRADOR', 'VIGENTE', 'HISTORICO', 'ARCHIVADO'])->default('VIGENTE');
                $table->boolean('es_vigente')->default(true);
                $table->tinyInteger('total_cuatrimestres')->default(10);
                $table->text('descripcion')->nullable();
                $table->timestamps();

                $table->unique(['programa_id', 'codigo', 'version'], 'idx_planes_prog_cod_ver_unique');
            });
        }

        // 3. Enriquecimiento de asignaturas (planes, tipos, estadías y optativas)
        Schema::table('asignaturas', function (Blueprint $table) {
            if (!Schema::hasColumn('asignaturas', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('programa_id')->constrained('planes_estudio', 'plan_id')->nullOnDelete();
            }
            if (!Schema::hasColumn('asignaturas', 'tipo')) {
                $table->enum('tipo', ['ORDINARIA', 'OPTATIVA', 'ESTADIA', 'PROYECTO_INTEGRADOR'])->default('ORDINARIA')->after('nombre');
            }
            if (!Schema::hasColumn('asignaturas', 'es_estadia')) {
                $table->boolean('es_estadia')->default(false)->after('tipo');
            }
            if (!Schema::hasColumn('asignaturas', 'es_optativa')) {
                $table->boolean('es_optativa')->default(false)->after('es_estadia');
            }
            if (!Schema::hasColumn('asignaturas', 'horas_semana')) {
                $table->tinyInteger('horas_semana')->nullable()->after('cuatrimestre');
            }
            if (!Schema::hasColumn('asignaturas', 'creditos')) {
                $table->tinyInteger('creditos')->nullable()->after('horas_semana');
            }
            if (!Schema::hasColumn('asignaturas', 'orden')) {
                $table->tinyInteger('orden')->default(1)->after('creditos');
            }
        });

        // 4. Parciales configurables por período académico
        if (!Schema::hasTable('periodo_parciales')) {
            Schema::create('periodo_parciales', function (Blueprint $table) {
                $table->id('parcial_id');
                $table->foreignId('periodo_id')->constrained('periodos_academicos', 'periodo_id')->cascadeOnDelete();
                $table->tinyInteger('numero_parcial'); // 1, 2, 3
                $table->string('nombre', 60);
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->unique(['periodo_id', 'numero_parcial'], 'idx_periodo_parciales_unique');
            });
        }

        // 5. Enriquecimiento de recompensas (propuestas personalizadas de docentes)
        Schema::table('recompensas_catalogo', function (Blueprint $table) {
            if (!Schema::hasColumn('recompensas_catalogo', 'docente_solicitante_id')) {
                $table->foreignId('docente_solicitante_id')->nullable()->after('categoria')->constrained('users', 'usuario_id')->nullOnDelete();
            }
            if (!Schema::hasColumn('recompensas_catalogo', 'estado_propuesta')) {
                $table->enum('estado_propuesta', ['INSTITUCIONAL', 'PROPUESTA_PENDIENTE', 'PROPUESTA_APROBADA', 'PROPUESTA_RECHAZADA'])
                    ->default('INSTITUCIONAL')
                    ->after('docente_solicitante_id');
            }
            if (!Schema::hasColumn('recompensas_catalogo', 'justificacion_docente')) {
                $table->text('justificacion_docente')->nullable()->after('condiciones_generales');
            }
            if (!Schema::hasColumn('recompensas_catalogo', 'motivo_resolucion_admin')) {
                $table->text('motivo_resolucion_admin')->nullable()->after('justificacion_docente');
            }
        });

        // 6. Enriquecimiento de recompensas_canjes (código digital único, trazabilidad y estado UTILIZADA)
        Schema::table('recompensas_canjes', function (Blueprint $table) {
            if (!Schema::hasColumn('recompensas_canjes', 'codigo_beneficio')) {
                $table->string('codigo_beneficio', 40)->nullable()->unique()->after('canje_id');
            }
            if (!Schema::hasColumn('recompensas_canjes', 'fecha_canje')) {
                $table->timestamp('fecha_canje')->nullable()->after('puntos_canjeados');
            }
            if (!Schema::hasColumn('recompensas_canjes', 'fecha_utilizacion')) {
                $table->timestamp('fecha_utilizacion')->nullable()->after('fecha_canje');
            }
            if (!Schema::hasColumn('recompensas_canjes', 'utilizado_por')) {
                $table->foreignId('utilizado_por')->nullable()->after('fecha_utilizacion')->constrained('users', 'usuario_id')->nullOnDelete();
            }
            if (!Schema::hasColumn('recompensas_canjes', 'condiciones_snapshot')) {
                $table->text('condiciones_snapshot')->nullable()->after('motivo_rechazo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recompensas_canjes', function (Blueprint $table) {
            if (Schema::hasColumn('recompensas_canjes', 'utilizado_por')) {
                $table->dropForeign(['utilizado_por']);
                $table->dropColumn(['codigo_beneficio', 'fecha_canje', 'fecha_utilizacion', 'utilizado_por', 'condiciones_snapshot']);
            }
        });

        Schema::table('recompensas_catalogo', function (Blueprint $table) {
            if (Schema::hasColumn('recompensas_catalogo', 'docente_solicitante_id')) {
                $table->dropForeign(['docente_solicitante_id']);
                $table->dropColumn(['docente_solicitante_id', 'estado_propuesta', 'justificacion_docente', 'motivo_resolucion_admin']);
            }
        });

        Schema::dropIfExists('periodo_parciales');

        Schema::table('asignaturas', function (Blueprint $table) {
            if (Schema::hasColumn('asignaturas', 'plan_id')) {
                $table->dropForeign(['plan_id']);
                $table->dropColumn(['plan_id', 'tipo', 'es_estadia', 'es_optativa', 'horas_semana', 'creditos', 'orden']);
            }
        });

        Schema::dropIfExists('planes_estudio');

        Schema::table('programas_educativos', function (Blueprint $table) {
            $table->dropColumn(['clave_historica', 'nombre_historico', 'archivado']);
        });
    }
};


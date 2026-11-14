<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Incremento 20.2 / Corrección de Aceptación Manual 01:
     * Ampliación no destructiva para soporte completo de tipos de reactivos
     * (ESCALA, OPCION_UNICA, OPCION_MULTIPLE, TEXTO_LIBRE), obligatoriedad,
     * opciones por pregunta, estado de retiro e historial de respuestas mixtas.
     */
    public function up(): void
    {
        if (Schema::hasTable('evaluaciones_instrumentos')) {
            Schema::table('evaluaciones_instrumentos', function (Blueprint $table) {
                if (!Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
                    $table->boolean('retirado')->default(false)->after('activo');
                }
            });
        }

        if (Schema::hasTable('evaluaciones_preguntas')) {
            Schema::table('evaluaciones_preguntas', function (Blueprint $table) {
                if (!Schema::hasColumn('evaluaciones_preguntas', 'tipo_pregunta')) {
                    $table->string('tipo_pregunta', 30)->default('ESCALA')->after('enunciado');
                }
                if (!Schema::hasColumn('evaluaciones_preguntas', 'es_obligatoria')) {
                    $table->boolean('es_obligatoria')->default(true)->after('tipo_pregunta');
                }
                if (!Schema::hasColumn('evaluaciones_preguntas', 'opciones_json')) {
                    $table->json('opciones_json')->nullable()->after('es_obligatoria');
                }
                if (!Schema::hasColumn('evaluaciones_preguntas', 'descripcion_ayuda')) {
                    $table->string('descripcion_ayuda', 255)->nullable()->after('opciones_json');
                }
            });
        }

        if (Schema::hasTable('evaluaciones_respuestas')) {
            Schema::table('evaluaciones_respuestas', function (Blueprint $table) {
                $table->unsignedBigInteger('escala_id')->nullable()->change();
                if (!Schema::hasColumn('evaluaciones_respuestas', 'respuesta_texto')) {
                    $table->text('respuesta_texto')->nullable()->after('escala_id');
                }
                if (!Schema::hasColumn('evaluaciones_respuestas', 'opciones_seleccionadas')) {
                    $table->json('opciones_seleccionadas')->nullable()->after('respuesta_texto');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('evaluaciones_respuestas')) {
            Schema::table('evaluaciones_respuestas', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('evaluaciones_respuestas', 'opciones_seleccionadas')) {
                    $cols[] = 'opciones_seleccionadas';
                }
                if (Schema::hasColumn('evaluaciones_respuestas', 'respuesta_texto')) {
                    $cols[] = 'respuesta_texto';
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('evaluaciones_preguntas')) {
            Schema::table('evaluaciones_preguntas', function (Blueprint $table) {
                $cols = [];
                foreach (['descripcion_ayuda', 'opciones_json', 'es_obligatoria', 'tipo_pregunta'] as $col) {
                    if (Schema::hasColumn('evaluaciones_preguntas', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('evaluaciones_instrumentos') && Schema::hasColumn('evaluaciones_instrumentos', 'retirado')) {
            Schema::table('evaluaciones_instrumentos', function (Blueprint $table) {
                $table->dropColumn('retirado');
            });
        }
    }
};


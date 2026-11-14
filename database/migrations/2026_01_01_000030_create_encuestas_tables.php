<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuestas', function (Blueprint $table) {
            $table->id('encuesta_id');
            $table->string('titulo', 150);
            $table->string('slug', 160)->unique();
            $table->text('descripcion')->nullable();
            $table->text('objetivo')->nullable();
            $table->enum('estado', ['BORRADOR', 'PUBLICADA', 'CERRADA'])->default('BORRADOR');
            $table->enum('tipo', ['GENERAL', 'POST_CITA'])->default('GENERAL');
            $table->date('disponible_desde')->nullable();
            $table->date('disponible_hasta')->nullable();
            $table->boolean('es_anonima')->default(true);
            $table->boolean('permite_multiples_respuestas')->default(false);
            $table->foreignId('creado_por')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
            $table->timestamps();

            $table->index(['estado', 'tipo'], 'idx_encuestas_estado_tipo');
        });

        Schema::create('encuestas_preguntas', function (Blueprint $table) {
            $table->id('pregunta_id');
            $table->foreignId('encuesta_id')->constrained('encuestas', 'encuesta_id')->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->text('texto_pregunta');
            $table->enum('tipo_pregunta', ['LIKERT', 'OPCION_UNICA', 'OPCION_MULTIPLE', 'VALORACION', 'TEXTO_LIBRE'])->default('LIKERT');
            $table->boolean('es_obligatoria')->default(true);
            $table->json('opciones')->nullable();
            $table->timestamps();

            $table->index(['encuesta_id', 'orden'], 'idx_preguntas_encuesta_orden');
        });

        Schema::create('encuestas_respuestas', function (Blueprint $table) {
            $table->id('respuesta_id');
            $table->foreignId('encuesta_id')->constrained('encuestas', 'encuesta_id')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas_orientacion', 'cita_id')->nullOnDelete();
            $table->dateTime('fecha_respuesta');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['encuesta_id', 'usuario_id'], 'idx_respuestas_enc_user');
            $table->index(['encuesta_id', 'fecha_respuesta'], 'idx_respuestas_enc_fecha');
        });

        Schema::create('encuestas_respuestas_detalles', function (Blueprint $table) {
            $table->id('detalle_id');
            $table->foreignId('respuesta_id')->constrained('encuestas_respuestas', 'respuesta_id')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('encuestas_preguntas', 'pregunta_id')->cascadeOnDelete();
            $table->decimal('valor_numerico', 5, 2)->nullable();
            $table->text('texto_respuesta')->nullable();
            $table->json('opciones_seleccionadas')->nullable();
            $table->timestamps();

            $table->index(['respuesta_id', 'pregunta_id'], 'idx_detalles_resp_preg');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas_respuestas_detalles');
        Schema::dropIfExists('encuestas_respuestas');
        Schema::dropIfExists('encuestas_preguntas');
        Schema::dropIfExists('encuestas');
    }
};


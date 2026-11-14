<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_consentimientos', function (Blueprint $table) {
            $table->id('consentimiento_id');
            $table->foreignId('usuario_id')
                ->constrained('estudiantes', 'usuario_id')
                ->onDelete('restrict');
            $table->foreignId('version_id')
                ->constrained('evaluaciones_versiones', 'version_id')
                ->onDelete('restrict');
            $table->foreignId('intento_id')
                ->nullable()
                ->constrained('evaluaciones_intentos', 'intento_id')
                ->onDelete('cascade');
            $table->string('consentimiento_version', 30)->default('v1.0-provisional');
            $table->text('texto_consentimiento');
            $table->dateTime('fecha_consentimiento');
            $table->string('ip_registro', 45)->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'version_id'], 'idx_eval_consent_usr_ver');
            $table->index('intento_id', 'idx_eval_consent_intento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_consentimientos');
    }
};


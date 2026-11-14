<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meditaciones', function (Blueprint $table) {
            $table->id('meditacion_id');
            $table->string('titulo', 150);
            $table->string('slug', 160)->unique();
            $table->string('categoria', 50)->default('RESPIRACION'); // RESPIRACION, RELAJACION_MUSCULAR, MINDFULNESS, PAUSA_ESTUDIO, DESCANSO
            $table->text('descripcion');
            $table->json('instrucciones'); // Pasos estructurados con paso, titulo, duracion_segundos, indicacion, fase
            $table->unsignedSmallInteger('duracion_minutos')->default(5);
            $table->enum('nivel', ['PRINCIPIANTE', 'INTERMEDIO', 'AVANZADO'])->default('PRINCIPIANTE');
            $table->string('icono', 100)->nullable();
            $table->boolean('es_publicado')->default(true);
            $table->unsignedInteger('vistas_count')->default(0);
            $table->foreignId('creado_por')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
            $table->timestamps();

            $table->index(['categoria', 'es_publicado'], 'idx_meditaciones_cat_pub');
            $table->index(['nivel', 'es_publicado'], 'idx_meditaciones_niv_pub');
        });

        Schema::create('meditaciones_sesiones', function (Blueprint $table) {
            $table->id('sesion_id');
            $table->foreignId('meditacion_id')->constrained('meditaciones', 'meditacion_id')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users', 'usuario_id')->onDelete('cascade');
            $table->unsignedInteger('duracion_segundos')->default(0);
            $table->boolean('completada')->default(true);
            $table->dateTime('fecha_sesion');
            $table->timestamps();

            $table->index(['usuario_id', 'created_at'], 'idx_sesiones_user_fecha');
            $table->index(['meditacion_id', 'completada'], 'idx_sesiones_med_comp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meditaciones_sesiones');
        Schema::dropIfExists('meditaciones');
    }
};


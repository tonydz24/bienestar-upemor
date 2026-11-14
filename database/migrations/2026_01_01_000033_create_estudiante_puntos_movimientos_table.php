<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante_puntos_movimientos', function (Blueprint $table) {
            $table->id('movimiento_id');
            $table->foreignId('estudiante_id')->constrained('users', 'usuario_id')->onDelete('cascade');
            $table->string('tipo', 40); // EMOCION_DIARIA, MEDITACION, EVALUACION, ENCUESTA, RUTINA_SEMANAL, CANJE_RECOMPENSA, REEMBOLSO_CANJE, AJUSTE_ADMINISTRATIVO
            $table->integer('puntos'); // Positivo (ganancia) o negativo (gasto)
            $table->integer('saldo_anterior')->default(0);
            $table->integer('saldo_nuevo')->default(0);
            $table->string('referencia_tipo', 80)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->date('fecha');
            $table->string('descripcion', 255);
            $table->timestamps();

            $table->index(['estudiante_id', 'fecha'], 'idx_puntos_estudiante_fecha');
            $table->index(['tipo', 'fecha'], 'idx_puntos_tipo_fecha');
            $table->index(['referencia_tipo', 'referencia_id'], 'idx_puntos_referencia');
        });

        // Apertura contable: Inicializar movimientos de saldo histórico para estudiantes con puntos preexistentes
        if (Schema::hasTable('estudiantes')) {
            $estudiantesConSaldo = DB::table('estudiantes')->where('saldo_puntos', '>', 0)->get();
            foreach ($estudiantesConSaldo as $est) {
                DB::table('estudiante_puntos_movimientos')->insert([
                    'estudiante_id' => $est->usuario_id,
                    'tipo' => 'SALDO_INICIAL',
                    'puntos' => (int) $est->saldo_puntos,
                    'saldo_anterior' => 0,
                    'saldo_nuevo' => (int) $est->saldo_puntos,
                    'referencia_tipo' => null,
                    'referencia_id' => null,
                    'fecha' => now()->toDateString(),
                    'descripcion' => 'Apertura contable: saldo histórico acumulado previo al libro mayor auditado',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_puntos_movimientos');
    }
};


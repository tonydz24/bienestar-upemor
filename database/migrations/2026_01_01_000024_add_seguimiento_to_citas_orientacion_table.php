<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas_orientacion', function (Blueprint $table) {
            $table->unsignedTinyInteger('sesion_numero')->default(1)->after('estado');
            $table->enum('tipo_seguimiento', ['INICIAL', 'SEGUIMIENTO', 'CIERRE', 'CANALIZACION_SUGERIDA'])
                ->default('INICIAL')
                ->after('sesion_numero');
        });
    }

    public function down(): void
    {
        Schema::table('citas_orientacion', function (Blueprint $table) {
            $table->dropColumn(['sesion_numero', 'tipo_seguimiento']);
        });
    }
};


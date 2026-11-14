<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('evaluaciones_respuestas') && ! Schema::hasColumn('evaluaciones_respuestas', 'cliente_timestamp')) {
            Schema::table('evaluaciones_respuestas', function (Blueprint $table) {
                $table->unsignedBigInteger('cliente_timestamp')->nullable()->after('puntos_obtenidos');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('evaluaciones_respuestas') && Schema::hasColumn('evaluaciones_respuestas', 'cliente_timestamp')) {
            Schema::table('evaluaciones_respuestas', function (Blueprint $table) {
                $table->dropColumn('cliente_timestamp');
            });
        }
    }
};


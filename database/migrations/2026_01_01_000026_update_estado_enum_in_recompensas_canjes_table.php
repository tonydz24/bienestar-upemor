<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ampliar la columna estado en recompensas_canjes para admitir el ciclo completo de beneficios digitales:
        // CANJEADA, UTILIZADA, EXPIRADA, ANULADA además de los estados previos SOLICITADO, APROBADO, RECHAZADO, CANCELADO.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE recompensas_canjes MODIFY COLUMN estado VARCHAR(30) NOT NULL DEFAULT 'SOLICITADO'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE recompensas_canjes MODIFY COLUMN estado ENUM('SOLICITADO', 'APROBADO', 'RECHAZADO', 'CANCELADO') NOT NULL DEFAULT 'SOLICITADO'");
        }
    }
};

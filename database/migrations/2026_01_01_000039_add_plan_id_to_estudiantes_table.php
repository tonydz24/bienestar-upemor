<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('estudiantes') && !Schema::hasColumn('estudiantes', 'plan_id')) {
            Schema::table('estudiantes', function (Blueprint $table) {
                $table->foreignId('plan_id')
                    ->nullable()
                    ->after('programa_id')
                    ->constrained('planes_estudio', 'plan_id')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('estudiantes') && Schema::hasColumn('estudiantes', 'plan_id')) {
            Schema::table('estudiantes', function (Blueprint $table) {
                $table->dropForeign(['plan_id']);
                $table->dropColumn('plan_id');
            });
        }
    }
};


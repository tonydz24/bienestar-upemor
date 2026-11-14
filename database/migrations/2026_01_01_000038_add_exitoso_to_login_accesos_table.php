<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('login_accesos') && !Schema::hasColumn('login_accesos', 'exitoso')) {
            Schema::table('login_accesos', function (Blueprint $table) {
                $table->boolean('exitoso')->default(true)->after('user_agent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('login_accesos') && Schema::hasColumn('login_accesos', 'exitoso')) {
            Schema::table('login_accesos', function (Blueprint $table) {
                $table->dropColumn('exitoso');
            });
        }
    }
};


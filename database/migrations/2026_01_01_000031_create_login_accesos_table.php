<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_accesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users', 'usuario_id')->nullOnDelete();
            $table->foreignId('rol_id')->nullable()->constrained('roles', 'rol_id')->nullOnDelete();
            $table->string('nombre_usuario', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('rol_nombre', 50)->default('Alumno');
            $table->dateTime('fecha_acceso');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['fecha_acceso', 'rol_nombre'], 'idx_login_fecha_rol');
            $table->index(['usuario_id', 'fecha_acceso'], 'idx_login_user_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_accesos');
    }
};


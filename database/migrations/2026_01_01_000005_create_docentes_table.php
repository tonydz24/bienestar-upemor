<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->foreignId('usuario_id')->primary()->constrained('users', 'usuario_id')->onDelete('cascade');
            $table->string('numero_empleado', 30)->unique();
            $table->string('puesto', 100)->nullable();
            $table->string('especialidad', 150)->nullable();
            $table->string('cedula_profesional', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
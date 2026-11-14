<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_roles', function (Blueprint $table) {
            $table->id('usuario_rol_id');
            $table->foreignId('usuario_id')->constrained('users', 'usuario_id')->cascadeOnDelete();
            $table->foreignId('rol_id')->constrained('roles', 'rol_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['usuario_id', 'rol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_roles');
    }
};


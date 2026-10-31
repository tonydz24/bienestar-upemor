<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Laravel 12 gestiona el cambio de enum en SQLite de forma segura.
            Schema::table('users', function (Blueprint $table) {
                $table->enum('estado', ['ACTIVO', 'INACTIVO', 'BLOQUEADO', 'PENDIENTE', 'RECHAZADO'])
                    ->default('PENDIENTE')
                    ->change();
            });
        } else {
            // MariaDB: ALTER de ENUM directo, preserva índices, claves y datos.
            DB::statement(
                "ALTER TABLE users MODIFY estado ENUM('ACTIVO','INACTIVO','BLOQUEADO','PENDIENTE','RECHAZADO') NOT NULL DEFAULT 'PENDIENTE'"
            );
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('nombre');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Nunca eliminamos ni transformamos cuentas PENDIENTE o RECHAZADO silenciosamente.
        $pendingCount = DB::table('users')->whereIn('estado', ['PENDIENTE', 'RECHAZADO'])->count();
        if ($pendingCount > 0) {
            throw new \RuntimeException(
                'No se puede revertir: existen ' . $pendingCount . ' cuenta(s) PENDIENTE o RECHAZADO. ' .
                'Resuelve esas cuentas manualmente antes de revertir la migración.'
            );
        }

        if ($driver === 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('estado', ['ACTIVO', 'INACTIVO', 'BLOQUEADO'])->default('ACTIVO')->change();
            });
        } else {
            DB::statement(
                "ALTER TABLE users MODIFY estado ENUM('ACTIVO','INACTIVO','BLOQUEADO') NOT NULL DEFAULT 'ACTIVO'"
            );
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });
    }
};

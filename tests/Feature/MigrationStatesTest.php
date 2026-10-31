<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationStatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_accepts_all_account_states(): void
    {
        $role = \App\Models\Rol::factory()->create();

        foreach (['ACTIVO', 'INACTIVO', 'BLOQUEADO', 'PENDIENTE', 'RECHAZADO'] as $estado) {
            $user = \App\Models\User::factory()->withRole($role)->create([
                'email' => strtolower($estado) . '@example.test',
                'estado' => $estado,
            ]);
            $this->assertSame($estado, $user->estado);
        }

        $this->assertDatabaseCount('users', 5);
    }

    public function test_default_estado_is_pendiente_when_not_specified(): void
    {
        $role = \App\Models\Rol::factory()->create();
        $user = \App\Models\User::factory()->withRole($role)->create([
            'email' => 'default@example.test',
        ]);

        $this->assertSame('ACTIVO', $user->estado);
    }

    public function test_roles_nombre_is_unique(): void
    {
        \App\Models\Rol::factory()->create(['nombre' => 'Docente']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        \App\Models\Rol::factory()->create(['nombre' => 'Docente']);
    }

    public function test_down_migration_refuses_to_run_with_pending_or_rejected_accounts(): void
    {
        $role = \App\Models\Rol::factory()->create();
        \App\Models\User::factory()->withRole($role)->create([
            'email' => 'pending@example.test',
            'estado' => 'PENDIENTE',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/PENDIENTE/');

        $migration = include database_path('migrations/2026_01_01_000006_add_account_statuses_and_unique_role_name.php');
        $migration->down();
    }
}

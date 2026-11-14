<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RespaldosAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $alumno;
    protected User $docente;
    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create(['rol_id' => 1, 'nombre' => 'Administrador']);
        $rolDocente = Rol::create(['rol_id' => 2, 'nombre' => 'Docente']);
        $rolAlumno = Rol::create(['rol_id' => 3, 'nombre' => 'Alumno']);

        $this->admin = User::factory()->create([
            'rol_id' => $rolAdmin->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'admin@upemor.edu.mx',
            'password' => bcrypt('AdminSecret123!'),
        ]);

        $this->docente = User::factory()->create([
            'rol_id' => $rolDocente->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'docente@upemor.edu.mx',
        ]);

        $this->alumno = User::factory()->create([
            'rol_id' => $rolAlumno->rol_id,
            'estado' => 'ACTIVO',
            'email' => 'alumno@upemor.edu.mx',
        ]);

        $this->backupDir = storage_path('app/backups');
        if (!File::isDirectory($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    public function test_admin_can_access_respaldos_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.respaldos.index'));
        $response->assertStatus(200);
        $response->assertSee('Respaldos de Base de Datos');
        $response->assertSee('Generar Respaldo Ahora');
    }

    public function test_admin_can_trigger_backup_generation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.respaldos.store'));
        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('status');

        $service = app(RespaldoDatabaseService::class);
        $archivos = $service->listarRespaldos();
        $this->assertNotEmpty($archivos);
    }

    public function test_admin_can_download_existing_backup(): void
    {
        // Crear archivo de prueba
        $fileName = 'test_backup_down.sql';
        File::put($this->backupDir . '/' . $fileName, '-- Test backup content --');

        $response = $this->actingAs($this->admin)->get(route('admin.respaldos.descargar', $fileName));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/sql', $response->headers->get('Content-Type'));

        // Limpiar archivo de prueba
        File::delete($this->backupDir . '/' . $fileName);
    }

    public function test_path_traversal_attack_is_blocked(): void
    {
        $maliciousPath = '..%2F..%2F.env';

        $response = $this->actingAs($this->admin)->get(route('admin.respaldos.descargar', $maliciousPath));
        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_delete_backup(): void
    {
        $fileName = 'test_backup_del.sql';
        File::put($this->backupDir . '/' . $fileName, '-- Test backup content --');

        $response = $this->actingAs($this->admin)->delete(route('admin.respaldos.destroy', $fileName));
        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('status');

        $this->assertFileDoesNotExist($this->backupDir . '/' . $fileName);
    }

    public function test_non_admin_cannot_access_or_trigger_respaldos(): void
    {
        $resAlumno = $this->actingAs($this->alumno)->get(route('admin.respaldos.index'));
        $resAlumno->assertStatus(403);

        $resDocente = $this->actingAs($this->docente)->get(route('admin.respaldos.index'));
        $resDocente->assertStatus(403);

        $resStore = $this->actingAs($this->alumno)->post(route('admin.respaldos.store'));
        $resStore->assertStatus(403);
    }

    public function test_admin_can_verify_backup_integrity(): void
    {
        $fileName = 'test_backup_verify.sql';
        File::put($this->backupDir . '/' . $fileName, "-- Header\nCREATE TABLE test (id INT);\nINSERT INTO test VALUES (1);");

        $response = $this->actingAs($this->admin)->post(route('admin.respaldos.verificar', $fileName));
        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('status');

        File::delete($this->backupDir . '/' . $fileName);
    }

    public function test_admin_can_request_safe_restoration_with_password(): void
    {
        $fileName = 'test_backup_restore.sql';
        File::put($this->backupDir . '/' . $fileName, "-- Header\nCREATE TABLE test (id INT);\nINSERT INTO test VALUES (1);");

        $response = $this->actingAs($this->admin)->post(route('admin.respaldos.solicitar-restauracion', $fileName), [
            'password' => 'AdminSecret123!',
        ]);

        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('info');

        File::delete($this->backupDir . '/' . $fileName);
    }

    public function test_restoration_request_rejected_with_invalid_password(): void
    {
        $fileName = 'test_backup_restore_fail.sql';
        File::put($this->backupDir . '/' . $fileName, "-- Header\nCREATE TABLE test (id INT);\nINSERT INTO test VALUES (1);");

        $response = $this->actingAs($this->admin)->post(route('admin.respaldos.solicitar-restauracion', $fileName), [
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('error');

        File::delete($this->backupDir . '/' . $fileName);
    }

    public function test_physical_restoration_pipeline_executes_safely_on_sqlite(): void
    {
        $fileName = 'test_physical_restore.sql';
        $sql = "-- Header\nCREATE TABLE synthetic_test_table (id INT, label VARCHAR(50));\nINSERT INTO synthetic_test_table VALUES (101, 'Sintetico');";
        File::put($this->backupDir . '/' . $fileName, $sql);

        $response = $this->actingAs($this->admin)->post(route('admin.respaldos.solicitar-restauracion', $fileName), [
            'password' => 'AdminSecret123!',
            'modo' => 'fisico',
        ]);

        $response->assertRedirect(route('admin.respaldos.index'));
        $response->assertSessionHas('status');

        // Constatar que en el entorno SQLite de test la tabla sintética fue restaurada físicamente
        $this->assertEquals('Sintetico', \Illuminate\Support\Facades\DB::table('synthetic_test_table')->where('id', 101)->value('label'));

        File::delete($this->backupDir . '/' . $fileName);
    }
}


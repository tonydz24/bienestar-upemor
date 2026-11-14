<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Respaldos\RespaldoDatabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador de Gestión Administrativa de Respaldos de Base de Datos.
 *
 * Administra la generación bajo demanda de respaldos SQL, listado con hash SHA-256,
 * descarga segura, restauración verificada y eliminación física de archivos de respaldo.
 */
class RespaldoAdminController extends Controller
{
    /**
     * Inicializa el controlador inyectando el servicio de operaciones de base de datos y respaldos.
     *
     * @param RespaldoDatabaseService $respaldoService Servicio que encapsula mysqldump, hash y restauración.
     */
    public function __construct(
        protected RespaldoDatabaseService $respaldoService
    ) {}

    /**
     * Listado administrativo de respaldos de base de datos (FN.16).
     */
    public function index(): View
    {
        $respaldos = $this->respaldoService->listarRespaldos();

        return view('admin.respaldos.index', compact('respaldos'));
    }

    /**
     * Generar un nuevo respaldo de la base de datos bajo demanda.
     */
    public function store(Request $request): RedirectResponse
    {
        $resultado = $this->respaldoService->crearRespaldo();

        if ($resultado['success']) {
            return redirect()->route('admin.respaldos.index')
                ->with('success', $resultado['mensaje'])
                ->with('status', $resultado['mensaje']);
        }

        return redirect()->route('admin.respaldos.index')
            ->with('error', $resultado['mensaje']);
    }

    /**
     * Descarga segura de un archivo de respaldo (prevención de Path Traversal).
     */
    public function descargar(string $archivo): BinaryFileResponse|RedirectResponse
    {
        $rutaSegura = $this->respaldoService->obtenerRutaDescarga($archivo);

        if (!$rutaSegura) {
            return redirect()->route('admin.respaldos.index')
                ->with('error', 'El archivo de respaldo solicitado no existe o no es accesible.');
        }

        return response()->download($rutaSegura, basename($rutaSegura), [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Eliminar un archivo de respaldo.
     */
    public function destroy(string $archivo): RedirectResponse
    {
        $exito = $this->respaldoService->eliminarRespaldo($archivo);

        if ($exito) {
            return redirect()->route('admin.respaldos.index')
                ->with('success', "El archivo de respaldo '{$archivo}' ha sido eliminado.")
                ->with('status', "El archivo de respaldo '{$archivo}' ha sido eliminado.");
        }

        return redirect()->route('admin.respaldos.index')
            ->with('error', "No fue posible eliminar el archivo '{$archivo}'.");
    }

    /**
     * Verifica la integridad técnica y la firma SHA-256 de un respaldo.
     */
    public function verificar(string $archivo): RedirectResponse
    {
        $resultado = $this->respaldoService->verificarIntegridad($archivo);

        if ($resultado['valido']) {
            return redirect()->route('admin.respaldos.index')
                ->with('success', "Integridad técnica verificada para '{$resultado['nombre']}'.")
                ->with('status', "Integridad técnica verificada para '{$resultado['nombre']}'.")
                ->with('verificacion_detalle', $resultado);
        }

        return redirect()->route('admin.respaldos.index')
            ->with('error', "Fallo en verificación técnica: {$resultado['mensaje']}")
            ->with('verificacion_detalle', $resultado);
    }

    /**
     * Procesa la solicitud protegida de restauración con reautenticación por contraseña y respaldo preventivo.
     * Requiere la contraseña actual del usuario administrador para confirmar la operación (sin tecnicismos).
     */
    public function solicitarRestauracion(Request $request, string $archivo): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'Debes ingresar tu contraseña de administrador para autorizar la restauración.',
        ]);

        $usuario = $request->user();

        $resultado = $this->respaldoService->ejecutarRestauracionFisica($archivo, $request->input('password'), $usuario);

        if ($resultado['success'] && empty($resultado['simulado'])) {
            return redirect()->route('admin.respaldos.index')
                ->with('success', $resultado['mensaje'])
                ->with('status', $resultado['mensaje'])
                ->with('info', $resultado['mensaje']);
        }

        if (!empty($resultado['simulado'])) {
            return redirect()->route('admin.respaldos.index')
                ->with('warning', $resultado['mensaje'])
                ->with('info', $resultado['mensaje']);
        }

        return redirect()->route('admin.respaldos.index')
            ->with('error', $resultado['mensaje']);
    }
}


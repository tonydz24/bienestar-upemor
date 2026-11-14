<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea la instancia de la aplicación para pruebas y asegura el blindaje de la base de datos.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        // BLINDAJE INCONDICIONAL: Forzar SQLite en memoria y abortar inmediatamente si no es sqlite
        $connection = $app['config']->get('database.default');
        if ($connection !== 'sqlite') {
            throw new \RuntimeException(
                'SEGURIDAD CRÍTICA: Las pruebas automatizadas sólo tienen autorización para ejecutarse sobre SQLite en memoria (:memory:). ' .
                'Se bloqueó inmediatamente la ejecución para proteger la base de datos: ' . $connection
            );
        }

        $dbName = $app['config']->get('database.connections.sqlite.database');
        if ($dbName !== ':memory:') {
            throw new \RuntimeException(
                'SEGURIDAD CRÍTICA: La base SQLite debe ser estrictamente en memoria (:memory:). ' .
                'Se bloqueó la ejecución sobre: ' . $dbName
            );
        }

        return $app;
    }

    /**
     * Blindaje secundario: gancho previo a cualquier ejecución de RefreshDatabase.
     */
    protected function beforeRefreshingDatabase()
    {
        if (config('database.default') !== 'sqlite') {
            throw new \RuntimeException(
                'SEGURIDAD CRÍTICA: Intento de RefreshDatabase bloqueado. Conexión no autorizada: ' . config('database.default')
            );
        }
    }
}

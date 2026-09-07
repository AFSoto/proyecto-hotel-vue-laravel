<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Blindaje anti-Docker.
     *
     * docker-compose inyecta DB_CONNECTION=mysql / DB_DATABASE=hotelOS como
     * variables de entorno REALES, que se filtran a los tests y hacen que
     * RefreshDatabase corra `migrate:fresh` contra la base de DESARROLLO,
     * borrándola. Aquí forzamos sqlite en memoria sobre la app ya booteada,
     * ANTES de que RefreshDatabase toque nada, sin depender del entorno.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');
    }
}

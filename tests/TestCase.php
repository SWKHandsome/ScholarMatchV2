<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if ($app['config']->get('database.default') === 'mysql' && ! str_ends_with($app['config']->get('database.connections.mysql.database'), '_test')) {
            throw new \RuntimeException('MySQL acceptance tests require a separate database whose name ends in _test.');
        }

        return $app;
    }
}

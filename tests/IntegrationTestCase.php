<?php

declare(strict_types=1);

namespace AndyDefer\Mixins\Tests;

use AndyDefer\Directive\Helpers\Paths;
use AndyDefer\LaravelChronos\Providers\LaravelChronosServiceProvider;
use AndyDefer\LaravelCluster\Providers\ClusterServiceProvider;
use AndyDefer\Mixins\MixinsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class IntegrationTestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ClusterServiceProvider::class,
            LaravelChronosServiceProvider::class,
            MixinsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configuration Chronos pour les tests
        $app['config']->set('chronos.min_durations.slot_search', 30);
        $app['config']->set('chronos.default_search_days', 30);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->runMigrations();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    protected function runMigrations(): void
    {
        $packagePaths = [
            __DIR__.'/Fixtures/migrations',
            Paths::packageRoot().'/../laravel-chronos/database/migrations',
            Paths::packageRoot().'/../laravel-ratings/database/migrations',
        ];

        foreach ($packagePaths as $path) {
            if (is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }
    }
}

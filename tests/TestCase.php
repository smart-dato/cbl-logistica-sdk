<?php

namespace SmartDato\CblLogistica\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SmartDato\CblLogistica\CblLogisticaServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            CblLogisticaServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('cache.default', 'array');
    }
}

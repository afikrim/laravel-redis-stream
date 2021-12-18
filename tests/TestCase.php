<?php

namespace Afikrim\LaravelRedisStream\Tests;

use Afikrim\LaravelRedisStream\LaravelRedisStreamServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelRedisStreamServiceProvider::class,
        ];
    }
}

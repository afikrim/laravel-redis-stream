<?php

namespace Afikrim\LaravelRedisStream;

use Illuminate\Support\ServiceProvider;

class LaravelRedisStreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Console\AddCommand::class,
            Console\ConsumeCommand::class,
            Console\DeclareGroupCommand::class,
            Console\DelCommand::class,
            Console\DestroyGroupCommand::class,
        ]);
    }
}

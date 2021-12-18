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
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ConsumeCommand::class,
            ]);
        }

        $this->commands([
            Console\DeclareGroupCommand::class,
            Console\DestroyGroupCommand::class,
        ]);
    }
}

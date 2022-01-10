# Laravel Redis Stream

Laravel Redis Stream is a package to help you handle event streaming between different applications powered by Redis.

Main concept of this package is to provide an easy way of storing new events from your application and consume it in your other applications.

## Installation

You can install this package via composer using this command:

```sh
composer require afikrim/laravel-redis-stream
```

### Installation on Lumen

After you install the package via composer, register a new service provider in `bootstrap/app.php`

```php
$app->register(\Afikrim\LaravelRedisStream\LaravelRedisStreamServiceProvider::class);
```

> Note: don't forget to uncomment facades and register redis

## Add configuration

Add new redis connection for the `stream` :

```php
<?php
...
    'redis' => [
        ...

        'stream' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_STREAM_DB', '3'),
            'prefix' => env('REDIS_STREAM_PREFIX', Str::slug(strtolower(config('app.env')) . '.' . strtolower(config('app.name')), '.')) . ':',
        ]
    ],
...
```

## Available commands

There are three commands includes in this packages,

1. `stream:declare-group` command to declare a group for a stream
2. `stream:destroy-group` command to destroy a group for a stream
3. `stream:consume` command to consume incoming event

## Add custom consume handler

You can add your custom consumer by extending our `ConsumeCommand` in `app/Console/Commands`

```php
<?php

namespace App\Console\Commands;

use Afikrim\LaravelRedisStream\Console\ConsumeCommand as ConsoleConsumeCommand;

class ConsumeCommand extends ConsoleConsumeCommand
{
    /**
     * Function to listen to new event stream
     */
    protected function listen()
    {
        $options = [];
        if ($this->hasOption('group')) {
            $options['group'] = $this->option('group');
        }
        if ($this->hasOption('consumer')) {
            $options['consumer'] = $this->option('consumer');
        }
        if ($this->hasOption('count')) {
            $options['count'] = $this->option('count');
        }
        if ($this->hasOption('block')) {
            $options['block'] = $this->option('block');
        }
        if ($this->laravel->config->get('redis.stream.prefix')) {
            $options['prefix'] = $this->laravel->config->get('redis.stream.prefix');
        }

        $server = new TransporterServer($options);
        $server->addHandler('mystream', function ($result) {return $result;});
        $server->addHandler('mystream2', function ($result) {return $result;});
        // And another awesome handlers...
        $server->listen();
    }
}
```

Then add your custom `ConsumeCommand` to `App\Console\Kernel` class

```php
protected $commands = [
    // you other command,
    \App\Console\Commands\ConsumeCommad::class
]
```

## Send data to the stream

To send your data to the stream, you can use `ClientProxy` class.

### Send data and listen for the response

```php
...
use Afikrim\LaravelRedisStream\ClientProxy;

...
    $results = ClientProxy::init($options)
        ->publish('mystream2', [
            'name' => 'Aziz',
            'email' => "afikrim10@gmail.com",
        ])
        ->subscribe('mystream2', 60);
...
```

### Send data without waiting any response

```php
...
use Afikrim\LaravelRedisStream\ClientProxy;

...
    ClientProxy::init($options)
        ->dispatch('mystream2', [
            'name' => 'Aziz',
            'email' => "afikrim10@gmail.com",
        ]);
...
```

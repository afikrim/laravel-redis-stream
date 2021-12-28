# Laravel Redis Stream

Laravel Redis Stream is a package to help you handle event streaming between different applications powered by Redis.

Main concept of this package is to provide an easy way of storing new events from your application and consume it in your other applications.

## Installation

You can install this package via composer using this command:

```sh
composer require afikrim/laravel-redis-stream
```

## Installation on Lumen

After you install the package via composer, register a new service provider in `bootstrap/app.php`

```php
$app->register(\Afikrim\LaravelRedisStream\LaravelRedisStreamServiceProvider::class);
```

> Note: don't forget to uncomment facades and register redis

### Available commands

There are three commands includes in this packages,

1. `stream:declare-group` command to declare a group for a stream
2. `stream:destroy-group` command to destroy a group for a stream
3. `stream:consume` command to consume incoming event

### Add custom consume handler

You can add your custom consumer by extending our `ConsumeCommand` in `app/Console/Commands`

```php
<?php

namespace App\Console\Commands;

use Afikrim\LaravelRedisStream\Console\ConsumeCommand as BaseConsume;

class ConsumeCommand extends BaseConsume
{
	/**
	 * Function to handle incoming stream
	 *
	 * @param string $key    The stream key
	 * @param array $data    The incoming data
	 */
	protected function processData($key, array $data)
	{
        try {
            // Write your custom handler here.
        } catch (\Exception $e) {
            // do nothing
        }
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

### Send data to the stream

To send your data to the stream, you can use `RedisStream` class.

```php
...
use Afikrim\LaravelRedisStream\RedisStream;

...
    RedisStream::xadd(
        $key,
        $id,
        $data
    );
...
```

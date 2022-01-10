<?php

return [
    'url' => env('REDIS_URL'),
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'password' => env('REDIS_PASSWORD', null),
    'port' => env('REDIS_PORT', '6379'),
    'database' => env('REDIS_STREAM_DB', '3'),
    'prefix' => env('REDIS_STREAM_PREFIX', \Illuminate\Support\Str::slug(strtolower(config('app.env')) . '.' . strtolower(config('app.name')), '.')) . ':',
];

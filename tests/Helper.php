<?php

namespace Afikrim\LaravelRedisStream\Tests;

use Illuminate\Support\Facades\Redis;

class Helper
{
    public static function xinfo(array $arguments)
    {
        $infosRaw = Redis::executeRaw($arguments);
        $infos = collect($infosRaw)
            ->map(function ($infoRaw) {
                $info = [];
                for ($i = 0; $i < count($infoRaw); $i += 2) {
                    $info[$infoRaw[$i]] = $infoRaw[$i + 1];
                }

                return $info;
            });

        return $infos;
    }
}

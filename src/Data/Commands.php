<?php

namespace Afikrim\LaravelRedisStream\Data;

final class Commands
{
    public const XACK = 'xack';
    public const XADD = 'xadd';
    public const XDEL = 'xdel';
    public const XGROUP = 'xgroup';
    public const XREAD = 'xread';
    public const XREADGROUP = 'xreadgroup';
}

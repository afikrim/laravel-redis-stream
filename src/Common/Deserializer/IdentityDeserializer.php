<?php

namespace Afikrim\LaravelRedisStream\Common\Deserializer;

use Illuminate\Support\Facades\Log;

class IdentityDeserializer
{
    public $is_dispossed = false;

    public function __construct(array $packet, bool $reply = false)
    {
        Log::info("Deserialize >>>>>>>>>>>>>>" . PHP_EOL . json_encode($packet) . PHP_EOL . ">>>>>>>>>>>>>>");

        $this->id = $packet['id'];
        $this->pattern = $packet['pattern'];
        if ($reply) {
            $this->response = $packet['response'];
            $this->error = $packet['error'];
        } else {
            $this->data = $packet['data'];
        }

        $this->time = time();
    }
}

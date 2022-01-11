<?php

namespace Afikrim\LaravelRedisStream\Common\Serializer;

use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class IdentitySerializer
{
    public function __construct(array $packet, bool $reply = false)
    {
        Log::info("Serialize >>>>>>>>>>>>>>" . PHP_EOL . json_encode($packet) . PHP_EOL . ">>>>>>>>>>>>>>");

        $this->id = $packet['id'] ?? Uuid::uuid4();
        $this->pattern = $packet['pattern'];
        if ($reply) {
            $this->response = $packet['response'];
            $this->error = $packet['error'] ?? null;
        } else {
            $this->data = $packet['data'];
        }

        $this->time = time();
    }
}

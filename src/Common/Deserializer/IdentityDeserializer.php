<?php

namespace Afikrim\LaravelRedisStream\Common\Deserializer;

use Illuminate\Support\Facades\Log;

class IdentityDeserializer
{
    public function __construct(array $packet, bool $reply = false)
    {
        Log::info("Deserialize >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
        Log::info(json_encode($packet));
        Log::info(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");

        $this->id = $packet['id'];
        $this->pattern = $packet['pattern'];
        if ($reply) {
            $this->response = $packet['response'];
            $this->error = $packet['error'];
        } else {
            $this->data = $packet['data'];
            $this->need_reply = $packet['need_reply'] ?? false;
        }

        $this->time = time();
    }
}

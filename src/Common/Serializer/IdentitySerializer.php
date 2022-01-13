<?php

namespace Afikrim\LaravelRedisStream\Common\Serializer;

use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class IdentitySerializer
{
    public function __construct(array $packet, bool $reply = false, bool $need_reply = false)
    {
        Log::info("Serialize >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
        Log::info(json_encode($packet));
        Log::info(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");

        $this->id = $packet['id'] ?? Uuid::uuid4();
        $this->pattern = $packet['pattern'];
        if ($reply) {
            $this->response = $packet['response'];
            $this->error = $packet['error'] ?? null;
        } else {
            $this->data = $packet['data'];
            $this->need_reply = $need_reply;
        }

        $this->time = time();
    }
}

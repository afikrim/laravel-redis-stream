<?php

namespace Afikrim\LaravelRedisStream;

use Afikrim\LaravelRedisStream\Common\Deserializer\IdentityDeserializer;
use Afikrim\LaravelRedisStream\Common\Serializer\IdentitySerializer;
use Afikrim\LaravelRedisStream\Data\Options;
use Afikrim\LaravelRedisStream\Data\XGROUPOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClientProxy
{
    protected $id;
    protected $options;
    protected $packet;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public static function init($options = [])
    {
        return (new static($options));
    }

    public function publish(string $pattern, array $data)
    {
        Log::info('Generating data to publish at pattern: ' . $pattern);
        $partial_packet = [
            'data' => json_encode($data),
            'pattern' => $pattern,
        ];
        $request = (array) (new IdentitySerializer($partial_packet));

        Log::info('Sending data to pattern: ' . $pattern);
        $this->id = RedisStream::xadd($this->getPattern($pattern), '*', $request);

        return $this;
    }

    public function dispatch(string $pattern, array $data)
    {
        Log::info('Generating data to publish at pattern: ' . $pattern);
        $partial_packet = [
            'data' => json_encode($data),
            'pattern' => $pattern,
        ];
        $request = (array) (new IdentitySerializer($partial_packet));

        Log::info('Sending data to pattern: ' . $pattern);
        RedisStream::xadd($this->getPattern($pattern), '*', $request);
    }

    public function subscribe(string $pattern)
    {
        try {
            // create consumer group
            RedisStream::xgroup(
                XGROUPOptions::OPTION_CREATE,
                $this->getReplyPattern($pattern),
                $this->getOption('group'),
                true,
                [
                    '$',
                ]
            );
        } catch (\Exception$e) {
            // do nothing
        }

        $results = RedisStream::xreadgroup(
            $this->getOption('group'),
            $this->getOption('consumer'),
            [$this->getReplyPattern($pattern)],
            ['>'],
            [
                Options::OPTION_COUNT,
                $this->getOption('count'),
                Options::OPTION_BLOCK,
                $this->getOption('block'),
            ],
        );

        return $this->handleReply($pattern, $results);
    }

    private function handleReply(string $pattern, array $results)
    {
        echo json_encode($results);
        if (count($results) === 0) {
            RedisStream::xdel($pattern, [$this->id]);
            return [];
        }

        [
            'data' => $raw_messages,
        ] = $results[0];

        $raw_message_index = array_search($this->id, array_column($raw_messages, 'id'));
        $raw_message = $raw_messages[$raw_message_index];
        if (!$raw_message) {
            RedisStream::xdel($pattern, [$this->id]);
            return [];
        }

        [
            'id' => $_id,
            'data' => $packet,
        ] = $raw_message;

        $packet = (array) (new IdentityDeserializer($packet, true));
        $response = json_decode($packet['response'], true);

        RedisStream::xack($this->getPattern($packet['pattern']), $this->getOption('group'), [$_id]);

        return $response;
    }

    private function getPattern(string $pattern)
    {
        return $pattern;
    }

    private function getReplyPattern(string $pattern)
    {
        return $pattern . '.reply';
    }

    private function getOption(string $key)
    {
        $value = array_key_exists($key, $this->options) ? $this->options[$key] : null;
        if (!$value) {
            if ($key === 'count') {
                return 1;
            } else if ($key === 'block') {
                return 2000;
            } else if ($key === 'group') {
                return Str::slug(
                    config('app.env')
                    . '.'
                    . config('app.name')
                    . '.group'
                    , '.');
            } else if ($key === 'consumer') {
                return Str::slug(
                    config('app.env')
                    . '.'
                    . config('app.name')
                    . '.consumer'
                    , '.');
            }

            return null;
        }

        if ($key === 'count' || $key === 'block') {
            return (int) $value;
        }

        return $value;
    }
}

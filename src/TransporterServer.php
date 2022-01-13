<?php

namespace Afikrim\LaravelRedisStream;

use Afikrim\LaravelRedisStream\Common\Deserializer\IdentityDeserializer;
use Afikrim\LaravelRedisStream\Common\Serializer\IdentitySerializer;
use Afikrim\LaravelRedisStream\Data\Options;
use Afikrim\LaravelRedisStream\Data\XGROUPOptions;
use Afikrim\LaravelRedisStream\RedisStream;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransporterServer
{
    protected $handlers;
    protected $options;

    public function __construct(array $options)
    {
        $this->handlers = collect([]);
        $this->options = $options;
    }

    public function addHandler(string $pattern, Closure $handler)
    {
        $existingHandler = $this->handlers->where('pattern', $pattern)->first();

        if ($existingHandler) {
            $this->handlers->where('pattern', $pattern)->replace([
                'pattern' => $pattern,
                'handler' => $handler,
            ]);
            return;
        }
        $this->handlers->add([
            'pattern' => $pattern,
            'handler' => $handler,
        ]);
    }

    public function removeHandler(string $pattern)
    {
        $this->handlers = $this->handlers
            ->filter(function ($handler) use ($pattern) {
                return $handler['pattern'] !== $pattern;
            });
    }

    public function listen()
    {
        Log::info('Collecting patterns to listen');
        $streams = $this->handlers
            ->reduce(function ($prev, $handler) {
                $pattern = $handler['pattern'];
                $id = '>';

                return [
                    'patterns' => [...$prev['patterns'], $pattern],
                    'ids' => [...$prev['ids'], $id],
                ];
            }, ['patterns' => [], 'ids' => []]);

        collect($streams['patterns'])->each(function ($pattern) {
            try {
                // create consumer group
                RedisStream::xgroup(
                    XGROUPOptions::OPTION_CREATE,
                    $pattern,
                    $this->getOption('group'),
                    true,
                    [
                        '$',
                    ]
                );
            } catch (\Exception$e) {
                // do nothing
            }
        });

        Log::info('Listening to the stream');
        $results = RedisStream::xreadgroup(
            $this->getOption('group'),
            $this->getOption('consumer'),
            $streams['patterns'],
            $streams['ids'],
            [
                Options::OPTION_COUNT,
                $this->getOption('count'),
                Options::OPTION_BLOCK,
                $this->getOption('block'),
            ],
        );

        $this->handle($results);
    }

    private function handle(array $results)
    {
        foreach ($results as $result) {
            [
                'key' => $key,
                'data' => $raw_messages,
            ] = $result;

            foreach ($raw_messages as $raw_message) {
                [
                    'id' => $_id,
                    'data' => $packet,
                ] = $raw_message;

                [
                    'id' => $id,
                    'data' => $message,
                    'pattern' => $pattern,
                    'need_reply' => $need_reply,
                ] = (array) (new IdentityDeserializer($packet));
                $message = json_decode($message, true);
                if (!$need_reply) {
                    Log::info('Request with pattern: ' . $pattern . ' doesn\'t need any reply. Processing next request...');
                    continue;
                }

                $handler = $this->handlers
                    ->where('pattern', $pattern)
                    ->first();
                if (!$handler) {
                    Log::critical("There is no handler for pattern: {$pattern}");

                    $response_packet = [
                        'id' => $id,
                        'response' => null,
                        'error' => 'There is no handler for this pattern',
                        'pattern' => $pattern,
                    ];
                    $response = (array) (new IdentitySerializer($response_packet, true));

                    Log::info('Sending reply to pattern: ' . $pattern);
                    RedisStream::xadd($this->getReplyPattern($pattern), '*', $response);
                    RedisStream::xack($this->getPattern($pattern), $this->getOption('group'), [$_id]);
                    continue;
                }

                Log::info('Handling request from pattern: ' . $pattern);
                [
                    'data' => $response_data,
                    'error' => $error,
                ] = $handler['handler']($message);
                $response_packet = [
                    'id' => $id,
                    'response' => $response_data ? json_encode($response_data) : null,
                    'pattern' => $pattern,
                    'error' => $error ?? null
                ];
                $response = (array) (new IdentitySerializer($response_packet, true));

                Log::info('Sending reply to pattern: ' . $pattern);
                RedisStream::xadd($this->getReplyPattern($pattern), '*', $response);
                RedisStream::xack($this->getPattern($pattern), $this->getOption('group'), [$_id]);
            }
        }
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

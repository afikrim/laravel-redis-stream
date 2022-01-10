<?php

namespace Afikrim\LaravelRedisStream;

use Afikrim\LaravelRedisStream\Data\Commands;
use Afikrim\LaravelRedisStream\Data\Options;
use Afikrim\LaravelRedisStream\Data\XGROUPOptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class RedisStream
{
    /**
     * Create new RedisStream instances
     *
     * @return void
     */
    public function __construct()
    {
        // do nothing
    }

    /**
     * Function to add new data to the stream
     *
     * @param string $key   The stream key
     * @param string $id    New object ID
     * @param array $data   The data that will be input. $data doesn't support a nested array
     * @return string
     */
    public static function xadd(string $key, string $id = '*', array $data): string
    {
        return (new static )->newXadd($key, $id, $data);
    }

    /**
     * Function to create static function 'xadd'
     *
     * @param string $key   The stream key
     * @param string $id    New object ID
     * @param array $data   The data that will be input
     * @return string
     */
    public function newXadd(string $key, string $id = '*', array $data): string
    {
        $fieldsAndValues = $this->parseData($data);

        $result = Redis::connection('stream')
            ->executeRaw([
                Commands::XADD,
                config('database.redis.stream.prefix', '') . $key,
                $id,
                ...$fieldsAndValues,
            ]);

        return $result;
    }

    /**
     * Function to read from the stream
     *
     * @param array $streams    Streams that will be read
     * @param array $ids        IDs from the stream that will retrieve
     * @param array $options
     * @return \Illuminate\Support\Collection
     */
    public static function xread(array $streams, array $ids, array $options): Collection
    {
        return (new static )->xread($streams, $ids, $options);
    }

    /**
     * Function to create static function 'xread'
     *
     * @param array $streams    Streams that will be read
     * @param array $ids        IDs from the stream that will retrieve
     * @param array $options
     * @return array
     */
    public function newXread(array $streams, array $ids, array $options): array
    {
        $redisArguments = [
            Commands::XREAD,
            ...$options,
            Options::OPTION_STREAMS,
        ];
        foreach ($streams as $stream) {
            $redisArguments[] = config('database.redis.stream.prefix', '') . $stream;
        }
        foreach ($ids as $id) {
            $redisArguments[] = !$id ? '0' : $id;
        }

        $results = Redis::connection('stream')
            ->executeRaw($redisArguments);

        if (is_string($results)) {
            throw new \Exception($results);
        }

        return $this->parseResults($results ?? []);
    }

    /**
     * Function to delete object(s) from stream
     *
     * @param string $key   Stream that will be read
     * @param array $ids    A List of id that will be delete
     * @return void
     */
    public static function xdel(string $key, array $ids): void
    {
        $result = Redis::connection('stream')
            ->executeRaw([
                Commands::XDEL,
                config('database.redis.stream.prefix', '') . $key,
                ...$ids,
            ]);

        if (!preg_match('/^\d+$/', $result)) {
            throw new \Exception($result);
        }
    }

    /**
     * Function to acknowledge event(s)
     *
     * @param string $key       The stream that will be read
     * @param string $group     The consumer group
     * @param array $ids        ID of objects
     * @return void
     */
    public static function xack(string $key, string $group, array $ids): void
    {
        $result = Redis::connection('stream')
            ->executeRaw([
                Commands::XACK,
                config('database.redis.stream.prefix', '') . $key,
                config('database.redis.stream.prefix', '') . $group,
                ...$ids,
            ]);

        if (!preg_match('/^\d+$/', $result)) {
            throw new \Exception($result);
        }
    }

    /**
     * Function to create stream group
     *
     * @param string $option        XGROUP Options as listed on \Afikrim\LaravelRedisStream\Data\XGROUPOptions
     * @param string $key           The stream that will be read
     * @param string $groupname     The group name
     * @param boolean $mkstream     To make a stream when creating group
     * @param array $arguments      Extra arguments, like ID or consumername. eg, ['$'] || ['consumername']
     * @return void
     */
    public static function xgroup(string $option, string $key, string $groupname, bool $mkstream = false, array $arguments = []): void
    {
        $redisArguments = [
            Commands::XGROUP,
            $option,
            config('database.redis.stream.prefix', '') . $key,
            config('database.redis.stream.prefix', '') . $groupname,
            ...$arguments,
        ];
        if ($option === XGROUPOptions::OPTION_CREATE
            && $mkstream) {
            $redisArguments[] = XGROUPOptions::OPTION_CREATE_OPTION_MKSTREAM;
        }

        $result = Redis::connection('stream')
            ->executeRaw($redisArguments);

        if ($result !== 'OK' && !preg_match('/^\d+$/', $result)) {
            throw new \Exception($result);
        }
    }

    /**
     * Function to read from a certain group
     *
     * @param string $group         The group
     * @param string $consumer      Consumer name
     * @param array $streams        Streams that will be read
     * @param array $ids            ID that will be retrieve
     * @param array $options        Extra options, as define in \Afikrim\LaravelRedisStream\Data\Options. eg, [Options::OPTION_BLOCK, 2000]
     * @return array
     */
    public static function xreadgroup(string $group, string $consumer, array $streams, array $ids, array $options): array
    {
        return (new static )->newXreadgroup($group, $consumer, $streams, $ids, $options);
    }

    /**
     * Function to read from a certain group
     *
     * @param string $group         The group
     * @param string $consumer      Consumer name
     * @param array $streams        Streams that will be read
     * @param array $ids            ID that will be retrieve
     * @param array $options        Extra options, as define in \Afikrim\LaravelRedisStream\Data\Options. eg, [Options::OPTION_BLOCK, 2000]
     * @return array
     */
    public function newXreadgroup(string $group, string $consumer, array $streams, array $ids, array $options): array
    {
        $redisArguments = [
            Commands::XREADGROUP,
            Options::OPTION_GROUP,
            config('database.redis.stream.prefix', '') . $group,
            config('database.redis.stream.prefix', '') . $consumer,
            ...$options,
            Options::OPTION_STREAMS,
        ];
        foreach ($streams as $stream) {
            $redisArguments[] = config('database.redis.stream.prefix', '') . $stream;
        }
        foreach ($ids as $id) {
            $redisArguments[] = !$id ? '>' : $id;
        }

        $results = Redis::connection('stream')
            ->executeRaw($redisArguments);

        if (is_string($results)) {
            throw new \Exception($results);
        }

        return $this->parseResults($results ?? []);
    }

    /**
     * Function to parse inserted data to redis arguments
     *
     * @param array $raw
     * @return array
     */
    protected function parseData(array $raw): array
    {
        $data = [];
        foreach ($raw as $field => $value) {
            $data[] = $field;
            $data[] = $value;
        }

        return $data;
    }

    /**
     * Function to parse read result to associative array
     *
     * @param array $result
     * @return array
     */
    protected function parseResult(array $result): array
    {
        [$key, $rawData] = $result;

        $data = collect($rawData)
            ->map(function ($rawData) {
                [$id, $rawData] = $rawData;

                $data = [];
                for ($i = 0; $i < count($rawData); $i += 2) {
                    $value = $rawData[$i + 1];
                    $data["{$rawData[$i]}"] = $value;
                }

                return ['id' => $id, 'data' => $data];
            });

        return ['key' => $key, 'data' => $data->toArray()];
    }

    /**
     * Function to parse read result to associative array
     *
     * @param array $results
     * @return array
     */
    protected function parseResults(array $results): array
    {
        $data = collect($results)
            ->reduce(function ($prev, $current) {
                $result = $this->parseResult($current);

                return [$result, ...$prev];
            }, []);

        return (array) $data;
    }
}

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

    public function xadd(string $key, string $id = '*', array $data): string
    {
        $fieldsAndValues = $this->parseData($data);

        $result = Redis::executeRaw([
            Commands::XADD,
            $key,
            $id,
            ...$fieldsAndValues,
        ]);

        if ($result !== 'OK') {
            throw new \Exception($result);
        }

        return $result;
    }

    public function xread(array $streams, array $ids, array $options): Collection
    {
        $redisArguments = [
            Commands::XREAD,
            ...$options,
            Options::OPTION_STREAMS,
        ];
        foreach ($streams as $stream) {
            $redisArguments[] = $stream;
        }
        foreach ($ids as $id) {
            $redisArguments[] = !$id ? '0' : $id;
        }

        $results = Redis::executeRaw($redisArguments);

        if (is_string($results)) {
            throw new \Exception($results);
        }

        return $this->parseResults($results);
    }

    public function xdel(string $key, array $ids): void
    {
        $result = Redis::executeRaw([
            $key,
            ...$ids,
        ]);

        if (!preg_match('/^\d+$/', $result)) {
            throw new \Exception($result);
        }
    }

    public function xack(string $key, string $group, array $ids): void
    {
        $result = Redis::executeRaw([
            $key,
            $group,
            ...$ids,
        ]);

        if (!preg_match('/^\d+$/', $result)) {
            throw new \Exception($result);
        }
    }

    public function xgroup(string $option, string $key, string $groupname, bool $mkstream = false, array $arguments = []): void
    {
        $redisArguments = [
            $option,
            $key,
            $groupname,
            ...$arguments,
        ];
        if ($option === XGROUPOptions::OPTION_CREATE
            && $mkstream) {
            $redisArguments[] = XGROUPOptions::OPTION_CREATE_OPTION_MKSTREAM;
        }

        $result = Redis::executeRaw($redisArguments);

        if ($result !== 'OK') {
            throw new \Exception($result);
        }
    }

    public function xreadgroup(string $group, string $consumer, array $streams, array $ids, array $options)
    {
        $redisArguments = [
            Options::OPTION_GROUP,
            $group,
            $consumer,
            ...$options,
            Options::OPTION_STREAMS,
        ];
        foreach ($streams as $stream) {
            $redisArguments[] = $stream;
        }
        foreach ($ids as $id) {
            $redisArguments[] = !$id ? '>' : $id;
        }

        $results = Redis::executeRaw($redisArguments);

        if (is_string($results)) {
            throw new \Exception($results);
        }

        return $this->parseResults($results);
    }

    private function parseData(array $raw)
    {
        $data = [];
        foreach ($raw as $field => $value) {
            $data[] = $field;
            $data[] = $value;
        }

        return $data;
    }

    private function parseResult(array $result)
    {
        [$key, $rawData] = $result;

        $data = collect($rawData)
            ->map(function ($rawData) {
                [$id, $rawData] = $rawData;

                $data = [];
                for ($i = 0; $i < count($rawData); $i += 2) {
                    $data["{$rawData[$i]}"] = $rawData[$i + 1];
                }

                return ['id' => $id, 'data' => $data];
            });

        return ['key' => $key, 'data' => $data];
    }

    private function parseResults(array $results)
    {
        $data = collect($results)
            ->reduce(function ($prev, $current) {
                $result = $this->parseResult($current);

                return array_merge(
                    [$result], $prev
                );
            }, []);

        return collect($data);
    }
}

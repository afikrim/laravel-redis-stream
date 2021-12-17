<?php

namespace Afikrim\LaravelRedisStream\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class ConsumeCommand extends Command
{
    protected $signature = 'stream:consume
                            {key* : Specified stream key}
                            {--count=5 : A number of event that will retrieve}
                            {--block=2000 : Blocking timeout of reading command in milis}
                            {--rest=3 : Delay between each read in seconds}';

    protected $description = 'Destroy an object from the stream';

    public function handle(): void
    {
        if (!$this->hasArgument('key')) {
            echo "Key params cannot be null.";
            return;
        }

        try {
            // create consumer group
            foreach ($this->argument('key') as $key) {
                Artisan::call('stream:declare-group', [
                    'key' => $key,
                    'group' => $this->getGroup(),
                ]);
            }
        } catch (\Exception$e) {
            // do nothing
        }

        while (true) {
            $result = $this->readStream();
            if (!$result) {
                continue;
            }

            $data = $this->parseResult($result);

            $data->each(function ($d) {
                ['key' => $key, 'models' => $models] = $d;

                $models->each(function ($model) use ($key) {
                    $this->processData($key, $model);

                    $this->ackStream($key, $model['id']);
                });
            });

            $this->rest();
        }
    }

    protected function parseResult(array $results)
    {
        $data = collect($results)
            ->reduce(function ($prev, $result) {
                [$key, $rawModels] = $result;

                $models = collect($rawModels)
                    ->map(function ($rawModel) {
                        [$id, $rawModelFields] = $rawModel;

                        $object = [];
                        for ($i = 0; $i < count($rawModelFields); $i += 2) {
                            $object["{$rawModelFields[$i]}"] = $rawModelFields[$i + 1];
                        }

                        return array_merge(['id' => $id], $object);
                    });

                return array_merge(
                    [['key' => $key, 'models' => $models]], $prev
                );
            }, []);

        return collect($data);
    }

    protected function processData($key, array $data)
    {
        // Write your handle here.
        echo "{$key}\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }

    protected function getGroup()
    {
        return env('STREAM_GROUP', Str::slug(
            $this->laravel->config->get('app.env')
            . '_'
            . $this->laravel->config->get('app.name')
            . '_group'
            , '_'));
    }

    protected function getConsumer()
    {
        return env('STREAM_GROUP', Str::slug(
            $this->laravel->config->get('app.env')
            . '_'
            . $this->laravel->config->get('app.name')
            . '_consumer'
            , '_'));
    }

    private function readStream()
    {
        $ids = [];
        foreach ($this->argument('key') as $key) {
            $ids[] = '>';
        }

        $result = Redis::executeRaw(
            [
                'XREADGROUP',
                'GROUP',
                $this->getGroup(),
                $this->getConsumer(),
                'BLOCK',
                $this->option('block'),
                'COUNT',
                $this->option('count'),
                'STREAMS',
                ...$this->argument('key'),
                ...$ids,
            ]
        );

        if ($result && is_string($result)) {
            throw new \Exception($result);
        }

        return $result;
    }

    private function ackStream($key, $id)
    {
        $_ack = Redis::executeRaw(
            [
                'XACK',
                $key,
                $this->getGroup(),
                $id,
            ]
        );
    }

    private function rest()
    {
        sleep($this->option('rest'));
    }
}

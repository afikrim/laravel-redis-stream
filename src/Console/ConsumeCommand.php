<?php

namespace Afikrim\LaravelRedisStream\Console;

use Afikrim\LaravelRedisStream\Data\Options;
use Afikrim\LaravelRedisStream\Data\XGROUPOptions;
use Afikrim\LaravelRedisStream\RedisStream;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ConsumeCommand extends Command
{
    protected $signature = 'stream:consume
                            {key* : Specified stream key}
                            {--group : Specified stream group}
                            {--consumer : Specified stream group}
                            {--mkstream=false : Make stream of the group}
                            {--count=5 : A number of event that will retrieve}
                            {--block=2000 : Blocking timeout of reading command in milis}
                            {--rest=3 : Delay between each read in seconds}';

    protected $description = 'Destroy an object from the stream';

    private $redisStream;

    public function __construct(RedisStream $redisStream)
    {
        $this->redisStream = $redisStream;
    }

    public function handle(): void
    {
        if (!$this->hasArgument('key')) {
            echo "Key params cannot be null.";
            return;
        }

        foreach ($this->argument('key') as $key) {
            try {
                // create consumer group
                $this->redisStream
                    ->xgroup(
                        XGROUPOptions::OPTION_CREATE,
                        $key,
                        $this->getGroup(),
                        $this->option('mkstream'),
                        [
                            '$',
                        ]
                    );
            } catch (\Exception$e) {
                // do nothing
            }
        }

        while (true) {
            $data = $this->redisStream
                ->xreadgroup(
                    $this->getGroup(),
                    $this->getConsumer(),
                    $this->argument('key'),
                    collect($this->argument('key'))
                        ->map(function () {
                            return '>';
                        })
                        ->toArray(),
                    [
                        Options::OPTION_COUNT,
                        $this->option('count'),
                        Options::OPTION_BLOCK,
                        $this->option('block'),
                    ]
                );
            if (count($data) === 0) {
                continue;
            }

            $data->each(function ($d) {
                ['key' => $key, 'data' => $data] = $d;

                $data->each(function ($data) use ($key) {
                    $this->processData($key, $data);

                    $this->redisStream
                        ->xack(
                            $key,
                            $this->getGroup(),
                            [$key['id']]
                        );
                });
            });

            $this->rest();
        }
    }

    protected function processData($key, array $data)
    {
        // Write your handle here.
        echo "{$key}\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }

    protected function getGroup()
    {
        return $this->hasOption('group')
        ? $this->option('group')
        : Str::slug(
            $this->laravel->config->get('app.env')
            . '_'
            . $this->laravel->config->get('app.name')
            . '_group'
            , '_');
    }

    protected function getConsumer()
    {
        return $this->hasOption('consumer')
        ? $this->option('consumer')
        : Str::slug(
            $this->laravel->config->get('app.env')
            . '_'
            . $this->laravel->config->get('app.name')
            . '_consumer'
            , '_');
    }

    private function rest()
    {
        sleep($this->option('rest'));
    }
}

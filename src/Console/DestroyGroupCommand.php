<?php

namespace Afikrim\LaravelRedisStream\Console;

use Afikrim\LaravelRedisStream\Data\XGROUPOptions;
use Afikrim\LaravelRedisStream\RedisStream;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DestroyGroupCommand extends Command
{
    protected $signature = 'stream:destroy-group
                            {key : The name of the stream}
                            {group : The name of the consumer group}';

    protected $description = 'Destroy a stream group';

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

        $this->redisStream
            ->xgroup(
                XGROUPOptions::OPTION_DESTROY,
                $this->argument('key'),
                $this->getGroup(),
            );

        echo "Group {$this->getGroup()} successfuly destroyed in {$this->argument('key')}.";
    }

    protected function getGroup()
    {
        return $this->hasArgument('group')
        ? $this->argument('group')
        : Str::slug(
            $this->laravel->config->get('app.env')
            . '_'
            . $this->laravel->config->get('app.name')
            . '_group'
            , '_');
    }
}

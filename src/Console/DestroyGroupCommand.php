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

    public function handle()
    {
        if (!$this->hasArgument('key')) {
            return 1;
        }

        RedisStream::xgroup(
            XGROUPOptions::OPTION_DESTROY,
            $this->argument('key'),
            $this->getGroup(),
        );

        return 0;
    }

    protected function getGroup()
    {
        return $this->hasArgument('group')
        ? $this->argument('group')
        : Str::slug(
            $this->laravel->config->get('app.env')
            . '.'
            . $this->laravel->config->get('app.name')
            . '.group'
            , '.');
    }
}

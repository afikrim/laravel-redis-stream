<?php

namespace Afikrim\LaravelRedisStream\Console;

use Afikrim\LaravelRedisStream\Data\XGROUPOptions;
use Afikrim\LaravelRedisStream\RedisStream;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class DeclareGroupCommand extends Command
{
    protected $signature = 'stream:declare-group
                            {key : The name of the stream}
                            {group : The name of the consumer group}
                            {--mkstream=false : Make stream of the group}
                            {--id=$ : The ID that will be add to consumer group}';

    protected $description = 'Declare a stream group';

    public function handle()
    {
        if (!$this->hasArgument('key')) {
            return 1;
        }

        RedisStream::xgroup(
            XGROUPOptions::OPTION_CREATE,
            $this->argument('key'),
            $this->getGroup(),
            $this->option('mkstream'),
            [
                '$',
            ]
        );

        return 0;
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

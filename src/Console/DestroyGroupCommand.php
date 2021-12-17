<?php

namespace App\Console\Commands\Stream;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DestroyGroupCommand extends Command
{
    protected $signature = 'stream:destroy-group
                            {key : The name of the stream}
                            {group : The name of the consumer group}';

    protected $description = 'Destroy a stream group';

    public function handle(): void
    {
        if (!$this->hasArgument('group') || !$this->hasArgument('key')) {
            echo "Group and Key params cannot be null.";
            return;
        }

        $result = Redis::executeRaw([
            'XGROUP',
            'DESTROY',
            $this->argument('key'),
            $this->argument('group'),
        ]);

        if (!preg_match('/^\d+$/', $result)) {
            throw new \Exception($result);
        }

        echo "Group {$this->argument('group')} successfuly destroyed in {$this->argument('key')}.";
    }
}

<?php

namespace App\Console\Commands\Stream;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DeclareGroupCommand extends Command
{
    protected $signature = 'stream:declare-group
                            {key : The name of the stream}
                            {group : The name of the consumer group}
                            {--id=$ : The ID that will be add to consumer group}';

    protected $description = 'Declare a stream group';

    public function handle(): void
    {
        if (!$this->hasArgument('group') || !$this->hasArgument('key')) {
            echo "Group and Key params cannot be null.";
            return;
        }

        $result = Redis::executeRaw([
            'XGROUP',
            'CREATE',
            $this->argument('key'),
            $this->argument('group'),
            $this->option('id'),
            'MKSTREAM',
        ]);

        if (strtolower($result) != 'ok') {
            throw new \Exception($result);
        }

        echo "Group {$this->argument('group')} successfuly created in {$this->argument('key')}.";
    }
}

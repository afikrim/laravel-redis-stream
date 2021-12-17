<?php

namespace App\Console\Commands\Stream;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class DelCommand extends Command
{
    protected $signature = 'stream:del
                            {key : Specify stream key}
                            {id* : ID of objects that will be destroy}';

    protected $description = 'Destroy an object from the stream';

    public function handle(): void
    {
        if (!$this->hasArgument('key') || !$this->hasArgument('id')) {
            echo "Key and IDs params cannot be null.";
            return;
        }

        $result = Redis::executeRaw(
            [
                'XDEL',
                $this->argument('key'),
                ...$this->argument('id'),
            ]
        );

        if (!preg_match('/^\d+$/', $result)) {
            throw new \Exception($result);
        }

        $ids = implode(', ', $this->argument('id'));

        echo "Successfully delete objects from {$this->argument('key')} with IDs: {$ids}.";
    }
}

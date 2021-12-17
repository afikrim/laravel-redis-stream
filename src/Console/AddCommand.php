<?php

namespace App\Console\Commands\Stream;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class AddCommand extends Command
{
    protected $signature = 'stream:add
                            {key : Specify stream key}
                            {value* : Field and its value, Format: "field:value"}
                            {--id : The ID of the new object}';

    protected $description = 'Declare an object to the stream';

    public function handle(): void
    {
        if (!$this->hasArgument('key') || !$this->hasArgument('value')) {
            echo "Key and Value params cannot be null.";
            return;
        }

        $id = $this->option('id') != '' ? $this->option('id') : '*';
        $values = collect($this->argument('value'))
            ->reduce(function ($prev, $value) {
                [$field, $value] = explode(':', $value);

                return array_merge($prev, [$field, $value]);
            }, []);

        $result = Redis::executeRaw(
            [
                'XADD',
                $this->argument('key'),
                $id,
                ...$values,
            ]
        );

        echo "Successfully add new object to {$this->argument('key')} with ID: {$result}.";
    }
}

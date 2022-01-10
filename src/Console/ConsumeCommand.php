<?php

namespace Afikrim\LaravelRedisStream\Console;

use Afikrim\LaravelRedisStream\TransporterServer;
use Illuminate\Console\Command;

class ConsumeCommand extends Command
{
    protected $signature = 'stream:consume
                            {--group= : Specified stream group}
                            {--consumer= : Specified stream group}
                            {--mkstream=false : Make stream of the group}
                            {--count=5 : A number of event that will retrieve}
                            {--block=2000 : Blocking timeout of reading command in milis}
                            {--rest=3 : Delay between each read in seconds}';

    protected $description = 'Destroy an object from the stream';

    public function handle()
    {
        while (true) {
            $this->listen();

            if (config('app.env') === 'testing') {
                break;
            }

            $this->rest();
        }
    }

    protected function listen()
    {
        $options = [];
        if ($this->hasOption('group')) {
            $options['group'] = $this->option('group');
        }
        if ($this->hasOption('consumer')) {
            $options['consumer'] = $this->option('consumer');
        }
        if ($this->hasOption('count')) {
            $options['count'] = $this->option('count');
        }
        if ($this->hasOption('block')) {
            $options['block'] = $this->option('block');
        }
        if ($this->laravel->config->get('redis.stream.prefix')) {
            $options['prefix'] = $this->laravel->config->get('redis.stream.prefix');
        }

        $server = new TransporterServer($options);
        $server->addHandler('mystream', function ($result) {return $result;});
        $server->addHandler('mystream2', function ($result) {return $result;});
        $server->listen();
    }

    private function rest()
    {
        sleep($this->option('rest'));
    }
}

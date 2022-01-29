<?php

namespace Afikrim\LaravelRedisStream\Tests\Functional;

use Afikrim\LaravelRedisStream\ClientProxy;
use Afikrim\LaravelRedisStream\Data\XGROUPOptions;
use Afikrim\LaravelRedisStream\RedisStream;
use Afikrim\LaravelRedisStream\Tests\Helper;
use Afikrim\LaravelRedisStream\Tests\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

class ConsoleCommandTest extends BaseTestCase
{
    public function testDeclareGroup()
    {
        // delete group first
        try {
            RedisStream::xgroup(
                XGROUPOptions::OPTION_DESTROY,
                'mystream',
                'mygroup',
            );
        } catch (\Exception$e) {}

        Artisan::call('stream:declare-group', [
            'key' => 'mystream',
            'group' => 'mygroup',
            '--mkstream' => true,
        ]);

        $infos = Helper::xinfo([
            'XINFO',
            'GROUPS',
            config('database.redis.stream.prefix') . 'mystream',
        ]);
        $info = $infos->filter(function ($info) {return $info['name'] === config('database.redis.stream.prefix') . 'mygroup';})->first();

        $this->assertNotNull($info, "Group not created");
    }

    public function testDestroyGroup()
    {
        // delete and create a new group first
        try {
            RedisStream::xgroup(
                XGROUPOptions::OPTION_DESTROY,
                'mystream',
                'mygroup',
            );
            RedisStream::xgroup(
                XGROUPOptions::OPTION_CREATE,
                'mystream',
                'mygroup',
                true,
                ['$']
            );
        } catch (\Exception$e) {}

        Artisan::call('stream:destroy-group', [
            'key' => 'mystream',
            'group' => 'mygroup',
        ]);

        $infos = Helper::xinfo([
            'XINFO',
            'GROUPS',
            config('database.redis.stream.prefix') . 'mystream',
        ]);
        $info = $infos->filter(function ($info) {return $info['name'] === config('database.redis.stream.prefix') . 'mygroup';})->first();

        $this->assertEquals(null, $info, "Group destroyed");
    }

    public function testConsume()
    {
        // delete and create a new group first
        try {
            RedisStream::xgroup(
                XGROUPOptions::OPTION_DESTROY,
                'mystream',
                'mygroup',
            );
            RedisStream::xgroup(
                XGROUPOptions::OPTION_CREATE,
                'mystream',
                'mygroup',
                true,
                ['$']
            );

            // populate stream
            $this->populateStream();
        } catch (\Exception$e) {}

        Artisan::call('stream:consume', [
            '--group' => 'mygroup',
            '--count' => 10,
            '--rest' => 0,
        ]);

        $infos = Helper::xinfo([
            'XINFO',
            'GROUPS',
            config('database.redis.stream.prefix') . 'mystream',
        ]);
        $info = $infos->filter(function ($info) {return $info['name'] === config('database.redis.stream.prefix') . 'mygroup';})->first();

        $this->assertEquals(0, $info['pending'], "There is still pending event");
    }

    /** Not part of console commands */
    // public function testClient()
    // {
    //     exec("/usr/bin/php " . __DIR__ . "/../../artisan stream:consume --group=mygroup --count=1 --rest=0 --block=0");
    //     $results = ClientProxy::init([
    //         'group' => 'mygroup',
    //     ])
    //         ->publish('mystream2', [
    //             'name' => 'Aziz',
    //             'email' => "afikrim10@gmail.com",
    //         ])
    //         ->subscribe('mystream2');

    //     // $this->assertArrayHasKey('response', $results, "Array doesn't have 'response' key");
    // }

    private function populateStream()
    {
        for ($i = 0; $i < 10; $i += 1) {
            ClientProxy::init([
                'group' => 'mygroup',
            ])
                ->dispatch('mystream', [
                    'name' => 'Aziz',
                    'email' => "afikrim1{$i}@gmail.com",
                ]);
        }
    }
}

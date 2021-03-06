# Changelog

All notable changes to this project will be documented in this file.

## ![1.1.4 (2022-01-13)](https://github.com/afikrim/laravel-redis-stream/compare/1.1.3...1.1.4)

-

## ![1.1.3 (2022-01-12)](https://github.com/afikrim/laravel-redis-stream/compare/1.1.2...1.1.3)

- Decode json to array before deserializing

## ![1.1.2 (2022-01-12)](https://github.com/afikrim/laravel-redis-stream/compare/1.1.1...1.1.2)

- Remove `is_dispossed` from deserializer
- Modify handle default value for options

## ![1.1.1 (2022-01-12)](https://github.com/afikrim/laravel-redis-stream/compare/1.1.0...1.1.1)

- Add predis to require
- Fix undefined function 'now'

## ![1.1.0 (2022-01-11)](https://github.com/afikrim/laravel-redis-stream/compare/1.0.4...1.1.0)

- Remove support for array or object
- Add transporter strategy
- Add client proxy
- Add serializer and deserializer
- Add stream config

## ![1.0.4 (2021-12-28)](https://github.com/afikrim/laravel-redis-stream/compare/1.0.3...1.0.4)

- Add support for array
- Add support for object

## ![1.0.3 (2021-12-27)](https://github.com/afikrim/laravel-redis-stream/compare/1.0.2...1.0.3)

- Fix group and consumer option in command

## ![1.0.2 (2021-12-18)](https://github.com/afikrim/laravel-redis-stream/compare/1.0.1...1.0.2)

- Fix typos

## ![1.0.1 (2021-12-18)](https://github.com/afikrim/laravel-redis-stream/compare/1.0.0...1.0.1)

- Modify functions in `RedisStream` to static
- Update documentation in `README`
- Add test cases for console commands
- Fix Redis Execute result handler in `RedisStream`

## ![1.0.0 (2021-12-18)](https://github.com/afikrim/laravel-redis-stream/tree/1.0.0)

- Add `stream:declare-group` artisan command
- Add `stream:destroy-group` artisan command
- Add `stream:consume` artisan command
- Add `LaravelRedisStreamServiceProvider` service provider
- Add `RedisStream` helper class

<?php

declare(strict_types=1);

use GaaraHyperf\RateLimiter\SlidingWindowRateLimiter;
use Hyperf\Redis\Redis;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('returns accepted result from redis eval response', function (): void {
    /** @var MockInterface&Redis $redis */
    $redis = Mockery::mock(Redis::class);

    $redis->shouldReceive('eval')
        ->once()
        ->with(
            Mockery::type('string'),
            Mockery::on(function (array $args): bool {
                return count($args) === 6
                    && $args[0] === 'gaara:test:user-1'
                    && is_float($args[1])
                    && is_float($args[2])
                    && $args[3] === 5
                    && $args[4] === 60
                    && is_string($args[5]);
            }),
            1
        )
        ->andReturn([1, 3, 0]);

    $limiter = new SlidingWindowRateLimiter($redis, 'gaara:test', 60, 5);
    $result = $limiter->attempt('user-1');

    expect($result->isAccepted())->toBeTrue()
        ->and($result->getRemaining())->toBe(3)
        ->and($result->getRetryAfter())->toBe(0);
});

it('returns rejected result and retry after from redis eval response', function (): void {
    /** @var MockInterface&Redis $redis */
    $redis = Mockery::mock(Redis::class);

    $redis->shouldReceive('eval')->once()->andReturn([0, 0, 12]);

    $limiter = new SlidingWindowRateLimiter($redis, 'gaara:test', 60, 5);
    $result = $limiter->attempt('user-2');

    expect($result->isAccepted())->toBeFalse()
        ->and($result->getRemaining())->toBe(0)
        ->and($result->getRetryAfter())->toBe(12);
});

it('deletes redis key on reset', function (): void {
    /** @var MockInterface&Redis $redis */
    $redis = Mockery::mock(Redis::class);

    $redis->shouldReceive('del')->once()->with('gaara:test:user-3');

    $limiter = new SlidingWindowRateLimiter($redis, 'gaara:test', 60, 5);
    $limiter->reset('user-3');

    expect(true)->toBeTrue();
});

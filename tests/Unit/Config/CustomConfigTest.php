<?php

declare(strict_types=1);

use GaaraHyperf\Config\CustomConfig;

it('creates custom config from class string', function (): void {
    $config = CustomConfig::from(TestCustomConfigHandler::class);

    expect($config->class())->toBe(TestCustomConfigHandler::class)
        ->and($config->params())->toBe([]);
});

it('creates custom config from array and maps params to camel case', function (): void {
    $config = CustomConfig::from([
        'class' => TestCustomConfigHandler::class,
        'params' => [
            'token_manager' => 'default',
            'max_ttl' => 3600,
        ],
    ]);

    expect($config->class())->toBe(TestCustomConfigHandler::class)
        ->and($config->params())->toBe([
            'tokenManager' => 'default',
            'maxTtl' => 3600,
        ]);
});

it('throws when class option is missing', function (): void {
    expect(fn () => CustomConfig::from(['params' => []]))
        ->toThrow(InvalidArgumentException::class, 'class is required');
});

class TestCustomConfigHandler
{
}

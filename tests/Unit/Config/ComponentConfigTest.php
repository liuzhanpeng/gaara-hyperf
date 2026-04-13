<?php

declare(strict_types=1);

use GaaraHyperf\Config\ComponentConfig;

it('creates component config from array and strips type from options', function (): void {
    $config = ComponentConfig::from([
        'type' => 'session',
        'prefix' => 'web',
    ]);

    expect($config->type())->toBe('session')
        ->and($config->options())->toBe(['prefix' => 'web']);
});

it('uses default type when type is absent', function (): void {
    $config = ComponentConfig::from(['pattern' => '/api/*'], 'default');

    expect($config->type())->toBe('default')
        ->and($config->options())->toBe(['pattern' => '/api/*']);
});

it('throws when type and default are both missing', function (): void {
    expect(fn () => ComponentConfig::from(['pattern' => '/api/*']))
        ->toThrow(InvalidArgumentException::class, 'type is required for component config');
});

<?php

declare(strict_types=1);

use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\GuardConfig;

it('creates config from valid array', function (): void {
    $config = Config::from(validConfigTestGaaraConfig());

    $guards = $config->guardConfigCollection();

    expect($guards)->toHaveKey('api')
        ->and($guards['api'])->toBeInstanceOf(GuardConfig::class);
});

it('throws exception when guards config is missing', function (): void {
    expect(fn () => Config::from([]))->toThrow(InvalidArgumentException::class, '`guards` config is required');
});

it('throws exception when guards config is empty', function (): void {
    expect(fn () => Config::from(['guards' => []]))->toThrow(InvalidArgumentException::class, '`guards` config is required');
});

it('returns service config by name and empty array for unknown service', function (): void {
    $config = Config::from(validConfigTestGaaraConfig());

    expect($config->serviceConfig('password_hashers'))->toBe([
        'default' => [
            'type' => 'default',
            'algo' => PASSWORD_DEFAULT,
        ],
    ]);

    expect($config->serviceConfig('unknown_service'))->toBe([]);
});

function validConfigTestGaaraConfig(): array
{
    return [
        'guards' => [
            'api' => [
                'matcher' => [
                    'patterns' => ['/api/*'],
                ],
                'user_provider' => [
                    'type' => 'memory',
                    'users' => [
                        [
                            'id' => 'u1',
                            'password' => 'secret',
                            'roles' => ['ROLE_USER'],
                        ],
                    ],
                ],
                'authenticators' => [
                    'api_key' => [
                        'api_key_field' => 'X-API-KEY',
                    ],
                ],
            ],
        ],
        'services' => [
            'password_hashers' => [
                'default' => [
                    'type' => 'default',
                    'algo' => PASSWORD_DEFAULT,
                ],
            ],
        ],
    ];
}

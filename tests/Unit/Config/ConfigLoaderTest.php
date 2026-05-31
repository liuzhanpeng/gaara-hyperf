<?php

declare(strict_types=1);

use GaaraHyperf\Config\Config;
use GaaraHyperf\Config\ConfigLoader;
use Hyperf\Contract\ConfigInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('loads gaara config and transforms it into Config object', function (): void {
    /** @var ConfigInterface&MockInterface $hyperfConfig */
    $hyperfConfig = Mockery::mock(ConfigInterface::class);
    $hyperfConfig->shouldReceive('get')->once()->with('gaara')->andReturn(validConfigLoaderTestGaaraConfig());

    $loader = new ConfigLoader($hyperfConfig);

    expect($loader->load())->toBeInstanceOf(Config::class);
});

it('throws exception when gaara guards config is invalid', function (): void {
    /** @var ConfigInterface&MockInterface $hyperfConfig */
    $hyperfConfig = Mockery::mock(ConfigInterface::class);
    $hyperfConfig->shouldReceive('get')->once()->with('gaara')->andReturn(['guards' => []]);

    $loader = new ConfigLoader($hyperfConfig);

    expect(fn () => $loader->load())->toThrow(InvalidArgumentException::class, '`guards` config is required');
});

function validConfigLoaderTestGaaraConfig(): array
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
    ];
}

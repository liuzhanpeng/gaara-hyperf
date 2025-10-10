<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Lzpeng\HyperfAuthGuard\Config\ConfigLoader;
use Lzpeng\HyperfAuthGuard\Config\ConfigLoaderInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ConfigLoaderInterface::class => ConfigLoader::class,
            ],
            'listeners' => [
                AuthInitListener::class,
            ],
            'commands' => [],
            'publish' => [
                [
                    'id' => 'hyperf-auth-guard',
                    'description' => 'The config for hyperf-auth-guard.',
                    'source' => __DIR__ . '/../publish/auth.php',
                    'destination' => BASE_PATH . '/config/autoload/auth.php',
                ]
            ]
        ];
    }
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [],
            'listeners' => [],
            'middlewares' => [],
            'commands' => [],
            'publish' => [
                [
                    'id' => 'auth',
                    'description' => 'The config for authentication.',
                    'source' => __DIR__ . '/../publish/auth.php',
                    'destination' => BASE_PATH . '/config/autoload/auth.php',
                ]
            ]
        ];
    }
}

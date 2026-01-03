<?php

declare(strict_types=1);

namespace GaaraHyperf;

use GaaraHyperf\Config\ConfigLoader;
use GaaraHyperf\Config\ConfigLoaderInterface;

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
                    'id' => 'gaara-hyperf',
                    'description' => 'The config for gaara-hyperf.',
                    'source' => __DIR__ . '/../publish/auth.php',
                    'destination' => BASE_PATH . '/config/autoload/gaara.php',
                ]
            ]
        ];
    }
}

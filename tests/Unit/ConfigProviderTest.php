<?php

declare(strict_types=1);

use GaaraHyperf\AuthInitListener;
use GaaraHyperf\Config\ConfigLoader;
use GaaraHyperf\Config\ConfigLoaderInterface;
use GaaraHyperf\ConfigProvider;

if (! defined('BASE_PATH')) {
    define('BASE_PATH', '/tmp');
}

it('returns expected dependencies/listeners/commands/publish config', function (): void {
    $provider = new ConfigProvider();

    $config = $provider();

    expect($config['dependencies'][ConfigLoaderInterface::class])->toBe(ConfigLoader::class)
        ->and($config['listeners'])->toContain(AuthInitListener::class)
        ->and($config['commands'])->toBeArray()
        ->and($config['publish'])->toHaveCount(1)
        ->and($config['publish'][0]['id'])->toBe('gaara-hyperf')
        ->and($config['publish'][0]['description'])->toBe('The config for gaara-hyperf.')
        ->and($config['publish'][0]['source'])->toContain('/publish/auth.php')
        ->and($config['publish'][0]['destination'])->toContain('/config/autoload/gaara.php');
});

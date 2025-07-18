<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Hyperf\Contract\ConfigInterface;

/**
 * 内置的配置加载器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class ConfigLoader implements ConfigLoaderInterface
{
    public function __construct(private ConfigInterface $config) {}

    public function load(): Config
    {
        $config = $this->config->get('auth');

        return Config::from($config);
    }
}

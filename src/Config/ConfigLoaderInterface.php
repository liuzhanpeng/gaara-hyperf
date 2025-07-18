<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 认证配置加载器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface ConfigLoaderInterface
{
    /**
     * 加载配置
     *
     * @return Config
     */
    public function load(): Config;
}

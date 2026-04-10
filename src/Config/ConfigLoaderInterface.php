<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

/**
 * 认证配置加载器接口.
 */
interface ConfigLoaderInterface
{
    /**
     * 加载配置.
     */
    public function load(): Config;
}

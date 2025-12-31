<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

interface IPWhiteListProviderInterface
{
    /**
     * 获取 IP 白名单列表
     * @return string[]
     */
    public function getWhiteList(): array;
}

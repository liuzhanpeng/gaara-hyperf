<?php

declare(strict_types=1);

namespace GaaraHyperf\IPWhiteListChecker;

/**
 * IP白名单提供器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface IPWhiteListProviderInterface
{
    /**
     * 获取 IP 白名单列表
     * @return string[]
     */
    public function getWhiteList(): array;
}

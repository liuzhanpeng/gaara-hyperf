<?php

declare(strict_types=1);

namespace GaaraHyperf\IPWhiteListChecker;

/**
 * IP白名单检查器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface IPWhiteListCheckerInterface
{
    /**
     * 检查ip是否在白名单内
     *
     * @param string $ip
     * @param array $whiteList
     * @return boolean
     */
    public function isAllowed(string $ip, array $whiteList): bool;
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\IPResolver;

use Psr\Http\Message\ServerRequestInterface;

/**
 * IP地址解析器接口
 */
interface IPResolverInterface
{
    /**
     * 从请求中解析出ip地址
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function resolve(ServerRequestInterface $request): string;
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 路径前缀匹配器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PrefixRequestMatcher implements RequestMatcherInterface
{
    /**
     * @param string $prefix
     */
    public function __construct(private string $prefix) {}

    /**
     * @inheritDoc
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function matches(ServerRequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), $this->prefix);
    }
}

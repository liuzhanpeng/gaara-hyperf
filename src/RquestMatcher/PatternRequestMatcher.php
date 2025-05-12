<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 正则模式请求匹配器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PatternRequestMatcher implements RequestMatcherInterface
{
    /**
     * @param string $pattern
     */
    public function __construct(private string $pattern) {}

    /**
     * @inheritDoc
     */
    public function matches(ServerRequestInterface $request): bool
    {
        return preg_match('#' . $this->pattern . '#', $request->getUri()->getPath()) === 1;
    }
}

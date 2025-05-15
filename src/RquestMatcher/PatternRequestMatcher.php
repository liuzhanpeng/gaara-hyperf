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
     * @param string $pattern 匹配模式
     * @param string[] $exclusions 排除的path集合(也支持正则模式)
     */
    public function __construct(
        private string $pattern,
        private array $exclusions
    ) {}

    /**
     * @inheritDoc
     */
    public function matches(ServerRequestInterface $request): bool
    {
        foreach ($this->exclusions as $exclusion) {
            if (preg_match('#' . $exclusion . '#', $request->getUri()->getPath()) === 1) {
                return false;
            }
        }

        return preg_match('#' . $this->pattern . '#', $request->getUri()->getPath()) === 1;
    }
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

use Hyperf\HttpServer\Contract\RequestInterface;

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
    public function matches(RequestInterface $request): bool
    {
        foreach ($this->exclusions as $exclusion) {
            if ($request->is($exclusion)) {
                return false;
            }
        }

        return $request->is($this->pattern);
    }
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RquestMatcher;

use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * 路径前缀匹配器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class PrefixRequestMatcher implements RequestMatcherInterface
{
    /**
     * @param string $prefix 路径前缀
     * @param string[] $exclusions 排除的path集合(也支持正则模式)
     */
    public function __construct(
        private string $prefix,
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

        return str_starts_with($request->getUri()->getPath(), $this->prefix);
    }
}

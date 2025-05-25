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
    public function matches(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        foreach ($this->exclusions as $exclusion) {
            if (preg_match('#' . $exclusion . '#', $path) === 1) {
                return false;
            }
        }
        return str_starts_with($path, $this->prefix);
    }
}

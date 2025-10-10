<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RequestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求匹配器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RequestMatcher implements RequestMatcherInterface
{
    /**
     * @param string $pattern 匹配的路径模式
     * @param string|null $logoutPath 注销路径
     * @param array $exclusions 排除的路径模式数组
     */
    public function __construct(
        private string $pattern,
        private ?string $logoutPath,
        private array $exclusions,
    ) {}

    /**
     * @inheritDoc
     */
    public function matchesPattern(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        return $this->matches($path, $this->pattern);
    }

    /**
     * @inheritDoc
     */
    public function matchesLogout(ServerRequestInterface $request): bool
    {
        if (is_null($this->logoutPath)) {
            return false;
        }

        return strcmp($request->getUri()->getPath(), $this->logoutPath) === 0;
    }

    /**
     * @inheritDoc
     */
    public function matchesExcluded(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        foreach ($this->exclusions as $exclusion) {
            if ($this->matches($path, $exclusion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 是否匹配给定的路径模式
     *
     * @param string $path
     * @param string $pattern
     * @return boolean
     */
    private function matches(string $path, string $pattern): bool
    {
        return preg_match('#' . $pattern . '#', $path) === 1;
    }
}

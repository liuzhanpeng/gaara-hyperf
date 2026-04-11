<?php

declare(strict_types=1);

namespace GaaraHyperf\RequestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 默认请求匹配器.
 */
class RequestMatcher implements RequestMatcherInterface
{
    /**
     * @param array|string $pattern 匹配的路径模式
     * @param null|array|string $logoutPath 注销路径，支持字符串、正则数组或 null
     * @param array $exclusions 排除的路径模式数组
     */
    public function __construct(
        private array|string $pattern,
        private array|string|null $logoutPath,
        private array $exclusions,
    ) {
        $this->pattern = is_array($this->pattern) ? $pattern : [$pattern];
        if (is_string($this->logoutPath)) {
            $this->logoutPath = [$this->logoutPath];
        }
    }

    public function matchesPattern(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        foreach ($this->pattern as $pattern) {
            if ($this->matches($path, $method, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function matchesLogout(ServerRequestInterface $request): bool
    {
        if (is_null($this->logoutPath)) {
            return false;
        }

        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        foreach ($this->logoutPath as $logoutPath) {
            if ($this->matches($path, $method, $logoutPath)) {
                return true;
            }
        }

        return false;
    }

    public function matchesExcluded(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        foreach ($this->exclusions as $exclusion) {
            if ($this->matches($path, $method, $exclusion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 是否匹配给定的路径模式.
     *
     * 支持可选的请求方法前缀，格式为 'METHOD path' 或 'METHOD1|METHOD2 path'，例如：
     *   'GET /api/users'
     *   'POST|PUT /api/users/\d+'
     * 不带方法前缀时仅匹配路径，不限制请求方法。
     */
    private function matches(string $path, string $method, string $pattern): bool
    {
        if (str_contains($pattern, ' ')) {
            [$methods, $pathPattern] = explode(' ', $pattern, 2);
            $allowed = array_map('strtoupper', explode('|', $methods));
            if (! in_array(strtoupper($method), $allowed, true)) {
                return false;
            }

            return $this->matchesPath($path, $pathPattern);
        }

        return $this->matchesPath($path, $pattern);
    }

    private function matchesPath(string $path, string $pattern): bool
    {
        if (strpbrk($pattern, '\.^$*+?()[]{}|') !== false) {
            return preg_match('#' . $pattern . '#', $path) === 1;
        }

        return str_starts_with($path, $pattern);
    }
}

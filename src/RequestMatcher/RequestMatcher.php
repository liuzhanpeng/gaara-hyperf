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
     * @var array<int, array{methods: null|array<string, true>, pattern: string, regex: bool, regexPattern: null|string}>
     */
    private array $compiledPatterns;

    /**
     * @var null|array<int, array{methods: null|array<string, true>, pattern: string, regex: bool, regexPattern: null|string}>
     */
    private ?array $compiledLogoutPaths;

    /**
     * @var array<int, array{methods: null|array<string, true>, pattern: string, regex: bool, regexPattern: null|string}>
     */
    private array $compiledExclusions;

    /**
     * @param array|string $pattern 匹配的路径模式
     * @param null|array|string $logoutPath 注销路径，支持字符串、正则数组或 null
     * @param array $exclusions 排除的路径模式数组
     */
    public function __construct(
        array|string $pattern,
        array|string|null $logoutPath,
        array $exclusions,
    ) {
        $this->compiledPatterns = $this->compileRules(is_array($pattern) ? $pattern : [$pattern]);
        $this->compiledLogoutPaths = match (true) {
            $logoutPath === null => null,
            is_array($logoutPath) => $this->compileRules($logoutPath),
            default => $this->compileRules([$logoutPath]),
        };
        $this->compiledExclusions = $this->compileRules($exclusions);
    }

    public function matchesPattern(ServerRequestInterface $request): bool
    {
        return $this->matchesRules(
            path: $request->getUri()->getPath(),
            method: strtoupper($request->getMethod()),
            rules: $this->compiledPatterns,
        );
    }

    public function matchesLogout(ServerRequestInterface $request): bool
    {
        if ($this->compiledLogoutPaths === null) {
            return false;
        }

        return $this->matchesRules(
            path: $request->getUri()->getPath(),
            method: strtoupper($request->getMethod()),
            rules: $this->compiledLogoutPaths,
        );
    }

    public function matchesExcluded(ServerRequestInterface $request): bool
    {
        return $this->matchesRules(
            path: $request->getUri()->getPath(),
            method: strtoupper($request->getMethod()),
            rules: $this->compiledExclusions,
        );
    }

    /**
     * 预编译规则，避免在请求热路径中重复解析字符串。
     *
     * 支持可选的请求方法前缀，格式为 'METHOD path' 或 'METHOD1|METHOD2 path'，例如：
     *   'GET /api/users'
     *   'POST|PUT /api/users/\d+'
     * 不带方法前缀时仅匹配路径，不限制请求方法。
     *
     * @param array<int, string> $rules
     * @return array<int, array{methods: null|array<string, true>, pattern: string, regex: bool, regexPattern: null|string}>
     */
    private function compileRules(array $rules): array
    {
        $compiledRules = [];

        foreach ($rules as $rule) {
            $methods = null;
            $pathPattern = $rule;

            if (str_contains($rule, ' ')) {
                [$methods, $pathPattern] = explode(' ', $rule, 2);
                $methods = array_fill_keys(
                    array_map('strtoupper', explode('|', $methods)),
                    true,
                );
            }

            $isRegex = strpbrk($pathPattern, '\.^$*+?()[]{}|') !== false;

            $compiledRules[] = [
                'methods' => $methods,
                'pattern' => $pathPattern,
                'regex' => $isRegex,
                'regexPattern' => $isRegex ? '#' . $pathPattern . '#' : null,
            ];
        }

        return $compiledRules;
    }

    /**
     * @param array<int, array{methods: null|array<string, true>, pattern: string, regex: bool, regexPattern: null|string}> $rules
     */
    private function matchesRules(string $path, string $method, array $rules): bool
    {
        foreach ($rules as $rule) {
            if ($this->matchesRule($path, $method, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{methods: null|array<string, true>, pattern: string, regex: bool, regexPattern: null|string} $rule
     */
    private function matchesRule(string $path, string $method, array $rule): bool
    {
        $allowedMethods = $rule['methods'];
        if ($allowedMethods !== null && ! isset($allowedMethods[$method])) {
            return false;
        }

        if ($rule['regex']) {
            return preg_match($rule['regexPattern'], $path) === 1;
        }

        return str_starts_with($path, $rule['pattern']);
    }
}

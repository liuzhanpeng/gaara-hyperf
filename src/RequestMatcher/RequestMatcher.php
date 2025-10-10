<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\RequestMatcher;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求匹配器
 * 
 * 增加LRU缓存，避免每次都进行正则匹配
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class RequestMatcher implements RequestMatcherInterface
{
    /**
     * 缓存匹配结果
     *
     * @var array
     */
    private array $caches = [];

    /**
     * @param string $pattern 匹配的路径模式
     * @param string|null $logoutPath 注销路径
     * @param array $exclusions 排除的路径模式数组
     * @param int $cacheSize  缓存大小，0表示不缓存
     */
    public function __construct(
        private string $pattern,
        private ?string $logoutPath,
        private array $exclusions,
        private int $cacheSize,
    ) {}

    /**
     * @inheritDoc
     */
    public function matchesPattern(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        return $this->matchesWithCache($path, $this->pattern);
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
            if ($this->matchesWithCache($path, $exclusion)) {
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

    /**
     * 带缓存的匹配方法
     *
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    private function matchesWithCache(string $path, string $pattern): bool
    {
        // 如果缓存大小为0，直接匹配不使用缓存
        if ($this->cacheSize === 0) {
            return $this->matches($path, $pattern);
        }

        $cacheKey = $path . '::' . $pattern;

        // 缓存命中，移动到最前面（LRU）
        if (array_key_exists($cacheKey, $this->caches)) {
            $result = $this->caches[$cacheKey];
            unset($this->caches[$cacheKey]);
            $this->caches[$cacheKey] = $result;
            return $result;
        }

        $result = $this->matches($path, $pattern);

        $this->addCache($cacheKey, $result);

        return $result;
    }

    /**
     * 添加结果到LRU缓存
     *
     * @param string $key
     * @param bool $value
     * @return void
     */
    private function addCache(string $key, bool $value): void
    {
        // 如果缓存已满，删除最久未使用的项（第一个）
        if (count($this->caches) >= $this->cacheSize) {
            $oldestKey = array_key_first($this->caches);
            if ($oldestKey !== null) {
                unset($this->cache[$oldestKey]);
            }
        }

        // 添加新项到缓存末尾
        $this->caches[$key] = $value;
    }
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\LoginRateLimiter;

use Hyperf\Redis\Redis;

/**
 * 固定窗口登录限流器
 * 
 * 依赖于Redis实现，使用固定窗口算法
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class FixedWindowLoginRateLimiter implements LoginRateLimiterInterface
{
    /**
     * @param Redis $redis
     * @param integer $interval 时间窗口，单位秒
     * @param integer $limit 最大请求数
     * @param string $prefix 缓存键前缀
     */
    public function __construct(
        private Redis $redis,
        private int $interval,
        private int $limit,
        private string $prefix
    ) {}

    /**
     * 尝试请求并返回限流结果
     *
     * @param string $key 限流key
     * @return LimitResult
     */
    public function attempt(string $key): LimitResult
    {
        $redisKey = $this->getKey($key);

        // 使用Lua脚本保证原子性
        $script = '
            local key = KEYS[1]
            local limit = tonumber(ARGV[1])
            local interval = tonumber(ARGV[2])

            -- 增加计数器
            local current_count = redis.call("INCR", key)

            -- 如果是第一次请求，设置过期时间
            if current_count == 1 then
                redis.call("EXPIRE", key, interval)
            end

            if current_count <= limit then
                return {1, limit - current_count, 0}
            else
                -- 获取剩余过期时间
                local ttl = redis.call("TTL", key)
                return {0, 0, ttl > 0 and ttl or 0}
            end
        ';

        $result = $this->redis->eval(
            $script,
            [$redisKey, $this->limit, $this->interval],
            1
        );

        return new LimitResult(
            $result[0] === 1,
            (int) $result[1],
            (int) $result[2]
        );
    }

    /**
     * 检查限流状态但不消费次数
     *
     * @param string $key 限流key
     * @return LimitResult
     */
    public function check(string $key): LimitResult
    {
        $redisKey = $this->getKey($key);

        // 使用Lua脚本检查状态但不消费
        $script = '
            local key = KEYS[1]
            local limit = tonumber(ARGV[1])

            -- 获取当前计数
            local current_count = redis.call("GET", key)
            if not current_count then
                current_count = 0
            end

            current_count = tonumber(current_count)

            if current_count < limit then
                return {1, limit - current_count, 0}
            else
                -- 获取剩余过期时间
                local ttl = redis.call("TTL", key)
                return {0, 0, ttl > 0 and ttl or 0}
            end
        ';

        $result = $this->redis->eval(
            $script,
            [$redisKey, $this->limit],
            1
        );

        return new LimitResult(
            $result[0] === 1,
            (int) $result[1],
            (int) $result[2]
        );
    }

    /**
     * 重置指定key的限流记录
     *
     * @param string $key 限流key
     * @return void
     */
    public function reset(string $key): void
    {
        $redisKey = $this->getKey($key);
        $this->redis->del($redisKey);
    }

    /**
     * @param string $key
     * @return string
     */
    private function getKey(string $key): string
    {
        return sprintf('%s:%s', $this->prefix, $key);
    }
}

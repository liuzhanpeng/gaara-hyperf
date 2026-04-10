<?php

declare(strict_types=1);

namespace GaaraHyperf\RateLimiter;

use Hyperf\Redis\Redis;

/**
 * 滑动窗口限流器.
 *
 * 依赖于Redis有序集合实现
 */
class SlidingWindowRateLimiter implements RateLimiterInterface
{
    /**
     * @param int $interval 时间窗口，单位秒
     * @param int $limit 最大请求数
     * @param string $prefix 缓存键前缀
     */
    public function __construct(
        private Redis $redis,
        private int $interval,
        private int $limit,
        private string $prefix
    ) {
    }

    /**
     * 尝试请求并返回限流结果.
     *
     * @param string $key 限流key
     */
    public function attempt(string $key): LimitResult
    {
        $now = microtime(true);
        $windowStart = $now - $this->interval;
        $redisKey = $this->getKey($key);

        // 使用Lua脚本保证原子性
        $script = '
            local key = KEYS[1]
            local window_start = tonumber(ARGV[1])
            local now = tonumber(ARGV[2])
            local limit = tonumber(ARGV[3])
            local interval = tonumber(ARGV[4])
            local member = ARGV[5]

            -- 删除窗口外的记录
            redis.call("ZREMRANGEBYSCORE", key, 0, window_start)

            -- 获取当前窗口内的请求数
            local current_count = redis.call("ZCARD", key)

            if current_count < limit then
                -- 使用 "时间戳:唯一标识" 作为 member，避免并发时同一微秒覆盖已有记录
                redis.call("ZADD", key, now, member)
                redis.call("EXPIRE", key, interval)
                return {1, limit - current_count - 1, 0}
            else
                -- 取出窗口内最早的记录，计算其滑出窗口所需秒数
                local oldest = redis.call("ZRANGE", key, 0, 0, "WITHSCORES")
                local retry_after = 0
                if oldest[2] then
                    retry_after = math.ceil(tonumber(oldest[2]) + interval - now)
                    if retry_after < 0 then retry_after = 0 end
                end
                return {0, 0, retry_after}
            end
        ';

        $member = $now . ':' . bin2hex(random_bytes(4));

        $result = $this->redis->eval(
            $script,
            [$redisKey, $windowStart, $now, $this->limit, $this->interval, $member],
            1
        );

        return new LimitResult(
            $result[0] === 1,
            (int) $result[1],
            (int) $result[2]
        );
    }

    /**
     * 重置指定key的限流记录.
     *
     * @param string $key 限流key
     * @return bool
     */
    public function reset(string $key): void
    {
        $redisKey = $this->getKey($key);
        $this->redis->del($redisKey);
    }

    private function getKey(string $key): string
    {
        return sprintf('%s:%s', $this->prefix, $key);
    }
}

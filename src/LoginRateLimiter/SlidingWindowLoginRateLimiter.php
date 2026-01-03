<?php

declare(strict_types=1);

namespace GaaraHyperf\LoginRateLimiter;

use Hyperf\Redis\Redis;

/**
 * 登录限流器
 * 
 * 依赖于Redis有序集合实现, 使用滑动窗口算法
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class SlidingWindowLoginRateLimiter implements LoginRateLimiterInterface
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

            -- 删除窗口外的记录
            redis.call("ZREMRANGEBYSCORE", key, 0, window_start)

            -- 获取当前窗口内的请求数
            local current_count = redis.call("ZCARD", key)

            if current_count < limit then
                -- 直接使用时间戳作为member
                redis.call("ZADD", key, now, tostring(now))
                redis.call("EXPIRE", key, interval)
                return {1, limit - current_count - 1, 0}
            else
                -- 获取最早的请求时间
                local earliest = redis.call("ZRANGE", key, 0, 0, "WITHSCORES")
                local retry_after = 0
                if #earliest > 0 then
                    local earliest_time = tonumber(earliest[2])
                    local reset_time = earliest_time + interval
                    retry_after = math.max(0, math.ceil(reset_time - now))
                end
                return {0, 0, retry_after}
            end
        ';

        $result = $this->redis->eval(
            $script,
            [$redisKey, $windowStart, $now, $this->limit, $this->interval],
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
        $now = microtime(true);
        $windowStart = $now - $this->interval;
        $redisKey = $this->getKey($key);

        // 使用Lua脚本保证原子性，但不消费次数
        $script = '
            local key = KEYS[1]
            local window_start = tonumber(ARGV[1])
            local now = tonumber(ARGV[2])
            local limit = tonumber(ARGV[3])
            local interval = tonumber(ARGV[4])

            -- 删除窗口外的记录
            redis.call("ZREMRANGEBYSCORE", key, 0, window_start)

            -- 获取当前窗口内的请求数
            local current_count = redis.call("ZCARD", key)

            if current_count < limit then
                return {1, limit - current_count, 0}
            else
                -- 获取最早的请求时间
                local earliest = redis.call("ZRANGE", key, 0, 0, "WITHSCORES")
                local retry_after = 0
                if #earliest > 0 then
                    local earliest_time = tonumber(earliest[2])
                    local reset_time = earliest_time + interval
                    retry_after = math.max(0, math.ceil(reset_time - now))
                end
                return {0, 0, retry_after}
            end
        ';

        $result = $this->redis->eval(
            $script,
            [$redisKey, $windowStart, $now, $this->limit, $this->interval],
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
     * @return bool
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

<?php

declare(strict_types=1);

namespace GaaraHyperf\RateLimiter;

use Hyperf\Redis\Redis;

/**
 * 令牌桶限流器
 *
 * 依赖于Redis HASH 实现
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class TokenBucketRateLimiter implements RateLimiterInterface
{
    /**
     * @param Redis $redis
     * @param int $limit 桶的容量 (最大令牌数)
     * @param float $rate 每秒生成的令牌数
     * @param string $prefix 缓存键前缀
     */
    public function __construct(
        private Redis $redis,
        private int $limit,
        private float $rate,
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
        $redisKey = $this->getKey($key);

        // 使用Lua脚本保证原子性
        $script = '
            local key = KEYS[1]
            local limit = tonumber(ARGV[1])
            local rate = tonumber(ARGV[2])
            local now = tonumber(ARGV[3])
            local cost = 1 -- 每次请求消耗的令牌数

            local bucket = redis.call("HMGET", key, "tokens", "last_refill_time")
            local tokens = tonumber(bucket[1])
            local last_refill_time = tonumber(bucket[2])

            if tokens == nil then
                -- 首次请求，桶是满的
                tokens = limit
                last_refill_time = now
            else
                -- 计算自上次填充以来经过的时间，并生成新的令牌
                local time_passed = now - last_refill_time
                local new_tokens = time_passed * rate
                if new_tokens > 0 then
                    tokens = math.min(limit, tokens + new_tokens)
                    last_refill_time = now
                end
            end

            if tokens >= cost then
                -- 令牌足够，消耗令牌
                tokens = tokens - cost
                redis.call("HMSET", key, "tokens", tokens, "last_refill_time", last_refill_time)
                -- 设置一个合理的过期时间，防止key永不过期
                -- 过期时间为从空桶到满桶所需的时间，加上一个缓冲
                local expire_time = math.ceil(limit / rate) + 60
                redis.call("EXPIRE", key, expire_time)
                return {1, math.floor(tokens), 0}
            else
                -- 令牌不足
                redis.call("HMSET", key, "tokens", tokens, "last_refill_time", last_refill_time)
                local expire_time = math.ceil(limit / rate) + 60
                redis.call("EXPIRE", key, expire_time)
                
                -- 计算需要等待多久才能获得一个令牌
                local retry_after = (cost - tokens) / rate
                return {0, 0, math.ceil(retry_after)}
            end
        ';

        $result = $this->redis->eval(
            $script,
            [$redisKey, $this->limit, $this->rate, $now],
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

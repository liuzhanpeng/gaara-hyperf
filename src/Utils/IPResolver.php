<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Utils;

use Psr\Http\Message\ServerRequestInterface;

/**
 * IP地址解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IpResolver
{
    /**
     * 获取请求的真实IP地址，支持代理转发
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function resolve(ServerRequestInterface $request): string
    {
        // 优先取 X-Forwarded-For，可能有多个IP，取第一个
        $forwardedFor = $request->getHeaderLine('x-forwarded-for');
        if (!empty($forwardedFor)) {
            $ips = array_map('trim', explode(',', $forwardedFor));
            if (!empty($ips[0])) {
                return $ips[0];
            }
        }

        // 兼容 X-Real-IP
        $realIp = $request->getHeaderLine('x-real-ip');
        if (!empty($realIp)) {
            return $realIp;
        }

        // 兼容 Hyperf 的 ip 属性
        $ip = $request->getAttribute('ip');
        if (!empty($ip)) {
            return $ip;
        }

        // 最后取 server param
        return $request->getServerParams()['remote_addr'] ?? '';
    }
}

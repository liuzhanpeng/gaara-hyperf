<?php

declare(strict_types=1);

namespace GaaraHyperf\IPResolver;

use Psr\Http\Message\ServerRequestInterface;

/**
 * IP地址解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IPResolver implements IPResolverInterface
{
    /**
     * @param array $headers 自定义要检查的IP头, 按定义顺序检查
     */
    public function __construct(private array $headers = [
        'X-Forwarded-For',
        'X-Real-IP',
        'CF-Connecting-IP'
    ]) {}

    /**
     * 获取请求的真实IP地址，支持代理转发
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public function resolve(ServerRequestInterface $request): string
    {
        foreach ($this->headers as $header) {
            $headerLine = $request->getHeaderLine($header);
            if (empty($headerLine)) {
                continue;
            }

            // 处理可能存在的逗号分隔列表（如 X-Forwarded-For: client, proxy1, proxy2）
            $ips = explode(',', $headerLine);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($this->isValidIp($ip)) {
                    return $ip;
                }
            }
        }

        // 3. Hyperf Attribute 兜底
        $ip = (string) $request->getAttribute('ip');
        if ($this->isValidIp($ip)) {
            return $ip;
        }

        // 4. Remote Addr 兜底
        $ip = $request->getServerParams()['remote_addr'] ?? '';

        return $this->isValidIp($ip) ? $ip : '';
    }

    /**
     * 是否有效ip
     *
     * @param mixed $ip
     * @return boolean
     */
    private function isValidIp(mixed $ip): bool
    {
        return !empty($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}

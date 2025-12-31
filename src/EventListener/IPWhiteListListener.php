<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\EventListener;

use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Exception\IPNotInWhiteListException;
use Lzpeng\HyperfAuthGuard\Utils\IpResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * IP白名单检查监听器
 * 
 * 支持单个IP和IP段（CIDR格式）
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IPWhiteListListener implements EventSubscriberInterface
{
    public function __construct(
        private IpResolver $ipResolver,
        private array|IPWhiteListProviderInterface $whiteList = []
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => 'checkPassport',
        ];
    }

    public function checkPassport(CheckPassportEvent $event): void
    {
        $whiteList = $this->whiteList instanceof IPWhiteListProviderInterface
            ? $this->whiteList->getWhiteList()
            : $this->whiteList;

        // 空白名单表示不限制
        if (empty($whiteList)) {
            return;
        }

        $passport = $event->getPassport();
        $ip = $this->ipResolver->resolve($event->getRequest());

        if (!$this->isAllowed($ip, $whiteList)) {
            throw new IPNotInWhiteListException($passport->getUserIdentifier(), $ip);
        }
    }

    /**
     * 检查IP是否在白名单中
     *
     * @param string $ip
     * @param array $whiteList
     * @return boolean
     */
    private function isAllowed(string $ip, array $whiteList): bool
    {
        foreach ($whiteList as $allowed) {
            if ($this->matchesRule($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查IP是否匹配规则
     *
     * @param string $ip 要检查的IP
     * @param string $rule 规则（单个IP或CIDR格式）
     * @return bool
     */
    private function matchesRule(string $ip, string $rule): bool
    {
        // 精确匹配
        if ($ip === $rule) {
            return true;
        }

        // CIDR 格式检查
        if (str_contains($rule, '/')) {
            return $this->matchesCidr($ip, $rule);
        }

        // 通配符支持（可选）
        if (str_contains($rule, '*')) {
            return $this->matchesWildcard($ip, $rule);
        }

        return false;
    }

    /**
     * 检查IP是否在CIDR范围内
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    private function matchesCidr(string $ip, string $cidr): bool
    {
        [$network, $mask] = explode('/', $cidr, 2);

        // 验证IP格式
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($network, FILTER_VALIDATE_IP)) {
            return false;
        }

        // 验证掩码
        $mask = (int) $mask;
        if ($mask < 0 || $mask > (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32)) {
            return false;
        }

        // IPv4 处理
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->matchesIpv4Cidr($ip, $network, $mask);
        }

        // IPv6 处理
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->matchesIpv6Cidr($ip, $network, $mask);
        }

        return false;
    }

    /**
     * IPv4 CIDR 匹配
     *
     * @param string $ip
     * @param string $network
     * @param int $mask
     * @return bool
     */
    private function matchesIpv4Cidr(string $ip, string $network, int $mask): bool
    {
        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);

        if ($ipLong === false || $networkLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($networkLong & $maskLong);
    }

    /**
     * IPv6 CIDR 匹配
     *
     * @param string $ip
     * @param string $network
     * @param int $mask
     * @return bool
     */
    private function matchesIpv6Cidr(string $ip, string $network, int $mask): bool
    {
        $ipBinary = inet_pton($ip);
        $networkBinary = inet_pton($network);

        if ($ipBinary === false || $networkBinary === false) {
            return false;
        }

        $bytesToCheck = intval($mask / 8);
        $bitsToCheck = $mask % 8;

        // 检查完整字节
        if ($bytesToCheck > 0 && substr($ipBinary, 0, $bytesToCheck) !== substr($networkBinary, 0, $bytesToCheck)) {
            return false;
        }

        // 检查剩余位
        if ($bitsToCheck > 0) {
            $byte1 = ord($ipBinary[$bytesToCheck]) >> (8 - $bitsToCheck);
            $byte2 = ord($networkBinary[$bytesToCheck]) >> (8 - $bitsToCheck);

            if ($byte1 !== $byte2) {
                return false;
            }
        }

        return true;
    }

    /**
     * 通配符匹配
     *
     * @param string $ip
     * @param string $pattern
     * @return bool
     */
    private function matchesWildcard(string $ip, string $pattern): bool
    {
        // 将通配符转换为正则表达式
        $regex = '/^' . str_replace(['\.', '\*'], ['\.', '[0-9]+'], preg_quote($pattern, '/')) . '$/';

        return preg_match($regex, $ip) === 1;
    }
}

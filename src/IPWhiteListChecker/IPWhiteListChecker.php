<?php

declare(strict_types=1);

namespace GaaraHyperf\IPWhiteListChecker;

/**
 * IP白名单检查器
 * 
 * 支持单个IP、P段（CIDR格式）和通配符(*)
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class IPWhiteListChecker implements IPWhiteListCheckerInterface
{
    /**
     * 检查IP是否在白名单中
     *
     * @param string $ip
     * @param array $whiteList
     * @return boolean
     */
    public function isAllowed(string $ip, array $whiteList): bool
    {
        if (empty($whiteList)) {
            return true;
        }

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
     * @param string $ip
     * @param string $rule
     * @return boolean
     */
    private function matchesRule(string $ip, string $rule): bool
    {
        if ($ip === $rule) {
            return true;
        }

        if (str_contains($rule, '/')) {
            return $this->matchesCidr($ip, $rule);
        }

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
     * @return boolean
     */
    private function matchesCidr(string $ip, string $cidr): bool
    {
        [$network, $mask] = explode('/', $cidr, 2);

        if (!filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($network, FILTER_VALIDATE_IP)) {
            return false;
        }

        $mask = (int) $mask;
        if ($mask < 0 || $mask > (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 32)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->matchesIpv4Cidr($ip, $network, $mask);
        }

        return $this->matchesIpv6Cidr($ip, $network, $mask);
    }

    /**
     * IPv4 CIDR 检查
     *
     * @param string $ip
     * @param string $network
     * @param integer $mask
     * @return boolean
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
     * IPv6 CIDR 检查
     *
     * @param string $ip
     * @param string $network
     * @param integer $mask
     * @return boolean
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

        if ($bytesToCheck > 0 && substr($ipBinary, 0, $bytesToCheck) !== substr($networkBinary, 0, $bytesToCheck)) {
            return false;
        }

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
     * 通配符匹配检查
     *
     * @param string $ip
     * @param string $pattern
     * @return boolean
     */
    private function matchesWildcard(string $ip, string $pattern): bool
    {
        $regex = '/^' . str_replace(['\.', '\*'], ['\.', '[0-9]+'], preg_quote($pattern, '/')) . '$/';

        return preg_match($regex, $ip) === 1;
    }
}

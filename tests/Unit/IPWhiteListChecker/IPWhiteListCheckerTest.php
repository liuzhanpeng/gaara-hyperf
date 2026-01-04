<?php

declare(strict_types=1);

describe('IPWhiteListChecker', function () {

    beforeEach(function () {
        $this->checker = new GaaraHyperf\IPWhiteListChecker\IPWhiteListChecker();
    });

    it('should allow IP in whitelist', function () {
        $whiteList = [
            '203.0.113.195',
            '198.51.100.17',
            '192.0.2.*',
            '10.0.0.0/8',
        ];

        expect($this->checker->isAllowed('203.0.113.195', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('198.51.100.17', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('192.0.2.1', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('10.0.0.1', $whiteList))->toBeTrue();
    });

    it('should deny IP not in whitelist', function () {
        $whiteList = [
            '203.0.113.195',
            '198.51.100.17',
            '192.0.2.*',
            '10.0.0.0/8',
        ];
        expect($this->checker->isAllowed('203.0.113.196', $whiteList))->toBeFalse();
        expect($this->checker->isAllowed('198.51.100.18', $whiteList))->toBeFalse();
        expect($this->checker->isAllowed('192.0.3.1', $whiteList))->toBeFalse();
        expect($this->checker->isAllowed('11.0.0.1', $whiteList))->toBeFalse();
    });

    it('should allow all IPs when whitelist is empty', function () {
        $whiteList = [];
        expect($this->checker->isAllowed('203.0.113.195', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('198.51.100.17', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('192.0.2.1', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('10.0.0.1', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('10.255.255.1', $whiteList))->toBeTrue();
    });

    it('should handle IPv6 addresses', function () {
        $whiteList = [
            '2001:db8:85a3::8a2e:370:7334', // Exact match
            '2001:db8:a::/48',              // CIDR
            '2001:db8:b:*:*:*:*:*',         // Wildcard
        ];

        // Allowed
        expect($this->checker->isAllowed('2001:db8:85a3::8a2e:370:7334', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('2001:db8:a:ffff:ffff:ffff:ffff:ffff', $whiteList))->toBeTrue();
        expect($this->checker->isAllowed('2001:db8:b:1:2:3:4:5', $whiteList))->toBeTrue();

        // Denied
        expect($this->checker->isAllowed('2001:db8:85a3::8a2e:370:7335', $whiteList))->toBeFalse();
        expect($this->checker->isAllowed('2001:db8:b::/48', $whiteList))->toBeFalse();
        expect($this->checker->isAllowed('2001:db8:c:1:2:3:4:5', $whiteList))->toBeFalse();
    });

    it('should handle CIDR edge cases', function () {
        // IPv4
        expect($this->checker->isAllowed('192.168.1.100', ['192.168.1.0/24']))->toBeTrue();
        expect($this->checker->isAllowed('192.168.2.1', ['192.168.1.0/24']))->toBeFalse();
        expect($this->checker->isAllowed('192.168.1.1', ['192.168.1.1/32']))->toBeTrue();
        expect($this->checker->isAllowed('192.168.1.2', ['192.168.1.1/32']))->toBeFalse();
        expect($this->checker->isAllowed('10.20.30.40', ['0.0.0.0/0']))->toBeTrue();

        // IPv6
        expect($this->checker->isAllowed('2001:db8:dead:beef::1', ['2001:db8:dead:beef::/64']))->toBeTrue();
        expect($this->checker->isAllowed('2001:db8:dead:beee::1', ['2001:db8:dead:beef::/64']))->toBeFalse();
        expect($this->checker->isAllowed('2001:db8::1', ['2001:db8::1/128']))->toBeTrue();
        expect($this->checker->isAllowed('2001:db8::2', ['2001:db8::1/128']))->toBeFalse();
        expect($this->checker->isAllowed('::1', ['::/0']))->toBeTrue();
    });

    it('should handle wildcard edge cases', function () {
        expect($this->checker->isAllowed('192.168.1.1', ['192.168.*.1']))->toBeTrue();
        expect($this->checker->isAllowed('192.168.2.1', ['192.168.*.1']))->toBeTrue();
        expect($this->checker->isAllowed('192.168.2.2', ['192.168.*.1']))->toBeFalse();
        expect($this->checker->isAllowed('10.0.0.1', ['*.*.*.*']))->toBeTrue();
    });

    it('should return false for invalid inputs', function () {
        // Invalid IP to check
        expect($this->checker->isAllowed('not-an-ip', ['192.168.1.1']))->toBeFalse();
        expect($this->checker->isAllowed('999.999.999.999', ['192.168.1.1']))->toBeFalse();
        expect($this->checker->isAllowed('192.168.1.1', ['not-a-rule']))->toBeFalse();

        // Invalid CIDR rule
        expect($this->checker->isAllowed('192.168.1.1', ['192.168.1.0/33']))->toBeFalse();
        expect($this->checker->isAllowed('192.168.1.1', ['192.168.1.0/-1']))->toBeFalse();
        expect($this->checker->isAllowed('192.168.1.1', ['not-an-ip/24']))->toBeFalse();
        expect($this->checker->isAllowed('2001:db8::1', ['2001:db8::/129']))->toBeFalse();

        // Invalid wildcard rule (though current regex is lenient)
        expect($this->checker->isAllowed('192.168.1.1', ['192.168.1.**']))->toBeFalse();
    });
});

<?php

declare(strict_types=1);

describe('IPResolver', function () {
    beforeEach(function () {
        $this->resolver = new GaaraHyperf\IPResolver\IPResolver();
        $this->request = mock(Psr\Http\Message\ServerRequestInterface::class);
    });

    it('should return null when no IP headers are present', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->andReturn('');
        $this->request->shouldReceive('getAttribute')
            ->with('ip')
            ->andReturn('');
        $this->request->shouldReceive('getServerParams')
            ->andReturn([]);

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('');
    });

    it('should extract IP from X-Forwarded-For header', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('203.0.113.195');

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('203.0.113.195');
    });

    it('should extract IP from X-Forwarded-For header with multiple ip', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('203.0.113.195, 198.51.100.17');

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('203.0.113.195');
    });

    it('should extract first valid IP from X-Forwarded-For header with multiple IPs', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('unknown, 203.0.113.195');
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Real-IP')
            ->andReturn('');

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('203.0.113.195');
    });

    it('should extract IP from X-Real-IP header if X-Forwarded-For is absent', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Real-IP')
            ->andReturn('203.0.113.195');

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('203.0.113.195');
    });

    it('should extract IP from cf-connecting-ip header if others are absent', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Real-IP')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('CF-Connecting-IP')
            ->andReturn('203.0.113.195');

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('203.0.113.195');
    });

    it('should extract IP from request attribute if headers are absent', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Real-IP')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('CF-Connecting-IP')
            ->andReturn('');
        $this->request->shouldReceive('getAttribute')
            ->with('ip')
            ->andReturn('203.0.113.195');
        $this->request->shouldReceive('getServerParams')
            ->andReturn([]);
        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('203.0.113.195');
    });

    it('should extract IP from remote_addr server param if others are absent', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Real-IP')
            ->andReturn('');
        $this->request->shouldReceive('getHeaderLine')
            ->with('CF-Connecting-IP')
            ->andReturn('');
        $this->request->shouldReceive('getAttribute')
            ->with('ip')
            ->andReturn('');
        $this->request->shouldReceive('getServerParams')
            ->andReturn(['remote_addr' => '203.0.113.195']);

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('203.0.113.195');
    });

    it('should return null if no valid IP found', function () {
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Forwarded-For')
            ->andReturn('invalid_ip');
        $this->request->shouldReceive('getHeaderLine')
            ->with('X-Real-IP')
            ->andReturn('also_invalid');
        $this->request->shouldReceive('getHeaderLine')
            ->with('CF-Connecting-IP')
            ->andReturn('');
        $this->request->shouldReceive('getAttribute')
            ->with('ip')
            ->andReturn('not_an_ip');
        $this->request->shouldReceive('getServerParams')
            ->andReturn(['remote_addr' => 'still_not_ip']);

        $ip = $this->resolver->resolve($this->request);
        expect($ip)->toBe('');
    });
});

<?php

describe('GuardResolver', function () {
    beforeEach(function () {
        $this->container = mock(\Psr\Container\ContainerInterface::class);
        $this->guardMap = [
            'web' => 'guard.web',
            'api' => 'guard.api',
        ];
        $this->resolver = new \GaaraHyperf\GuardResolver(
            $this->guardMap,
            $this->container,
        );
    });

    it('resolves guard by name', function () {
        $webGuard = mock(\GaaraHyperf\GuardInterface::class);
        $this->container
            ->shouldReceive('get')
            ->with('guard.web')
            ->andReturn($webGuard);

        $resolvedGuard = $this->resolver->resolve('web');
        expect($resolvedGuard)->toBe($webGuard);
    });

    it('throws exception for unknown guard', function () {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->resolve('unknown');
    });

    it('iterates over guards', function () {
        $webGuard = mock(\GaaraHyperf\GuardInterface::class);
        $apiGuard = mock(\GaaraHyperf\GuardInterface::class);
        $this->container
            ->shouldReceive('get')
            ->with('guard.web')
            ->andReturn($webGuard);
        $this->container
            ->shouldReceive('get')
            ->with('guard.api')
            ->andReturn($apiGuard);

        $guards = iterator_to_array($this->resolver);
        expect($guards)->toHaveKey('web');
        expect($guards['web'])->toBe($webGuard);
        expect($guards)->toHaveKey('api');
        expect($guards['api'])->toBe($apiGuard);
    });
});

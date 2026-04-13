<?php

declare(strict_types=1);

use GaaraHyperf\GuardInterface;
use GaaraHyperf\GuardResolver;

describe('GuardResolver', function () {
    beforeEach(function () {
        $this->factories = [];
        $this->resolver = new GuardResolver($this->factories);
    });

    it('resolves guard by name', function () {
        $webGuard = mock(GuardInterface::class);
        $this->resolver = new GuardResolver([
            'web' => fn () => $webGuard,
        ]);

        $resolvedGuard = $this->resolver->resolve('web');
        expect($resolvedGuard)->toBe($webGuard);
    });

    it('throws exception for unknown guard', function () {
        $this->expectException(InvalidArgumentException::class);
        $this->resolver->resolve('unknown');
    });

    it('iterates over guards', function () {
        $webGuard = mock(GuardInterface::class);
        $apiGuard = mock(GuardInterface::class);
        $this->resolver = new GuardResolver([
            'web' => fn () => $webGuard,
            'api' => fn () => $apiGuard,
        ]);

        $guards = iterator_to_array($this->resolver);
        expect($guards)->toHaveKey('web');
        expect($guards['web'])->toBe($webGuard);
        expect($guards)->toHaveKey('api');
        expect($guards['api'])->toBe($apiGuard);
    });
});

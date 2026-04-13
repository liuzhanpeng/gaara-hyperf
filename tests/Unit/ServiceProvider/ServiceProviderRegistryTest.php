<?php

declare(strict_types=1);

use GaaraHyperf\ServiceProvider\ServiceProviderInterface;
use GaaraHyperf\ServiceProvider\ServiceProviderRegistry;
use Hyperf\Contract\ContainerInterface;

it('registers providers in order and returns self', function (): void {
    $registry = new ServiceProviderRegistry();

    $first = new class implements ServiceProviderInterface {
        public function register(ContainerInterface $container): void
        {
        }
    };

    $second = new class implements ServiceProviderInterface {
        public function register(ContainerInterface $container): void
        {
        }
    };

    $returned = $registry->register($first)->register($second);

    expect($returned)->toBe($registry)
        ->and($registry->getProviders())->toBe([$first, $second]);
});

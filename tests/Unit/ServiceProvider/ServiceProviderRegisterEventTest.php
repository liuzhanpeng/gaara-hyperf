<?php

declare(strict_types=1);

use GaaraHyperf\ServiceProvider\ServiceProviderRegisterEvent;
use GaaraHyperf\ServiceProvider\ServiceProviderRegistry;

it('exposes the same provider registry instance', function (): void {
    $registry = new ServiceProviderRegistry();
    $event = new ServiceProviderRegisterEvent($registry);

    expect($event->serviceProviderRegistry())->toBe($registry);
});

<?php

declare(strict_types=1);

use GaaraHyperf\Config\ListenerConfigCollection;

it('creates listener config collection from string and array configs', function (): void {
    $collection = ListenerConfigCollection::from([
        TestListenerConfigCollectionListener::class,
        [
            'class' => TestListenerConfigCollectionAnotherListener::class,
            'params' => ['max_attempts' => 5],
        ],
    ]);

    $items = iterator_to_array($collection);

    expect($items)->toHaveCount(2)
        ->and($items[0]->class())->toBe(TestListenerConfigCollectionListener::class)
        ->and($items[0]->params())->toBe([])
        ->and($items[1]->class())->toBe(TestListenerConfigCollectionAnotherListener::class)
        ->and($items[1]->params())->toBe(['maxAttempts' => 5]);
});

class TestListenerConfigCollectionListener
{
}

class TestListenerConfigCollectionAnotherListener
{
}

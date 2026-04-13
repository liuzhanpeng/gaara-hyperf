<?php

declare(strict_types=1);

use GaaraHyperf\Config\AuthenticatorConfigCollection;

it('throws when authenticator config is empty', function (): void {
    expect(fn () => AuthenticatorConfigCollection::from([]))
        ->toThrow(InvalidArgumentException::class, 'authenticators config is required');
});

it('builds collection for built in and custom authenticators', function (): void {
    $collection = AuthenticatorConfigCollection::from([
        'api_key' => ['api_key_field' => 'X-API-KEY'],
        'custom' => [
            [
                'class' => TestAuthenticatorConfigCollectionCustomAuthenticator::class,
                'params' => ['token_manager' => 'default'],
            ],
        ],
    ]);

    $items = iterator_to_array($collection);

    expect($items)->toHaveCount(2)
        ->and($items[0]->type())->toBe('api_key')
        ->and($items[0]->options())->toBe(['api_key_field' => 'X-API-KEY'])
        ->and($items[1]->type())->toBe(TestAuthenticatorConfigCollectionCustomAuthenticator::class)
        ->and($items[1]->options())->toBe(['token_manager' => 'default']);
});

it('throws when custom authenticator misses class option', function (): void {
    expect(fn () => AuthenticatorConfigCollection::from([
        'custom' => [
            ['params' => []],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'missing the class option');
});

class TestAuthenticatorConfigCollectionCustomAuthenticator
{
}

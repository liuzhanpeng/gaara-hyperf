<?php

declare(strict_types=1);

use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\Builder\ModelUserProviderBuilder;
use GaaraHyperf\UserProvider\ModelUserProvider;
use Hyperf\DbConnection\Model\Model;

it('throws when class option is missing', function (): void {
    $builder = new ModelUserProviderBuilder();

    expect(fn () => $builder->create(['identifier' => 'email']))
        ->toThrow(InvalidArgumentException::class, 'class');
});

it('throws when identifier option is missing', function (): void {
    $builder = new ModelUserProviderBuilder();

    expect(fn () => $builder->create(['class' => ModelUserProviderBuilderTestUserModel::class]))
        ->toThrow(InvalidArgumentException::class, 'identifier');
});

it('creates model user provider for valid options', function (): void {
    $builder = new ModelUserProviderBuilder();

    $provider = $builder->create([
        'class' => ModelUserProviderBuilderTestUserModel::class,
        'identifier' => 'email',
    ]);

    expect($provider)->toBeInstanceOf(ModelUserProvider::class);
});

class ModelUserProviderBuilderTestUserModel extends Model implements UserInterface
{
    public function getIdentifier(): string
    {
        return (string) ($this->attributes['email'] ?? '');
    }
}

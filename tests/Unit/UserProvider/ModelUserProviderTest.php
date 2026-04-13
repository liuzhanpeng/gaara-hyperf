<?php

declare(strict_types=1);

use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\ModelUserProvider;
use Hyperf\DbConnection\Model\Model;

it('throws when model class does not exist', function (): void {
    expect(fn () => new ModelUserProvider('Not\Existing\Model', 'email'))
        ->toThrow(InvalidArgumentException::class, 'does not exist');
});

it('throws when model class is not subclass of Model', function (): void {
    expect(fn () => new ModelUserProvider(ModelUserProviderTestNotModelClass::class, 'email'))
        ->toThrow(InvalidArgumentException::class, 'must extend');
});

it('throws when identifier is empty', function (): void {
    expect(fn () => new ModelUserProvider(ModelUserProviderTestUserModel::class, ''))
        ->toThrow(InvalidArgumentException::class, 'identifier field name cannot be empty');
});

it('returns null when user cannot be found', function (): void {
    ModelUserProviderTestUserModel::$queryResult = null;

    $provider = new ModelUserProvider(ModelUserProviderTestUserModel::class, 'email');

    expect($provider->findByIdentifier('missing@example.com'))->toBeNull();
});

it('returns user when model implements user interface', function (): void {
    $user = new ModelUserProviderTestUser('alice@example.com');
    ModelUserProviderTestUserModel::$queryResult = $user;

    $provider = new ModelUserProvider(ModelUserProviderTestUserModel::class, 'email');

    $found = $provider->findByIdentifier('alice@example.com');

    expect($found)->toBeInstanceOf(UserInterface::class)
        ->and($found?->getIdentifier())->toBe('alice@example.com');
});

it('throws when found model does not implement user interface', function (): void {
    ModelUserProviderTestNonUserModel::$queryResult = new stdClass();

    $provider = new ModelUserProvider(ModelUserProviderTestNonUserModel::class, 'email');

    expect(fn () => $provider->findByIdentifier('x@example.com'))
        ->toThrow(RuntimeException::class, 'must implement UserInterface');
});

class ModelUserProviderTestFakeBuilder
{
    public function __construct(private mixed $result)
    {
    }

    public function where(string $field, string $value): self
    {
        return $this;
    }

    public function first(): mixed
    {
        return $this->result;
    }
}

class ModelUserProviderTestUserModel extends Model implements UserInterface
{
    public static mixed $queryResult = null;

    public static function query(): ModelUserProviderTestFakeBuilder
    {
        return new ModelUserProviderTestFakeBuilder(static::$queryResult);
    }

    public function getIdentifier(): string
    {
        return (string) ($this->email ?? '');
    }
}

class ModelUserProviderTestUser implements UserInterface
{
    public function __construct(private string $identifier)
    {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}

class ModelUserProviderTestNonUserModel extends Model
{
    public static mixed $queryResult = null;

    public static function query(): ModelUserProviderTestFakeBuilder
    {
        return new ModelUserProviderTestFakeBuilder(static::$queryResult);
    }
}

class ModelUserProviderTestNotModelClass
{
}

<?php

declare(strict_types=1);

use GaaraHyperf\PasswordHasher\DefaultPasswordHasher;
use GaaraHyperf\PasswordHasher\PasswordHasherFactory;
use GaaraHyperf\PasswordHasher\PasswordHasherInterface;
use Hyperf\Contract\ContainerInterface;
use Mockery\MockInterface;

afterEach(function (): void {
    Mockery::close();
});

it('creates default password hasher and verifies hash', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    $factory = new PasswordHasherFactory($container);

    $hasher = $factory->create(['type' => 'default']);

    expect($hasher)->toBeInstanceOf(DefaultPasswordHasher::class);

    $hash = $hasher->hash('secret');
    expect($hasher->verify('secret', $hash))->toBeTrue();
    expect($hasher->verify('wrong', $hash))->toBeFalse();
});

it('creates custom password hasher and maps params', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    /** @var MockInterface&PasswordHasherInterface $hasher */
    $hasher = Mockery::mock(PasswordHasherInterface::class);

    $container->shouldReceive('make')->once()->with(
        CustomPasswordHasherForFactoryTest::class,
        ['saltRounds' => 12]
    )->andReturn($hasher);

    $factory = new PasswordHasherFactory($container);

    expect($factory->create([
        'type' => 'custom',
        'class' => CustomPasswordHasherForFactoryTest::class,
        'params' => ['salt_rounds' => 12],
    ]))->toBe($hasher);
});

it('throws when custom password hasher does not implement interface', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);

    $container->shouldReceive('make')->once()->with(NotPasswordHasherForFactoryTest::class, [])->andReturn(new NotPasswordHasherForFactoryTest());

    $factory = new PasswordHasherFactory($container);

    expect(fn () => $factory->create([
        'type' => 'custom',
        'class' => NotPasswordHasherForFactoryTest::class,
    ]))->toThrow(InvalidArgumentException::class, 'must be an instance of PasswordHasherInterface');
});

it('throws when password hasher type is invalid', function (): void {
    /** @var ContainerInterface&MockInterface $container */
    $container = Mockery::mock(ContainerInterface::class);
    $factory = new PasswordHasherFactory($container);

    expect(fn () => $factory->create(['type' => 'invalid']))
        ->toThrow(InvalidArgumentException::class, 'Invalid password hasher type');
});

class CustomPasswordHasherForFactoryTest implements PasswordHasherInterface
{
    public function hash(string $password): string
    {
        return $password;
    }

    public function verify(string $password, string $hashedPassword): bool
    {
        return true;
    }
}

class NotPasswordHasherForFactoryTest
{
}

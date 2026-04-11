<?php

declare(strict_types=1);

namespace GaaraHyperf\PasswordHasher;

use InvalidArgumentException;

/**
 * 密码哈希器解析器.
 */
class PasswordHasherResolver implements PasswordHasherResolverInterface
{
    private array $passwordHashers = [];

    public function __construct(
        private array $factories,
    ) {
    }

    /**
     * @{@inheritDoc}
     */
    public function resolve(string $name = 'default'): PasswordHasherInterface
    {
        if (! isset($this->passwordHashers[$name])) {
            if (! isset($this->factories[$name])) {
                throw new InvalidArgumentException(sprintf('Password hasher "%s" is not defined', $name));
            }

            $this->passwordHashers[$name] = ($this->factories[$name])();
        }

        return $this->passwordHashers[$name];
    }
}

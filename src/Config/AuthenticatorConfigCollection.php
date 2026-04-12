<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * 认证器配置集合.
 */
class AuthenticatorConfigCollection implements IteratorAggregate
{
    /**
     * @param AuthenticatorConfig[] $authenticatorConfigCollection
     */
    public function __construct(
        private array $authenticatorConfigCollection,
    ) {
    }

    public static function from(array $config): self
    {
        if (count($config) === 0) {
            throw new InvalidArgumentException('authenticators config is required');
        }

        $authenticatorConfigCollection = [];
        foreach ($config as $type => $options) {
            if ($type === 'custom') {
                foreach ($options as $customAuthenticatorConfig) {
                    if (! isset($customAuthenticatorConfig['class'])) {
                        throw new InvalidArgumentException('Custom authenticator config is missing the class option');
                    }

                    $authenticatorConfigCollection[] = new AuthenticatorConfig($customAuthenticatorConfig['class'], $customAuthenticatorConfig['params'] ?? []);
                }
            } else {
                $authenticatorConfigCollection[] = new AuthenticatorConfig($type, $options);
            }
        }

        return new self($authenticatorConfigCollection);
    }

    /**
     * @return Traversable<AuthenticatorConfig>
     */
    public function getIterator(): Traversable
    {
        yield from $this->authenticatorConfigCollection;
    }
}

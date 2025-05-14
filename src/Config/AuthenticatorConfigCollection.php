<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

use Traversable;

/**
 * 认证器配置集合
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorConfigCollection implements \IteratorAggregate
{
    /**
     * @param AuthenticatorConfig[] $authenticatorConfigCollection
     */
    public function __construct(
        private array $authenticatorConfigCollection,
    ) {}

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        $authenticatorConfigCollection = [];
        foreach ($config as $type => $params) {
            if ($type === 'custom') {
                foreach ($params as $customAuthenticatorConfig) {
                    if (!isset($customAuthenticatorConfig['class'])) {
                        throw new \InvalidArgumentException('Invalid custom authenticator config: class is required');
                    }

                    $authenticatorConfigCollection[] = new AuthenticatorConfig($customAuthenticatorConfig['class'], $customAuthenticatorConfig['params'] ?? []);
                }
            } else {
                $authenticatorConfigCollection[] = new AuthenticatorConfig($type, $params);
            }
        }

        return new self($authenticatorConfigCollection);
    }

    /**
     * @inheritDoc
     * 
     * @return Traversable<AuthenticatorConfig>
     */
    public function getIterator(): Traversable
    {
        yield from $this->authenticatorConfigCollection;
    }
}

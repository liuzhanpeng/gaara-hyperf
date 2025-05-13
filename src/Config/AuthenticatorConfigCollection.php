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

    public static function from(array $config): self
    {
        $authenticatorConfigCollection = [];
        foreach ($config as $id => $params) {
            if ($id === 'custom') {
                foreach ($params as $customAuthenticator) {
                    $authenticatorConfigCollection[] = new AuthenticatorConfig($customAuthenticator['class'], $customAuthenticator['params'] ?? []);
                }
            } else {
                $authenticatorConfigCollection[] = new AuthenticatorConfig($id, $params);
            }
        }

        return new self($authenticatorConfigCollection);
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->authenticatorConfigCollection;
    }
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

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
        if (count($config) === 0) {
            throw new \InvalidArgumentException('authenticators配置不能为空');
        }

        $authenticatorConfigCollection = [];
        foreach ($config as $type => $options) {
            if ($type === 'custom') {
                foreach ($options as $customAuthenticatorConfig) {
                    if (!isset($customAuthenticatorConfig['class'])) {
                        throw new \InvalidArgumentException("自定义认证器配置缺少class选项");
                    }

                    $authenticatorConfigCollection[] = new AuthenticatorConfig($customAuthenticatorConfig['class'], $customAuthenticatorConfig['args'] ?? []);
                }
            } else {
                $authenticatorConfigCollection[] = new AuthenticatorConfig($type, $options);
            }
        }

        return new self($authenticatorConfigCollection);
    }

    /**
     * @inheritDoc
     * 
     * @return \Traversable<AuthenticatorConfig>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->authenticatorConfigCollection;
    }
}

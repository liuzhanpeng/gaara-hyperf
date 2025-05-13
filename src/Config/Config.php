<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

/**
 * 认证配置
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class Config
{
    /**
     * @param GuardConfig[] $guardConfigCollection
     */
    public function __construct(private array $guardConfigCollection) {}

    public static function from(array $config): self
    {
        if (!isset($config['guards'])) {
            throw new \InvalidArgumentException('配置中必须包含guards');
        }

        if (count($config['guards']) === 0) {
            throw new \InvalidArgumentException('配置中必须包含至少一个guard');
        }

        $guardConfigCollection = [];
        foreach ($config['guards'] as $name => $guardConfig) {
            $guardConfigCollection[$name] = GuardConfig::from($guardConfig);
        }

        return new self($guardConfigCollection);
    }

    /**
     * 返回所有guard的配置
     * 
     * @return array<string, GuardConfig>
     */
    public function guardConfigCollection(): array
    {
        return $this->guardConfigCollection;
    }
}

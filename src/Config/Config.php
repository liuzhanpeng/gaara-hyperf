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

    /**
     * @param array $config
     * @return self
     */
    public static function from(array $config): self
    {
        if (!isset($config['guards']) || count($config['guards']) === 0) {
            throw new \InvalidArgumentException('guards config is required');
        }

        $guardConfigCollection = [];
        foreach ($config['guards'] as $guardName => $guardConfig) {
            $guardConfigCollection[] = GuardConfig::from($guardName, $guardConfig);
        }

        return new self($guardConfigCollection);
    }

    /**
     * 返回所有guard的配置
     * 
     * @return GuardConfig[]
     */
    public function guardConfigCollection(): array
    {
        return $this->guardConfigCollection;
    }
}

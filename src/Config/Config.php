<?php

declare(strict_types=1);

namespace GaaraHyperf\Config;

use InvalidArgumentException;

/**
 * 认证配置.
 */
class Config
{
    /**
     * @param array<string, GuardConfig> $guardConfigCollection
     * @param array<string, array> $servicesConfigCollection
     */
    public function __construct(
        private array $guardConfigCollection,
        private array $servicesConfigCollection,
    ) {
    }

    public static function from(array $config): self
    {
        if (! isset($config['guards']) || count($config['guards']) === 0) {
            throw new InvalidArgumentException('`guards` config is required and must contain at least one guard. See docs/quickstart.md and docs/configuration.md.');
        }

        $guardConfigCollection = [];
        foreach ($config['guards'] as $guardName => $guardConfig) {
            $guardConfigCollection[$guardName] = GuardConfig::from($guardConfig, (string) $guardName);
        }

        return new self($guardConfigCollection, $config['services'] ?? []);
    }

    /**
     * 返回所有guard的配置.
     *
     * @return array<string, GuardConfig>
     */
    public function guardConfigCollection(): array
    {
        return $this->guardConfigCollection;
    }

    /**
     * 返回指定服务的配置.
     *
     * @return array<string, array>
     */
    public function serviceConfig(string $name): array
    {
        return $this->servicesConfigCollection[$name] ?? [];
    }
}

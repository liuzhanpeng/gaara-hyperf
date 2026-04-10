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
     */
    public function __construct(
        private array $guardConfigCollection,
        private array $servicesConfigCollection,
        private array $exceptionMessages
    ) {
    }

    public static function from(array $config): self
    {
        if (! isset($config['guards']) || count($config['guards']) === 0) {
            throw new InvalidArgumentException('guards config is required');
        }

        $guardConfigCollection = [];
        foreach ($config['guards'] as $guardName => $guardConfig) {
            $guardConfigCollection[$guardName] = GuardConfig::from($guardConfig);
        }

        return new self($guardConfigCollection, $config['services'] ?? [], $config['exception_messages'] ?? []);
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

    /**
     * 返回异常消息配置.
     */
    public function exceptionMessages(): array
    {
        return $this->exceptionMessages;
    }
}

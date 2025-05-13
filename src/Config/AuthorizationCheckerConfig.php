<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Config;

class AuthorizationCheckerConfig
{
    public function __construct(
        private string $class,
        private array $params,
    ) {}

    public static function from(array $config): self
    {
        return new self(
            $config['calss'],
            $config['params'] ?? []
        );
    }

    public function class(): string
    {
        return $this->class;
    }

    public function params(): array
    {
        return $this->params;
    }
}

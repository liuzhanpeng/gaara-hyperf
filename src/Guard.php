<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证守卫
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class Guard
{
    public function __construct(
        private string $name,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * 返回认证守卫名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function authenticate(ServerRequestInterface $request): ?ResponseInterface {}
}

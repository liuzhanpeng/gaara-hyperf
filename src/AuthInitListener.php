<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Lzpeng\HyperfAuthGuard\ServiceProvider\BuiltInAuthenticatorServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\BuiltInUserProviderServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\CsrfTokenManagerServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\GuardServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\OpaqueTokenIssuerServiceProvider;
use Lzpeng\HyperfAuthGuard\ServiceProvider\PasswordHasherServiceProvider;

/**
 * 认证初始化监听器
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthInitListener implements ListenerInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @inheritDoc
     */
    public function listen(): array
    {
        return [
            BeforeMainServerStart::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(object $event): void
    {
        /**
         * @var AuthInitializer $initializer
         */
        $initializer = $this->container->get(AuthInitializer::class);

        // 注册内置服务提供者
        $initializer->registerService(new PasswordHasherServiceProvider())
            ->registerService(new CsrfTokenManagerServiceProvider())
            ->registerService(new OpaqueTokenIssuerServiceProvider())
            ->registerService(new BuiltInUserProviderServiceProvider())
            ->registerService(new BuiltInAuthenticatorServiceProvider())
            ->registerService(new GuardServiceProvider())
            ->boot();
    }
}

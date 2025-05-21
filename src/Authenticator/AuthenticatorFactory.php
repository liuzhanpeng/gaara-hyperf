<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorRegistry;
use Lzpeng\HyperfAuthGuard\Config\AuthenticatorConfig;

/**
 * 认证器创建工厂
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(
        AuthenticatorConfig $authenticatorConfig,
        string $userProviderId,
        string $eventDispatcherId,
    ): AuthenticatorInterface {
        $type = $authenticatorConfig->type();
        $options = $authenticatorConfig->options();

        /**
         * @var AuthenticatorRegistry $authenticatorRegistry
         */
        $authenticatorRegistry = $this->container->get(AuthenticatorRegistry::class);

        if ($authenticatorRegistry->hasFactory($type)) {
            return $authenticatorRegistry->getFacotry($type)->create($options, $userProviderId, $eventDispatcherId);
        } else {
            $authenticator = $this->container->make($type, $options);
            if (!$authenticator instanceof AuthenticatorInterface) {
                throw new \LogicException(sprintf('Authenticator "%s" must implement AuthenticatorInterface', $type));
            }

            return $authenticator;
        }
    }
}

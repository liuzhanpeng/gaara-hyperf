<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Config\AuthenticatorConfig;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * 认证器创建工厂
 *
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorFactory
{
    /**
     * 已注册的认证器构建器
     *
     * @var array<string, string> 认证器类型 => 认证器构建器类名
     */
    private array $builders = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @param AuthenticatorConfig $authenticatorConfig
     * @param UserProviderInterface $userProvider
     * @param EventDispatcher $eventDispatcher
     * @return AuthenticatorInterface
     */
    public function create(
        AuthenticatorConfig $authenticatorConfig,
        UserProviderInterface $userProvider,
        EventDispatcher $eventDispatcher
    ): AuthenticatorInterface {
        $type = $authenticatorConfig->type();
        $options = $authenticatorConfig->options();

        if (isset($this->builders[$type])) {
            $builder = $this->container->get($this->builders[$type]);
            return $builder->create($options, $userProvider, $eventDispatcher);
        } elseif ($type === 'custom') {
            $authenticator = $this->container->make($type, $options);
            if (!$authenticator instanceof AuthenticatorInterface) {
                throw new \LogicException(sprintf('Authenticator "%s" must implement AuthenticatorInterface', $type));
            }

            return $authenticator;
        }

        throw new \InvalidArgumentException(sprintf("Unsupported authenticator type: %s", $type));
    }

    /**
     * 注册认证器构建器
     *
     * @param string $type
     * @param string $builderClass
     * @return void
     */
    public function registerBuilder(string $type, string $builderClass): void
    {
        if (!is_subclass_of($builderClass, AuthenticatorBuilderInterface::class)) {
            throw new \InvalidArgumentException(sprintf('Authenticator Builder "%s" must implement %s', $builderClass, AuthenticatorBuilderInterface::class));
        }

        $this->builders[$type] = $builderClass;
    }
}

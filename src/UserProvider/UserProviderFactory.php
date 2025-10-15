<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\ComponentConfig;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Config\UserProviderConfig;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;

/**
 * 用户提供者服务工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com> 
 */
class UserProviderFactory
{
    /**
     * 用户提供者构建器
     *
     * @var array<string, string> 用户提供者类型 => 用户提供者构建器类名
     */
    private array $builders = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(
        private ContainerInterface $container
    ) {}

    /**
     * @param UserProviderConfig $userProviderConfig
     * @return UserProviderInterface
     */
    public function create(ComponentConfig $config): UserProviderInterface
    {
        $type = $config->type();
        $options = $config->options();

        if (isset($this->builders[$type])) {
            $builder = $this->container->get($this->builders[$type]);
            return $builder->create($options);
        } elseif ($type === 'custom') {
            $customConfig = CustomConfig::from($options);

            $userProvider = $this->container->make($customConfig->class(), $customConfig->args());
            if (!$userProvider instanceof UserProviderInterface) {
                throw new \LogicException("The custom user provider must implement the UserProviderInterface.");
            }

            return $userProvider;
        }

        throw new \InvalidArgumentException("Unsupported user provider type: {$type}");
    }

    /**
     * 注册用户提供者构建器
     *
     * @param string $type
     * @param string $builderClass
     * @return void
     */
    public function registerBuilder(string $type, string $builderClass): void
    {
        if (!is_subclass_of($builderClass, UserProviderBuilderInterface::class)) {
            throw new \InvalidArgumentException(sprintf('The builder class "%s" must implement %s.', $builderClass, UserProviderBuilderInterface::class));
        }

        $this->builders[$type] = $builderClass;
    }
}

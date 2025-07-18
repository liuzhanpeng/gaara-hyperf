<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UserProvider;

use Hyperf\Contract\ContainerInterface;
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

    public function __construct(
        private ContainerInterface $container
    ) {}

    /**
     * @param UserProviderConfig $userProviderConfig
     * @return UserProviderInterface
     */
    public function create(UserProviderConfig $userProviderConfig): UserProviderInterface
    {
        $type = $userProviderConfig->type();
        $options = $userProviderConfig->options();

        if (isset($this->builders[$type])) {
            $builder = $this->container->get($this->builders[$type]);
            return $builder->create($options);
        } elseif ($type === 'custom') {
            $customConfig = CustomConfig::from($options);

            $userProvider = $this->container->make($customConfig->class(), $customConfig->args());
            if (!$userProvider instanceof UserProviderInterface) {
                throw new \LogicException("自定义类型的用户提供器必须实现UserProviderInterface接口");
            }

            return $userProvider;
        }

        throw new \InvalidArgumentException("未支持的用户提供器类型: {$type}");
    }

    public function registerBuilder(string $type, string $builderClass): void
    {
        if (!is_subclass_of($builderClass, UserProviderBuilderInterface::class)) {
            throw new \InvalidArgumentException(sprintf('The builder class "%s" must implement %s.', $builderClass, UserProviderBuilderInterface::class));
        }

        $this->builders[$type] = $builderClass;
    }
}

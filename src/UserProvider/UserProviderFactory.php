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

        /**
         * @var UserProviderRegistry $userProviderRegistry
         */
        $userProviderRegistry = $this->container->get(UserProviderRegistry::class);

        if ($userProviderRegistry->hasFactory($type)) {
            return $userProviderRegistry->getFactory($type)->create($options);
        } elseif ($type === 'custom') {
            $customConfig = CustomConfig::from($options);

            $userProvider = $this->container->make($customConfig->class(), $customConfig->args());
            if (!$userProvider instanceof UserProviderInterface) {
                throw new \LogicException("自定义类型的用户提供器必须实现UserProviderInterface接口");
            }

            return $userProvider;
        }

        throw new \InvalidArgumentException("未支持的用户提供者类型: {$type}");
    }
}

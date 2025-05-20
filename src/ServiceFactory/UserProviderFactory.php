<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\ServiceFactory;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Config\UserProviderConfig;
use Lzpeng\HyperfAuthGuard\UserProvider\MemoryUserProvider;
use Lzpeng\HyperfAuthGuard\UserProvider\ModelUserProvider;
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

        switch ($type) {
            case 'memory':
                if (!isset($options['users'])) {
                    throw new \InvalidArgumentException("memory类型的用户提供器必须配置users选项");
                }

                return new MemoryUserProvider($options['users']);
            case 'model':
                if (!isset($options['class'])) {
                    throw new \InvalidArgumentException("model类型的用户提供器必须配置class选项");
                }

                if (!isset($options['identifier'])) {
                    throw new \InvalidArgumentException("model类型的用户提供器必须配置identifier选项");
                }

                return new ModelUserProvider($options['class'], $options['identifier']);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $userProvider = $this->container->make($customConfig->class(), $customConfig->args());
                if (!$userProvider instanceof UserProviderInterface) {
                    throw new \LogicException("自定义类型的用户提供器必须实现UserProviderInterface接口");
                }

                return $userProvider;
            default:
                throw new \InvalidArgumentException("未支持的用户提供者类型: {$type}");
        }
    }
}

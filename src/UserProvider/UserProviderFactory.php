<?php

declare(strict_types=1);

namespace GaaraHyperf\UserProvider;

use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\Config\CustomConfig;
use Hyperf\Contract\ContainerInterface;
use LogicException;

/**
 * 用户提供者服务工厂
 */
class UserProviderFactory
{
    /**
     * 用户提供者构建器.
     *
     * @var array<string, string> 用户提供者类型 => 用户提供者构建器类名
     */
    private array $builders = [];

    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function create(ComponentConfig $config): UserProviderInterface
    {
        $type = $config->type();
        $options = $config->options();

        if (isset($this->builders[$type])) {
            $builder = $this->container->get($this->builders[$type]);
            return $builder->create($options);
        }
        if ($type === 'custom') {
            $customConfig = CustomConfig::from($options);

            $userProvider = $this->container->make($customConfig->class(), $customConfig->params());
            if (! $userProvider instanceof UserProviderInterface) {
                throw new LogicException('The custom user provider must implement the UserProviderInterface.');
            }

            return $userProvider;
        }

        throw new LogicException("Unsupported user provider type: {$type}");
    }

    /**
     * 注册用户提供者构建器.
     */
    public function registerBuilder(string $type, string $builderClass): void
    {
        if (! is_subclass_of($builderClass, UserProviderBuilderInterface::class)) {
            throw new LogicException(sprintf('The builder class "%s" must implement %s.', $builderClass, UserProviderBuilderInterface::class));
        }

        $this->builders[$type] = $builderClass;
    }
}

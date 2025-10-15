<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\ComponentConfig;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\DefaultUnauthenticatedHandler;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\RedirectUnauthenticatedHandler;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;

/**
 * 未认证处理器服务工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class UnauthenticatedHandlerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(ComponentConfig $config): UnauthenticatedHandlerInterface
    {
        $type = $config->type();
        $options = $config->options();

        switch ($type) {
            case 'default':
                return new DefaultUnauthenticatedHandler();
            case 'redirect':
                return $this->container->make(RedirectUnauthenticatedHandler::class, [
                    'options' => $options
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $unauthenticatedHandler = $this->container->make($customConfig->class(), $customConfig->args());
                if (!$unauthenticatedHandler instanceof UnauthenticatedHandlerInterface) {
                    throw new \InvalidArgumentException(sprintf('Unauthenticated Handler "%s" must implement %s', $customConfig->class(), UnauthenticatedHandlerInterface::class));
                }

                return $unauthenticatedHandler;

            default:
                throw new \InvalidArgumentException(sprintf('Unsupported unauthenticated handler type: %s', $type));
        }
    }
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\UnauthenticatedHandler;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Config\UnauthenticatedHandlerConfig;
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
        private ContainerInterface  $container,
    ) {}

    public function create(UnauthenticatedHandlerConfig $unauthenticatedHandlerConfig): UnauthenticatedHandlerInterface
    {
        $type = $unauthenticatedHandlerConfig->type();
        $options = $unauthenticatedHandlerConfig->options();

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
                    throw new \InvalidArgumentException(sprintf('%s must implement %s', $customConfig->class(), UnauthenticatedHandlerInterface::class));
                }

                return $unauthenticatedHandler;

            default:
                throw new \InvalidArgumentException("不支持的未认证处理器类型: $type");
        }
    }
}

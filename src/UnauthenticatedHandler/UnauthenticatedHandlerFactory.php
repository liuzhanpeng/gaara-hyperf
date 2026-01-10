<?php

declare(strict_types=1);

namespace GaaraHyperf\UnauthenticatedHandler;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\Config\CustomConfig;
use GaaraHyperf\UnauthenticatedHandler\DefaultUnauthenticatedHandler;
use GaaraHyperf\UnauthenticatedHandler\RedirectUnauthenticatedHandler;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerInterface;

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
                    'targetPath' => $options['target_path'] ?? '',
                    'redirectEnabled' => $options['redirect_enabled'] ?? true,
                    'redirectField' => $options['redirect_field'] ?? 'redirect_to',
                    'errorField' => $options['error_field'] ?? 'authentication_error',
                    'errorMessage' => $options['error_message'] ?? '未认证或已登出，请重新登录',
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $unauthenticatedHandler = $this->container->make($customConfig->class(), $customConfig->params());
                if (!$unauthenticatedHandler instanceof UnauthenticatedHandlerInterface) {
                    throw new \InvalidArgumentException(sprintf('Unauthenticated Handler "%s" must implement %s', $customConfig->class(), UnauthenticatedHandlerInterface::class));
                }

                return $unauthenticatedHandler;

            default:
                throw new \InvalidArgumentException(sprintf('Unsupported unauthenticated handler type: %s', $type));
        }
    }
}

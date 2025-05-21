<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfToken;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfTokenManager;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfTokenManagerInterface;

class CsrfTokenManagerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(array $config): CsrfTokenManagerInterface
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('csrf_token_manager配置必须是单个数组');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        switch ($type) {
            case 'default':
                return $this->container->get(CsrfTokenManager::class);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $csrfTokenManager = $this->container->get($customConfig->class(), $customConfig->args());
                if (!$csrfTokenManager instanceof CsrfTokenManagerInterface) {
                    throw new \InvalidArgumentException(sprintf('The custom CsrfTokenManager must implement %s.', CsrfTokenManagerInterface::class));
                }

                return $csrfTokenManager;
            default:
                throw new \InvalidArgumentException('csrf_token_manager配置类型错误');
        }
    }
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfToken;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\CsrfToken\CsrfTokenManagerInterface;

/**
 * CSRF令牌管理器工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CsrfTokenManagerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(array $config): CsrfTokenManagerInterface
    {
        if (count($config) !== 1) {
            throw new \InvalidArgumentException('csrf_token_manager config must be an associative array with a single key-value pair');
        }

        $type = array_key_first($config);
        $options = $config[$type];

        switch ($type) {
            case 'session':
                return $this->container->make(SessionCsrfTokenManager::class, [
                    'prefix' => $options['prefix'] ?? sprintf('%s:%s:', Constants::__PREFIX, 'csrf_token'),
                    'session' => $this->container->get(SessionInterface::class),
                ]);
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

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\CsrfTokenManager;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\CsrfTokenManager\CsrfTokenManagerInterface;

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
        $type = $config['type'] ?? 'session';
        unset($config['type']);

        switch ($type) {
            case 'session':
                return $this->container->make(SessionCsrfTokenManager::class, [
                    'prefix' => $config['prefix'] ?? sprintf('%s:%s:', Constants::__PREFIX, 'csrf_token'),
                    'session' => $this->container->get(SessionInterface::class),
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($config);

                $csrfTokenManager = $this->container->get($customConfig->class(), $customConfig->args());
                if (!$csrfTokenManager instanceof CsrfTokenManagerInterface) {
                    throw new \InvalidArgumentException(sprintf('The custom CsrfTokenManager must implement %s.', CsrfTokenManagerInterface::class));
                }

                return $csrfTokenManager;
            default:
                throw new \InvalidArgumentException("Unsupported CSRF Token Manager type: $type");
        }
    }
}

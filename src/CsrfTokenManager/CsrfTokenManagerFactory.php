<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

use GaaraHyperf\Config\CustomConfig;
use GaaraHyperf\Constants;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use LogicException;

/**
 * CSRF令牌管理器工厂
 */
class CsrfTokenManagerFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function create(array $config): CsrfTokenManagerInterface
    {
        $type = $config['type'] ?? 'session';
        unset($config['type']);

        switch ($type) {
            case 'session':
                return $this->container->make(SessionCsrfTokenManager::class, [
                    'prefix' => sprintf('%s.csrf_token.%s', Constants::__PREFIX, $config['prefix'] ?? 'default'),
                    'session' => $this->container->get(SessionInterface::class),
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($config);
                $csrfTokenManager = $this->container->make($customConfig->class(), $customConfig->params());
                if (! $csrfTokenManager instanceof CsrfTokenManagerInterface) {
                    throw new LogicException(sprintf('The custom CsrfTokenManager must implement %s.', CsrfTokenManagerInterface::class));
                }

                return $csrfTokenManager;
            default:
                throw new LogicException("Unsupported CSRF Token Manager type: {$type}");
        }
    }
}

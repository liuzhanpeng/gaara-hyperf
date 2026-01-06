<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

use GaaraHyperf\Config\ComponentConfig;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Contract\SessionInterface;
use GaaraHyperf\Config\CustomConfig;
use GaaraHyperf\Constants;
use GaaraHyperf\CsrfTokenManager\CsrfTokenManagerInterface;

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

    public function create(ComponentConfig $config): CsrfTokenManagerInterface
    {
        $type = $config->type();
        $options = $config->options();

        switch ($type) {
            case 'session':
                return $this->container->make(SessionCsrfTokenManager::class, [
                    'prefix' => sprintf('%s.csrf_token.%s', Constants::__PREFIX, $options['prefix'] ?? 'default'),
                    'session' => $this->container->get(SessionInterface::class),
                ]);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $csrfTokenManager = $this->container->make($customConfig->class(), $customConfig->params());
                if (!$csrfTokenManager instanceof CsrfTokenManagerInterface) {
                    throw new \InvalidArgumentException(sprintf('The custom CsrfTokenManager must implement %s.', CsrfTokenManagerInterface::class));
                }

                return $csrfTokenManager;
            default:
                throw new \InvalidArgumentException("Unsupported CSRF Token Manager type: $type");
        }
    }
}

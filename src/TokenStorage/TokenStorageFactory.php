<?php

declare(strict_types=1);

namespace GaaraHyperf\TokenStorage;

use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\Config\CustomConfig;
use GaaraHyperf\Constants;
use Hyperf\Contract\ContainerInterface;
use InvalidArgumentException;

/**
 * Token存储器服务工厂
 */
class TokenStorageFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function create(ComponentConfig $config): TokenStorageInterface
    {
        $type = $config->type();
        $options = $config->options();

        switch ($type) {
            case 'session':
                if (! isset($options['prefix']) || ! is_string($options['prefix'])) {
                    throw new InvalidArgumentException('The "prefix" option is required and must be a string for session token storage');
                }

                return $this->container->make(SessionTokenStorage::class, [
                    'prefix' => sprintf('%s:token_storage:%s', Constants::__PREFIX, $options['prefix']),
                ]);
            case 'null':
                return $this->container->make(NullTokenStorage::class);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $tokenStorage = $this->container->make($customConfig->class(), $customConfig->params());
                if (! $tokenStorage instanceof TokenStorageInterface) {
                    throw new InvalidArgumentException(sprintf('Token storage "%s" must implement %s', $customConfig->class(), TokenStorageInterface::class));
                }

                return $tokenStorage;
            default:
                throw new InvalidArgumentException(sprintf('Unsupported token storage type: %s', $type));
        }
    }
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\TokenStorage;

use Hyperf\Contract\ContainerInterface;
use GaaraHyperf\Config\ComponentConfig;
use GaaraHyperf\Config\CustomConfig;
use GaaraHyperf\Constants;
use GaaraHyperf\TokenStorage\NullTokenStorage;
use GaaraHyperf\TokenStorage\SessionTokenStorage;
use GaaraHyperf\TokenStorage\TokenStorageInterface;

/**
 * Token存储器服务工厂
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class TokenStorageFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function create(ComponentConfig $config): TokenStorageInterface
    {
        $type = $config->type();
        $options = $config->options();

        switch ($type) {
            case 'session':
                if (!isset($options['prefix']) || !is_string($options['prefix'])) {
                    throw new \InvalidArgumentException('The "prefix" option is required and must be a string for session token storage');
                }

                return $this->container->make(SessionTokenStorage::class, [
                    'prefix' => sprintf('%s:token_storage:%s', Constants::__PREFIX, $options['prefix']),
                ]);
            case 'null':
                return $this->container->make(NullTokenStorage::class);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $tokenStorage = $this->container->make($customConfig->class(), $customConfig->params());
                if (!$tokenStorage instanceof TokenStorageInterface) {
                    throw new \LogicException(sprintf('Token storage "%s" must implement %s', $customConfig->class(), TokenStorageInterface::class));
                }

                return $tokenStorage;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported token storage type: %s', $type));
        }
    }
}

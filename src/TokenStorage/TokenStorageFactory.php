<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\TokenStorage;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\ComponentConfig;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\TokenStorage\NullTokenStorage;
use Lzpeng\HyperfAuthGuard\TokenStorage\SessionTokenStorage;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;

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
                    'prefix' => sprintf('%s:%s:', Constants::__PREFIX, $options['prefix'])
                ]);
            case 'null':
                return $this->container->make(NullTokenStorage::class);
            case 'custom':
                $customConfig = CustomConfig::from($options);

                $tokenStorage = $this->container->make($customConfig->class(), $customConfig->args());
                if (!$tokenStorage instanceof TokenStorageInterface) {
                    throw new \LogicException(sprintf('Token storage "%s" must implement %s', $customConfig->class(), TokenStorageInterface::class));
                }

                return $tokenStorage;
            default:
                throw new \InvalidArgumentException(sprintf('Unsupported token storage type: %s', $type));
        }
    }
}

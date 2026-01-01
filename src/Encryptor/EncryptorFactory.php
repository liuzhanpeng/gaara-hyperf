<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Encryptor;

use Hyperf\Contract\ContainerInterface;
use Lzpeng\HyperfAuthGuard\Config\CustomConfig;

/**
 * 加密器工场
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class EncryptorFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function create(array $config): EncryptorInterface
    {
        $type = $config['type'] ?? 'default';
        unset($config['type']);

        switch ($type) {
            case 'default':
                if (!isset($config['key'])) {
                    throw new \InvalidArgumentException('Secret crypto key must be provided when secret_encrypto_enabled is true');
                }

                return new Encryptor($config['key'], $config['algo'] ?? 'AES-256-CBC');
            case 'custom':
                $customConfig = CustomConfig::from($config);

                $encryptor = $this->container->get($customConfig->class(), $customConfig->args());
                if (!$encryptor instanceof EncryptorInterface) {
                    throw new \LogicException(sprintf('The custom Encryptor must implement %s.', EncryptorInterface::class));
                }

                return $encryptor;
            default:
                throw new \InvalidArgumentException("Encryptor type does not exist: $type");
        }
    }
}

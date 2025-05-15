<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\Contract\ContainerInterface;

/**
 * 内置的认证器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatorResolver implements AuthenticatorResolverInterface
{
    /**
     * @param string[] $authenticatorIds
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $authenticatorIds,
        private ContainerInterface $container
    ) {}

    /**
     * @inheritDoc
     */
    public function getAuthenticatorIds(): array
    {
        return $this->authenticatorIds;
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $authenticatorId): AuthenticatorInterface
    {
        $authenticator = $this->container->get($authenticatorId);
        if (!$authenticator instanceof AuthenticatorInterface) {
            throw new \LogicException(sprintf('Authenticator "%s" must implement AuthenticatorInterface', $authenticatorId));
        }

        return $authenticator;
    }
}

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
     * @param array $authenticatorMap 结构: [guardName => [authenticatorId]]
     * @param ContainerInterface $container
     */
    public function __construct(
        private array $authenticatorMap,
        private ContainerInterface $container
    ) {}

    /**
     * @inheritDoc
     */
    public function getAuthenticatorIds(string $guardName): array
    {
        return $this->authenticatorMap[$guardName] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $authenticatorId): AuthenticatorInterface
    {
        $authenticator = $this->container->get($authenticatorId);
        if (!$authenticator instanceof AuthenticatorInterface) {
            throw new \InvalidArgumentException(sprintf('Authenticator "%s" must implement AuthenticatorInterface', $authenticatorId));
        }

        return $authenticator;
    }
}

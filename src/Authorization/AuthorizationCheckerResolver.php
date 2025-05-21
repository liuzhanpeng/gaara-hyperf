<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authorization;

use Hyperf\Contract\ContainerInterface;

/**
 * 内置的授权检查器解析器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthorizationCheckerResolver implements AuthorizationCheckerResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
        private array $authorizationCheckerMap,
    ) {}

    /**
     * @inheritDoc
     *
     * @param string $guardName
     * @return AuthorizationCheckerInterface
     */
    public function resolve(string $guardName): AuthorizationCheckerInterface
    {
        if (!isset($this->authorizationCheckerMap[$guardName])) {
            throw  new \InvalidArgumentException(sprintf('The authorization checker for guard "%s" is not found.', $guardName));
        }

        $authorizationCheckerId = $this->authorizationCheckerMap[$guardName];
        $authorizationChecker = $this->container->get($authorizationCheckerId);
        if (!$authorizationChecker instanceof AuthorizationCheckerInterface) {
            throw new \InvalidArgumentException(sprintf('The authorization checker for guard "%s" must be an instance of "%s".', $guardName, AuthorizationCheckerInterface::class));
        }

        return $authorizationChecker;
    }
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Logout\LogoutHandlerResolverInterface;
use Lzpeng\HyperfAuthGuard\RquestMatcher\RequestMatcherResolverInteface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guard 管理器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardManager implements GuardManagerInterface
{
    /**
     * @param RequestMatcherResolverInteface $requestMatcherResolver
     * @param GuardResolverInterface $guardResolver
     */
    public function __construct(
        private RequestMatcherResolverInteface $requestMatcherResolver,
        private GuardResolverInterface $guardResolver,
        private LogoutHandlerResolverInterface $logoutHandlerResolver,
    ) {}

    /**
     * @inheritDoc
     */
    public function process(RequestInterface $request): ?ResponseInterface
    {
        foreach ($this->guardResolver->getGuardNames() as $guardName) {
            $matcher = $this->requestMatcherResolver->resolve($guardName);
            if (!$matcher->matches($request)) {
                continue;
            }

            $guard = $this->guardResolver->resolve($guardName);

            $response = $guard->authenticate($request);

            if (is_null($response)) {
                $logoutHandler = $this->logoutHandlerResolver->resolve($guardName);
                if ($logoutHandler->supports($request)) {
                    return $logoutHandler->handle($request);
                }
            }

            return $response;
        }

        return null;
    }
}

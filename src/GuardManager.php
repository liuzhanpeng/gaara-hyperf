<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Guard管理器
 * 
 * 用于管理和处理守卫相关的逻辑
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardManager
{
    /**
     * @param GuardResolverInterface $guardResolver
     */
    public function __construct(
        private GuardResolverInterface $guardResolver,
    ) {}

    /**
     * 处理请求
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function process(ServerRequestInterface $request): ?ResponseInterface
    {
        foreach ($this->guardResolver->getGuardNames() as $guardName) {
            $guard = $this->guardResolver->resolve($guardName);
            if (!$guard->supports($request)) {
                continue;
            }

            $response = $guard->authenticate($request);
            if ($response !== null) {
                return $response;
            }
        }

        return null;
    }
}

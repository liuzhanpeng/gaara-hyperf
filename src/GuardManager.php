<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Guard管理器
 * 
 * 负责管理和调度各个Guard来处理请求 
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class GuardManager
{
    /**
     * @param GuardResolver $guardResolver
     */
    public function __construct(
        private GuardResolver $guardResolver,
    ) {}

    /**
     * 处理请求
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function process(ServerRequestInterface $request): ?ResponseInterface
    {
        foreach ($this->guardResolver as $guard) {
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

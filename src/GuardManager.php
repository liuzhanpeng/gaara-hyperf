<?php

declare(strict_types=1);

namespace GaaraHyperf;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Guard管理器.
 *
 * 负责管理和调度各个Guard来处理请求
 */
class GuardManager
{
    public function __construct(
        private GuardResolver $guardResolver,
    ) {
    }

    /**
     * 处理请求
     */
    public function process(ServerRequestInterface $request): ?ResponseInterface
    {
        foreach ($this->guardResolver as $guard) {
            if (! $guard->supports($request)) {
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

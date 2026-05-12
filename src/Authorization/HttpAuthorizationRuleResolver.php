<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 基于HTTP请求解析授权规则.
 *
 * object: 请求路径
 * action: 请求方法
 */
class HttpAuthorizationRuleResolver implements AuthorizationRuleResolverInterface
{
    public function resolve(ServerRequestInterface $request): array
    {
        return [
            'object' => $request->getUri()->getPath(),
            'action' => strtoupper($request->getMethod()),
        ];
    }
}

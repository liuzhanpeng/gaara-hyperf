<?php

declare(strict_types=1);

namespace GaaraHyperf\Authorization;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 授权规则解析器接口.
 */
interface AuthorizationRuleResolverInterface
{
    /**
     * 从请求中解析授权对象与动作.
     *
     * @return array{object: mixed, action: mixed}
     */
    public function resolve(ServerRequestInterface $request): array;
}

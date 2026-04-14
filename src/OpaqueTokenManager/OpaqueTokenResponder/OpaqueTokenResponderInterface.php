<?php

declare(strict_types=1);

namespace GaaraHyperf\OpaqueTokenManager\OpaqueTokenResponder;

use GaaraHyperf\OpaqueTokenManager\OpaqueToken;
use Psr\Http\Message\ResponseInterface;

/**
 * 不透明令牌响应器接口.
 */
interface OpaqueTokenResponderInterface
{
    /**
     * 创建并返回不透明令牌响应.
     */
    public function respond(OpaqueToken $opaqueToken): ResponseInterface;
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\AccessTokenExtractor;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 访问令牌提取器接口.
 */
interface AccessTokenExtractorInterface
{
    /**
     * 从请求中提取access token字符串.
     */
    public function extract(ServerRequestInterface $request): ?string;
}

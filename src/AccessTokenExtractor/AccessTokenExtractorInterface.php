<?php

declare(strict_types=1);

namespace GaaraHyperf\AccessTokenExtractor;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 访问令牌提取器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AccessTokenExtractorInterface
{
    /**
     * 从请求中提取access token字符串
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function extract(ServerRequestInterface $request): ?string;
}

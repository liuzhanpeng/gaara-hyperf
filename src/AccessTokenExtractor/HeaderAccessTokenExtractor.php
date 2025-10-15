<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\AccessTokenExtractor;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 从请求头中提取访问令牌的提取器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class HeaderAccessTokenExtractor implements AccessTokenExtractorInterface
{
    /**
     * @param string $param
     * @param string $type
     */
    public function __construct(
        private string $param = 'Authorization',
        private string $type = 'Bearer',
    ) {}

    /**
     * @inheritDoc
     */
    public function extractAccessToken(ServerRequestInterface $request): ?string
    {
        if (!$request->hasHeader($this->param) || !\is_string($header = $request->getHeaderLine($this->param))) {
            return null;
        }

        $regex = \sprintf(
            '/^%s([a-zA-Z0-9\-_\+~\/\.]+=*)$/',
            '' === $this->type ? '' : preg_quote($this->type) . '\s+'
        );

        if (preg_match($regex, $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

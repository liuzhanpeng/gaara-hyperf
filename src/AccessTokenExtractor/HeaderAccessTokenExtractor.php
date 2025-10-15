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
        private string $paramName = 'Authorization',
        private string $paramType = 'Bearer',
    ) {}

    /**
     * @inheritDoc
     */
    public function extractAccessToken(ServerRequestInterface $request): ?string
    {
        if (!$request->hasHeader($this->paramName) || !\is_string($header = $request->getHeaderLine($this->paramName))) {
            return null;
        }

        $regex = \sprintf(
            '/^%s([a-zA-Z0-9\-_\+~\/\.]+=*)$/',
            '' === $this->paramType ? '' : preg_quote($this->paramType) . '\s+'
        );

        if (preg_match($regex, $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

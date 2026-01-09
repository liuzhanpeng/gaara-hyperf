<?php

declare(strict_types=1);

namespace GaaraHyperf\AccessTokenExtractor;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 从 Body 中提取访问令牌的提取器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class BodyAccessTokenExtractor implements AccessTokenExtractorInterface
{
    /**
     * @param string $param Body 参数名称
     */
    public function __construct(
        private string $paramName = 'access_token',
    ) {}

    /**
     * @inheritDoc
     */
    public function extract(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody) || !isset($parsedBody[$this->paramName])) {
            return null;
        }

        $token = $parsedBody[$this->paramName];
        if (!\is_string($token) || empty($token)) {
            return null;
        }

        // 验证 token 格式
        if (preg_match('/^[a-zA-Z0-9\-_\+~\/\.]+=*$/', $token)) {
            return $token;
        }

        return null;
    }
}

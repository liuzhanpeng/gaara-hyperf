<?php

declare(strict_types=1);

namespace GaaraHyperf\AccessTokenExtractor;

use Psr\Http\Message\ServerRequestInterface;

/**
 * 从请求头中提取访问令牌的提取器.
 */
class HeaderAccessTokenExtractor implements AccessTokenExtractorInterface
{
    public function __construct(
        private string $field,
        private string $scheme,
    ) {
    }

    public function extract(ServerRequestInterface $request): ?string
    {
        if (! $request->hasHeader($this->field) || ! \is_string($header = $request->getHeaderLine($this->field))) {
            return null;
        }

        $regex = \sprintf(
            '/^%s([a-zA-Z0-9\-_\+~\/\.]+=*)$/',
            $this->scheme === '' ? '' : preg_quote($this->scheme) . '\s+'
        );

        if (preg_match($regex, $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

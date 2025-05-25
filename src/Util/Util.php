<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Util;

use Psr\Http\Message\ServerRequestInterface;

class Util
{
    /**
     * 判断是JSON请求
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function expectJson(ServerRequestInterface $request): bool
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        return str_starts_with($contentType, 'application/json');
    }
}

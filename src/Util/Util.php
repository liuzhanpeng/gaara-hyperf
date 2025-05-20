<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Util;

use Hyperf\HttpServer\Contract\RequestInterface;

class Util
{
    /**
     * 判断是JSON请求
     *
     * @param RequestInterface $request
     * @return boolean
     */
    public function expectJson(RequestInterface $request): bool
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        return str_starts_with($contentType, 'application/json');
    }
}

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
        $acceptHeader = $request->getHeaderLine('Accept');
        if (empty($acceptHeader) || $acceptHeader === '*/*') {
            return false;
        }

        $parts = explode(',', $acceptHeader);
        foreach ($parts as $part) {
            $part = trim($part);
            $part = explode(';', $part, 2)[0];
            $part = trim($part);
            if (stripos($part, 'application/json') === 0) {
                return true;
            }
        }

        return false;
    }
}

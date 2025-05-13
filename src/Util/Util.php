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

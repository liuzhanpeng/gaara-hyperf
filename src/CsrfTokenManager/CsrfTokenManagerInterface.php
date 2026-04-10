<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

/**
 * CsrfToken管理器接口.
 */
interface CsrfTokenManagerInterface
{
    /**
     * 生成CsrfToken.
     */
    public function generate(string $tokenId = 'authenticate'): CsrfToken;

    /**
     * 验证CsrfToken.
     */
    public function verify(CsrfToken $token): bool;
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

/**
 * CsrfToken管理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface CsrfTokenManagerInterface
{
    /**
     * 生成CsrfToken
     *
     * @param string $tokenId
     * @return CsrfToken
     */
    public function generate(string $tokenId = 'authenticate'): CsrfToken;

    /**
     * 验证CsrfToken
     *
     * @param CsrfToken $token
     * @return boolean
     */
    public function verify(CsrfToken $token): bool;
}

<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Token;

/**
 * 已认证成功令牌
 * 
 * 只有持有这个令牌才表示最终认证成功
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatedToken extends AbstractToken
{
    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'guardName' => $this->guardName,
            'userIdentifier' => $this->userIdentifier,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->guardName = $data['guardName'];
        $this->userIdentifier = $data['userIdentifier'];
        $this->attributes = $data['attributes'];
    }
}

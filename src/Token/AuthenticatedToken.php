<?php

declare(strict_types=1);

namespace GaaraHyperf\Token;

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
            'guard_name' => $this->guardName,
            'user_identifier' => $this->userIdentifier,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->guardName = $data['guard_name'];
        $this->userIdentifier = $data['user_identifier'];
        $this->attributes = $data['attributes'];
    }
}

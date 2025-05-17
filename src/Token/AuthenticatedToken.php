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
class AuthenticatedToken extends AbstractToken {}

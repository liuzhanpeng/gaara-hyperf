<?php

declare(strict_types=1);

namespace GaaraHyperf;

use function Hyperf\Support\make;

/**
 * 获取认证上下文
 * 
 * @return AuthContext
 */
function auth(): AuthContext
{
    return make(AuthContext::class);
}

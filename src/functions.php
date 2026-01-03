<?php

declare(strict_types=1);

namespace GaaraHyperf;

use function Hyperf\Support\make;

/**
 * @return AuthContext
 */
function auth(): AuthContext
{
    return make(AuthContext::class);
}

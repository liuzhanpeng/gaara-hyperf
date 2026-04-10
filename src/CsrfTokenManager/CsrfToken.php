<?php

declare(strict_types=1);

namespace GaaraHyperf\CsrfTokenManager;

use SensitiveParameter;
use Stringable;

/**
 * CSRF令牌.
 */
class CsrfToken implements Stringable
{
    public function __construct(
        private string $id,
        #[SensitiveParameter]
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * 返回ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 返回值
     */
    public function getValue(): string
    {
        return $this->value;
    }
}

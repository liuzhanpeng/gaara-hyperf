<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\User;

interface DisabledUserInterface
{
    /**
     * 是否已禁用
     *
     * @return boolean
     */
    public function disabled(): bool;
}

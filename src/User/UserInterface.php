<?php

declare(strict_types=1);

namespace GaaraHyperf\User;

/**
 * 用户接口.
 */
interface UserInterface
{
    /**
     * 返回用户标识符.
     */
    public function getIdentifier(): string;
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\Exception;

use Exception;
use GaaraHyperf\Token\TokenInterface;

/**
 * 访问被拒绝异常.
 */
class AccessDeniedException extends Exception
{
    public function __construct(
        private TokenInterface $token,
        private mixed $attribute,
        private mixed $resource = null
    ) {
        parent::__construct('access denied');
    }

    /**
     * 返回用户令牌.
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * 返回访问属性.
     */
    public function getAttribute(): mixed
    {
        return $this->attribute;
    }

    /**
     * 返回访问主体.
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }
}

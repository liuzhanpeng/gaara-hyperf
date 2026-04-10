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
    /**
     * @param TokenInterface $token 用户令牌
     * @param array $attribute 访问属性
     * @param mixed $subject 访问主体
     */
    public function __construct(
        private TokenInterface $token,
        private array|string $attribute = [],
        private mixed $subject = null
    ) {
        parent::__construct();
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
    public function getAttribute(): array|string
    {
        return $this->attribute;
    }

    /**
     * 返回访问主体.
     */
    public function getSubject(): mixed
    {
        return $this->subject;
    }
}

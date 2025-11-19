<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 访问被拒绝异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AccessDeniedException extends \Exception
{
    /**
     * @param TokenInterface $token 用户令牌
     * @param array $attribute 访问属性
     * @param mixed $subject 访问主体
     */
    public function __construct(
        private TokenInterface $token,
        private string|array $attribute = [],
        private mixed $subject = null
    ) {
        parent::__construct();
    }

    /**
     * 返回用户令牌
     *
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * 返回访问属性
     *
     * @return string|array
     */
    public function getAttribute(): string|array
    {
        return $this->attribute;
    }

    /**
     * 返回访问主体
     *
     * @return mixed
     */
    public function getSubject(): mixed
    {
        return $this->subject;
    }
}

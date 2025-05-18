<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Exception;

use Lzpeng\HyperfAuthGuard\Token\TokenInterface;

/**
 * 拒绝访问异常
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AccessDeniedException extends \RuntimeException
{
    /**
     * 用户令牌
     *
     * @var TokenInterface|null
     */
    private ?TokenInterface $token = null;

    /**
     * @var string|array
     */
    private string|array $attribute = [];

    /**
     * @var mixed
     */
    private mixed $subject = null;

    /**
     * @param TokenInterface|null $token
     * @param string|array $attribute
     * @param mixed $subject
     * @return self
     */
    public static function from(?TokenInterface $token, string|array $attribute, mixed $subject): self
    {
        $self = new self('Forbidden', 403);
        $self->token = $token;
        $self->attribute = $attribute;
        $self->subject = $subject;

        return $self;
    }
}

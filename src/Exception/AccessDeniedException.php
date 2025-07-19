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
    private ?TokenInterface $token = null;
    private string|array $attribute = [];
    private mixed $subject = null;

    public function __construct(
        ?TokenInterface $token = null,
        string|array $attribute = [],
        mixed $subject = null
    ) {
        parent::__construct('Forbidden', 403);
        $this->token = $token;
        $this->attribute = $attribute;
        $this->subject = $subject;
    }

    public function getToken(): ?TokenInterface
    {
        return $this->token;
    }

    public function getAttribute(): string|array
    {
        return $this->attribute;
    }

    public function getSubject(): mixed
    {
        return $this->subject;
    }

    public function getDisplayMessage(): string
    {
        return '访问被拒绝';
    }
}

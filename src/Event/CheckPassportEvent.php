<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Passport\Passport;

/**
 * Passport 检查事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CheckPassportEvent
{
    /**
     * @param AuthenticatorInterface $authenticator 不同的认证方法需要不同的验证方式，因此将Authenticator作为参数
     * @param Passport $passport
     */
    public function __construct(
        private AuthenticatorInterface $authenticator,
        private Passport $passport,
    ) {}

    /**
     * 返回认证器
     *
     * @return AuthenticatorInterface
     */
    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }

    /**
     * 返回认证通行证
     *
     * @return Passport
     */
    public function getPassport(): Passport
    {
        return $this->passport;
    }
}

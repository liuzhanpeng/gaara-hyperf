<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Passport 检查事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class CheckPassportEvent
{
    /**
     * @param string $guardName 认证守卫名称
     * @param AuthenticatorInterface $authenticator 不同的认证方法需要不同的验证方式，因此将Authenticator作为参数
     * @param Passport $passport 认证通行证
     * @param ServerRequestInterface $request 请求对象
     */
    public function __construct(
        private string $guardName,
        private AuthenticatorInterface $authenticator,
        private Passport $passport,
        private ServerRequestInterface $request,
    ) {}

    /**
     * 返回认证守卫名称
     *
     * @return string
     */
    public function getGuardName(): string
    {
        return $this->guardName;
    }

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

    /**
     * 返回请求对象
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}

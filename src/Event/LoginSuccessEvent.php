<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 登录成功事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginSuccessEvent
{
    /**
     * @param string $guardName 认证守卫名称
     * @param AuthenticatorInterface $authenticator 认证器
     * @param Passport $passport 认证通行证
     * @param TokenInterface $token 认证令牌
     * @param ServerRequestInterface $request 请求对象
     * @param ResponseInterface|null $response 响应对象
     * @param TokenInterface|null $previousToken 上一个认证令牌
     */
    public function __construct(
        private string $guardName,
        private AuthenticatorInterface $authenticator,
        private Passport $passport,
        private TokenInterface $token,
        private ServerRequestInterface $request,
        private ?ResponseInterface $response,
        private ?TokenInterface $previousToken
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
     * 返回认证令牌
     * 
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * 返回请求
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * 返回响应
     *
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * 设置响应
     *
     * @param ResponseInterface|null $response
     * @return void
     */
    public function setResponse(?ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * 返回上一次认证的token
     *
     * @return TokenInterface|null
     */
    public function getPreviousToken(): ?TokenInterface
    {
        return $this->previousToken;
    }
}

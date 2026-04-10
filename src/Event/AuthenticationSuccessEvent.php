<?php

declare(strict_types=1);

namespace GaaraHyperf\Event;

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证成功事件.
 */
class AuthenticationSuccessEvent
{
    /**
     * @param string $guardName 认证守卫名称
     * @param AuthenticatorInterface $authenticator 认证器
     * @param TokenInterface $token 认证令牌
     * @param Passport $passport 认证通行证
     * @param ServerRequestInterface $request 请求对象
     * @param null|ResponseInterface $response 响应对象
     * @param null|TokenInterface $previousToken 上一个认证令牌
     */
    public function __construct(
        private string $guardName,
        private AuthenticatorInterface $authenticator,
        private TokenInterface $token,
        private Passport $passport,
        private ServerRequestInterface $request,
        private ?ResponseInterface $response,
        private ?TokenInterface $previousToken
    ) {
    }

    /**
     * 返回认证守卫名称.
     */
    public function getGuardName(): string
    {
        return $this->guardName;
    }

    /**
     * 返回认证器.
     */
    public function getAuthenticator(): AuthenticatorInterface
    {
        return $this->authenticator;
    }

    /**
     * 返回认证令牌.
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * 设置认证令牌.
     */
    public function setToken(TokenInterface $token): void
    {
        $this->token = $token;
    }

    /**
     * 返回认证通行证
     */
    public function getPassport(): Passport
    {
        return $this->passport;
    }

    /**
     * 返回请求
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * 返回响应.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * 设置响应.
     */
    public function setResponse(?ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * 返回上一次认证的token.
     */
    public function getPreviousToken(): ?TokenInterface
    {
        return $this->previousToken;
    }
}

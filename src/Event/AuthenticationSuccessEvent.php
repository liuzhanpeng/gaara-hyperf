<?php

declare(strict_types=1);

namespace GaaraHyperf\Event;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证成功事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationSuccessEvent
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
     * 设置认证令牌
     *
     * @param TokenInterface $token
     * @return void
     */
    public function setToken(TokenInterface $token): void
    {
        $this->token = $token;
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

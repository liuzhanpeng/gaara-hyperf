<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Hyperf\HttpServer\Contract\RequestInterface;
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
     * @param AuthenticatorInterface $authenticator
     * @param Passport $passport
     * @param TokenInterface $token
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param TokenInterface|null $previousToken
     */
    public function __construct(
        private AuthenticatorInterface $authenticator,
        private Passport $passport,
        private TokenInterface $token,
        private RequestInterface $request,
        private ?ResponseInterface $response,
        private ?TokenInterface $previousToken
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
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
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

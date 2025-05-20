<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Psr\Http\Message\ResponseInterface;

/**
 * 登录失败事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LoginFailureEvent
{
    /**
     * @param AuthenticatorInterface $authenticator
     * @param Passport|null $passport
     * @param AuthenticationException $exception
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     */
    public function __construct(
        private AuthenticatorInterface $authenticator,
        private ?Passport $passport,
        private AuthenticationException $exception,
        private RequestInterface $request,
        private ?ResponseInterface $response
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
     * 返回认证异常
     *
     * @return AuthenticationException
     */
    public function getException(): AuthenticationException
    {
        return $this->exception;
    }

    /**
     * 返回认证通行证
     *
     * @return Passport|null
     */
    public function getPassport(): ?Passport
    {
        return $this->passport;
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
}

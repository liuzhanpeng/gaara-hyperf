<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证失败事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationFailureEvent
{
    /**
     * @param AuthenticatorInterface $authenticator
     * @param Passport $passport
     * @param AuthenticationException $exception
     * @param ServerRequestInterface $request
     * @param ResponseInterface|null $response
     */
    public function __construct(
        private AuthenticatorInterface $authenticator,
        private Passport $passport,
        private AuthenticationException $exception,
        private ServerRequestInterface $request,
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
}

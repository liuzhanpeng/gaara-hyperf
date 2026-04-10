<?php

declare(strict_types=1);

namespace GaaraHyperf\Event;

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Passport\Passport;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证失败事件.
 */
class AuthenticationFailureEvent
{
    /**
     * @param string $guardName 认证守卫名称
     * @param AuthenticatorInterface $authenticator 认证器
     * @param AuthenticationException $exception 认证异常
     * @param null|Passport $passport 认证通行证
     * @param ServerRequestInterface $request 请求对象
     * @param null|ResponseInterface $response 响应对象
     */
    public function __construct(
        private string $guardName,
        private AuthenticatorInterface $authenticator,
        private AuthenticationException $exception,
        private ?Passport $passport,
        private ServerRequestInterface $request,
        private ?ResponseInterface $response
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
     * 返回认证异常.
     */
    public function getException(): AuthenticationException
    {
        return $this->exception;
    }

    /**
     * 返回认证通行证
     */
    public function getPassport(): ?Passport
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
}

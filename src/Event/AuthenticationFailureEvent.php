<?php

declare(strict_types=1);

namespace GaaraHyperf\Event;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Passport\Passport;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证失败事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticationFailureEvent
{
    /**
     * @param string $guardName 认证守卫名称
     * @param AuthenticatorInterface $authenticator 认证器
     * @param AuthenticationException $exception 认证异常
     * @param Passport|null $passport 认证通行证
     * @param ServerRequestInterface $request 请求对象
     * @param ResponseInterface|null $response 响应对象
     */
    public function __construct(
        private string $guardName,
        private AuthenticatorInterface $authenticator,
        private AuthenticationException $exception,
        private ?Passport $passport,
        private ServerRequestInterface $request,
        private ?ResponseInterface $response
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

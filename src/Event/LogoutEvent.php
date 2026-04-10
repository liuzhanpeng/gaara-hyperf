<?php

declare(strict_types=1);

namespace GaaraHyperf\Event;

use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 登出事件.
 */
class LogoutEvent
{
    private ?ResponseInterface $response = null;

    public function __construct(
        private TokenInterface $token,
        private ServerRequestInterface $request,
    ) {
    }

    /**
     * 返回用户令牌.
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
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
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}

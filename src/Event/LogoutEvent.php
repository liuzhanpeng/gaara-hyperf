<?php

declare(strict_types=1);

namespace GaaraHyperf\Event;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 登出事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutEvent
{
    /**
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $response = null;

    /**
     * @param TokenInterface $token
     * @param ServerRequestInterface $request
     */
    public function __construct(
        private TokenInterface $token,
        private ServerRequestInterface $request,
    ) {}

    /**
     * 返回用户令牌
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
     * @param ResponseInterface $response
     * @return void
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}

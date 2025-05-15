<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 登出事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class LogoutEvent implements EventInterface
{
    /**
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $response = null;

    /**
     * @param string $guardName
     * @param TokenInterface $token
     * @param RequestInterface $request
     */
    public function __construct(
        private string $guardName,
        private TokenInterface $token,
        private RequestInterface $request,
    ) {}

    /**
     * @inheritDoc
     */
    public function getGuardName(): string
    {
        return $this->guardName;
    }

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
     * @param ResponseInterface $response
     * @return void
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}

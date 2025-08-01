<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Event;

use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 用户认证令牌已创建事件
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class AuthenticatedTokenCreatedEvent
{
    /**
     * @param string $guardName 认证守卫名称
     * @param Passport $passport 可能需要Passport的信息对Token进行修改
     * @param TokenInterface $token 认证令牌
     * @param ServerRequestInterface $request 请求对象
     */
    public function __construct(
        private string $guardName,
        private Passport $passport,
        private TokenInterface $token,
        private ServerRequestInterface $request
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
     * 返回Passport
     *
     * @return Passport
     */
    public function getPassport(): Passport
    {
        return $this->passport;
    }

    /**
     * 返回Token
     *
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * 设置Token
     * 
     * 认证成功前可能对Token进行了修改，添加一些额外信息
     *
     * @param TokenInterface $token
     * @return void
     */
    public function setToken(TokenInterface $token): void
    {
        $this->token = $token;
    }

    /**
     * 返回请求对象
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}

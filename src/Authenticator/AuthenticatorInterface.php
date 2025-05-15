<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证器接口
 * 
 * 通过实现此接口可以实现自定义的认证逻辑
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticatorInterface
{
    /**
     * 判断请求是否需要进行认证
     * 
     * 返回ture时会调用 authenticate 方法进行认证，否则不进行认证
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function supports(ServerRequestInterface $request): bool;

    /**
     * 对请求进行认证
     *
     * @param ServerRequestInterface $request
     * @return Passport
     */
    public function authenticate(ServerRequestInterface $request): Passport;

    /**
     * 创建token
     *
     * @param Passport $passport
     * @param string $guardName
     * @return TokenInterface
     */
    public function createToken(Passport $passport, string $guardName): TokenInterface;

    /**
     * 认证成功处理函数
     *
     * @param ServerRequestInterface $request
     * @param TokenInterface $token
     * @return ResponseInterface|null
     */
    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface;

    /**
     * 认证失败处理函数
     *
     * @param ServerRequestInterface $request
     * @param AuthenticationException $exception
     * @return ResponseInterface|null
     */
    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface;
}

<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Passport\Passport;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证失败处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticationFailureHandlerInterface
{
    /**
     * @param string $guardName
     * @param ServerRequestInterface $request
     * @param AuthenticationException $exception
     * @param Passport|null $passport
     * @return ResponseInterface
     */
    public function handle(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ResponseInterface;
}

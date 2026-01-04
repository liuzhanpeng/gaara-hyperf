<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Passport\Passport;
use Psr\Http\Message\ServerRequestInterface;
use GaaraHyperf\Token\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证成功处理器接口
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
interface AuthenticationSuccessHandlerInterface
{
    /**
     * @param string $guardName
     * @param ServerRequestInterface $request
     * @param TokenInterface $token
     * @param Passport $passport
     * @return ResponseInterface|null
     */
    public function handle(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface;
}

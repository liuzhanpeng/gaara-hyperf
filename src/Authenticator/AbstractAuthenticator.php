<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\UnauthenticatedException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenInterface;
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 抽象认证器.
 */
abstract class AbstractAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        protected ?AuthenticationSuccessHandlerInterface $successHandler,
        protected ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
    }

    /**
     * 创建认证令牌.
     */
    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUser()->getIdentifier());
    }

    /**
     * 认证成功处理.
     */
    public function onAuthenticationSuccess(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        if (! is_null($this->successHandler)) {
            return $this->successHandler->handle($guardName, $request, $token, $passport);
        }

        return null;
    }

    /**
     * 认证失败处理.
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (! is_null($this->failureHandler)) {
            return $this->failureHandler->handle($guardName, $request, $exception, $passport);
        }

        if (! $this->isInteractive() && $exception instanceof UnauthenticatedException) {
            $response = new Response();
            return $response->withStatus(401)->withBody(new SwooleStream($exception->getMessage()));
        }

        throw $exception;
    }
}

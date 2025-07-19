<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Psr\Http\Message\ServerRequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\InvalidCredentialsException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * JSON登录认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private array $options,
    ) {
        if (!isset($options['check_path'])) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }

        $this->options = array_merge([
            'username_param' => 'username',
            'password_param' => 'password',
        ], $options);
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        return str_starts_with($contentType, 'application/json')
            && $request->getUri()->getPath() === $this->options['check_path']
            && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request, string $guardName): Passport
    {
        $credientials = $this->getCredentials($request);

        $passport = new Passport(
            $guardName,
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
            [
                new PasswordBadge($credientials['password']),
            ]
        );

        return $passport;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        throw $exception;
    }

    /**
     * @inheritDoc
     */
    public function isInteractive(): bool
    {
        return true;
    }

    /**
     * 获取认证凭证
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    private function getCredentials(ServerRequestInterface $request): array
    {
        $credientials = [];
        $username = $request->getParsedBody()[$this->options['username_param']] ?? '';
        if (!is_string($username) || empty($username)) {
            throw new InvalidCredentialsException('username must be string');
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->options['password_param']] ?? '';
        if (!is_string($password) || empty($password)) {
            throw new InvalidCredentialsException('password must be string');
        }
        $credientials['password'] = trim($password);

        return $credientials;
    }
}

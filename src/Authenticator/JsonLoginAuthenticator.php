<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Lzpeng\HyperfAuthGuard\Util\Util;
use Psr\Http\Message\ResponseInterface;

/**
 * 用于API登录认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class JsonLoginAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private ?AuthenticationSuccessHandlerInterface $successHandler = null,
        private ?AuthenticationFailureHandlerInterface $failureHandler = null,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private Util $util,
        private array $options
    ) {
        $this->options = array_merge([
            'check_path' => '/login',
            'username_parameter' => 'username',
            'password_parameter' => 'password',
            'success_handler' => null,
            'failure_handler' => null,
        ], $this->options);
    }

    /**
     * @inheritDoc
     */
    public function supports(RequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->options['check_path']
            && $this->util->expectJson($request)
            && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(RequestInterface $request): Passport
    {
        $credientials = $this->getCredentials($request);

        $passport = new Passport(
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
            [
                new PasswordBadge($credientials['password']),
            ]
        );

        return $passport;
    }


    public function createToken(Passport $passport, string $guardName): TokenInterface
    {
        return new AuthenticatedToken($guardName, $passport->getUser());
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(RequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (is_null($this->successHandler)) {
            return null;
        }

        return $this->successHandler->handle($request, $token);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(RequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (is_null($this->failureHandler)) {
            return $this->response->json([
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->failureHandler->handle($request, $exception);
    }

    /**
     * 获取认证凭证
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getCredentials(RequestInterface $request): array
    {
        return [
            'username' => $request->input($this->options['username_parameter']),
            'password' => $request->input($this->options['password_parameter'])
        ];
    }
}

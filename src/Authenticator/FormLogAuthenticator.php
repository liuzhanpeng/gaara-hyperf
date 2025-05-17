<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Session\Session;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 表单登录认证器
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class FormLogAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private string $checkPath,
        private string $successPath,
        private string $failurePath,
        private string $usernameParam,
        private string $passwordParam,
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private SessionInterface $session
    ) {}

    /**
     * @inheritDoc
     */
    public function supports(RequestInterface $request): bool
    {
        return $request->getPathInfo() === $this->checkPath
            && $request->isMethod('POST');
    }

    /**
     * @inheritDoc
     */
    public function authenticate(RequestInterface $request, string $guardName): Passport
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
    public function onAuthenticationSuccess(RequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        return $this->response->redirect($this->successPath);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(RequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        if ($this->session instanceof Session) {
            $this->session->flash('authentication_error', $exception->getMessage());
        }

        return $this->response->redirect($this->failurePath);
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
            'username' => $request->post($this->usernameParam),
            'password' => $request->post($this->passwordParam),
        ];
    }
}

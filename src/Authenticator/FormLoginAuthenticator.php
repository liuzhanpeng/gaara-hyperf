<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Closure;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCredentialsException;
use GaaraHyperf\Passport\CsrfTokenBadge;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Hyperf\Contract\SessionInterface;
use Hyperf\Session\Session;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 表单登录认证器.
 *
 * 基于Session的有状态认证
 */
class FormLoginAuthenticator extends AbstractAuthenticator
{
    /**
     * @param callable|string $errorMessage
     */
    public function __construct(
        private string $checkPath,
        private string $targetPath,
        private string $failurePath,
        private string $usernameField,
        private string $passwordField,
        private bool $redirectEnabled,
        private string $redirectField,
        private bool $csrfEnabled,
        private string $csrfField,
        private string $csrfId,
        private Closure|string $errorMessage,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private SessionInterface $session,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
        if (empty($this->checkPath)) {
            throw new InvalidArgumentException('The "check_path" option must be set.');
        }
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->checkPath
            && $request->getMethod() === 'POST';
    }

    public function authenticate(ServerRequestInterface $request): Passport
    {
        $credientials = $this->getCredentials($request);

        if ($this->csrfEnabled && empty($credientials['csrf_token'])) {
            throw new InvalidCredentialsException(
                message: 'CSRF token is missing',
                userIdentifier: $credientials['username']
            );
        }

        $passport = new Passport(
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
            [
                new PasswordBadge($credientials['password']),
            ]
        );

        if ($this->csrfEnabled) {
            $passport->addBadge(new CsrfTokenBadge(
                $this->csrfId,
                $credientials['csrf_token']
            ));
        }

        return $passport;
    }

    /**
     * @override
     */
    public function onAuthenticationSuccess(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        $this->session->migrate(true);

        if (! is_null($this->successHandler)) {
            return $this->successHandler->handle($guardName, $request, $token, $passport);
        }

        $redirectTo = $request->getParsedBody()[$this->redirectField] ?? null;
        if ($this->redirectEnabled && ! is_null($redirectTo)) {
            return $this->response->redirect(urldecode($redirectTo));
        }

        return $this->response->redirect($this->targetPath);
    }

    /**
     * @override
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (! is_null($this->failureHandler)) {
            return $this->failureHandler->handle($guardName, $request, $exception, $passport);
        }

        if ($this->session instanceof Session) {
            if (is_callable($this->errorMessage)) {
                $msg = ($this->errorMessage)($exception);
            } else {
                $msg = $this->errorMessage;
            }

            $this->session->flash('authentication_error', $msg);
        }

        return $this->response->redirect($this->failurePath);
    }

    public function isInteractive(): bool
    {
        return true;
    }

    /**
     * 获取认证凭证
     */
    private function getCredentials(ServerRequestInterface $request): array
    {
        $credientials = [];
        $username = $request->getParsedBody()[$this->usernameField] ?? '';
        if (! is_string($username) || empty($username)) {
            throw new InvalidCredentialsException(
                message: 'Username is missing',
            );
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->passwordField] ?? '';
        if (! is_string($password) || empty($password)) {
            throw new InvalidCredentialsException(
                message: 'Password is missing',
                userIdentifier: $username
            );
        }
        $credientials['password'] = trim($password);

        $credientials['csrf_token'] = $request->getParsedBody()[$this->csrfField] ?? '';

        return $credientials;
    }
}

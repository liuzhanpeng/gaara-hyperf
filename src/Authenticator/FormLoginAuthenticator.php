<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Hyperf\Contract\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Session\Session;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Exception\InvalidCsrfTokenException;
use GaaraHyperf\Exception\InvalidPasswordException;
use GaaraHyperf\Exception\UserNotFoundException;
use GaaraHyperf\Passport\CsrfTokenBadge;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\Passport\PasswordBadge;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 表单登录认证器
 * 
 * 基于Session的有状态认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class FormLoginAuthenticator extends AbstractAuthenticator
{
    /**
     * @param string $checkPath
     * @param string $usernameField
     * @param string $passwordField
     * @param bool $csrfEnabled
     * @param string $csrfField
     * @param string $csrfId
     * @param bool $redirectEnabled
     * @param string $redirectField
     * @param string $targetPath
     * @param string $failurePath
     * @param string|callable $errorMessage
     * @param UserProviderInterface $userProvider
     * @param \Hyperf\HttpServer\Contract\ResponseInterface $response
     * @param SessionInterface $session
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
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
        private string|\Closure $errorMessage,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private SessionInterface $session,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
        if (empty($this->checkPath)) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->checkPath
            && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $credientials = $this->getCredentials($request);

        if ($this->csrfEnabled && empty($credientials['csrf_token'])) {
            throw new InvalidCsrfTokenException(
                message: 'CSRF token is missing',
                userIdentifier: $this->usernameField,
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
     * @inheritDoc
     * @override
     */
    public function onAuthenticationSuccess(string $guardName, ServerRequestInterface $request, TokenInterface $token, Passport $passport): ?ResponseInterface
    {
        $this->session->migrate(true);

        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($guardName, $request, $token, $passport);
        }

        $redirectTo = $request->getParsedBody()[$this->redirectField] ?? null;
        if ($this->redirectEnabled && !is_null($redirectTo)) {
            return $this->response->redirect(urldecode($redirectTo));
        }

        return $this->response->redirect($this->targetPath);
    }

    /**
     * @inheritDoc
     * @override
     */
    public function onAuthenticationFailure(string $guardName, ServerRequestInterface $request, AuthenticationException $exception, ?Passport $passport = null): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
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
        $username = $request->getParsedBody()[$this->usernameField] ?? '';
        if (!is_string($username) || empty($username)) {
            throw new UserNotFoundException(
                message: 'Username is missing',
                userIdentifier: $username,
            );
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->passwordField] ?? '';
        if (!is_string($password) || empty($password)) {
            throw new InvalidPasswordException(
                message: 'Password is missing',
                userIdentifier: $username
            );
        }
        $credientials['password'] = trim($password);

        $credientials['csrf_token'] = $request->getParsedBody()[$this->csrfField] ?? '';

        return $credientials;
    }
}

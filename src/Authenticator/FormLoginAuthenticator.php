<?php

declare(strict_types=1);

namespace GaaraHyperf\Authenticator;

use Hyperf\Contract\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Session\Session;
use GaaraHyperf\Exception\AuthenticationException;
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
     * @param UserProviderInterface $userProvider
     * @param \Hyperf\HttpServer\Contract\ResponseInterface $response
     * @param SessionInterface $session
     * @param array $options
     * @param AuthenticationSuccessHandlerInterface|null $successHandler
     * @param AuthenticationFailureHandlerInterface|null $failureHandler
     */
    public function __construct(
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private SessionInterface $session,
        private array $options,
        ?AuthenticationSuccessHandlerInterface $successHandler,
        ?AuthenticationFailureHandlerInterface $failureHandler,
    ) {
        parent::__construct($successHandler, $failureHandler);
    }

    /**
     * @inheritDoc
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $request->getUri()->getPath() === $this->options['check_path']
            && $request->getMethod() === 'POST';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(ServerRequestInterface $request): Passport
    {
        $credientials = $this->getCredentials($request);

        if ($this->options['csrf_enabled'] && empty($credientials['csrf_token'])) {
            throw new AuthenticationException($this->options['username_param'], 'CSRF token is missing');
        }

        $passport = new Passport(
            $credientials['username'],
            $this->userProvider->findByIdentifier(...),
            [
                new PasswordBadge($credientials['password']),
            ]
        );

        if ($this->options['csrf_enabled']) {
            $passport->addBadge(new CsrfTokenBadge(
                $this->options['csrf_id'],
                $credientials['csrf_token']
            ));
        }

        return $passport;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        $this->session->migrate(false);

        if (!is_null($this->successHandler)) {
            return $this->successHandler->handle($request, $token);
        }

        $redirectTo = $request->getParsedBody()[$this->options['redirect_param']] ?? null;
        if ($this->options['redirect_enabled'] && !is_null($redirectTo)) {
            return $this->response->redirect(urldecode($redirectTo));
        }

        return $this->response->redirect($this->options['target_path']);
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        if (!is_null($this->failureHandler)) {
            return $this->failureHandler->handle($request, $exception);
        }

        if ($this->session instanceof Session) {
            if (is_callable($this->options['error_message'])) {
                $msg = ($this->options['error_message'])($exception);
            } else {
                $msg = $this->options['error_message'];
            }

            $this->session->flash('authentication_error', $msg);
        }

        return $this->response->redirect($this->options['failure_path']);
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
            throw new UserNotFoundException();
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->options['password_param']] ?? '';
        if (!is_string($password) || empty($password)) {
            throw new InvalidPasswordException($username);
        }
        $credientials['password'] = trim($password);

        $credientials['csrf_token'] = $request->getParsedBody()[$this->options['csrf_param']] ?? '';

        return $credientials;
    }
}

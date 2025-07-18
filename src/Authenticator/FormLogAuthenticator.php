<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\Contract\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Session\Session;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\InvalidCredentialsException;
use Lzpeng\HyperfAuthGuard\Passport\CsrfTokenBadge;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 表单登录认证器
 * 
 * 基于Session的有状态认证
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class FormLogAuthenticator extends AbstractAuthenticator
{
    /**
     * @param string $options 配置
     * @param AuthenticationSuccessHandlerInterface|null $successHandler 登录成功处理器
     * @param AuthenticationFailureHandlerInterface|null $failureHandler 登录失败处理器
     * @param UserProviderInterface $userProvider 用户提供者
     * @param \Hyperf\HttpServer\Contract\ResponseInterface $response
     * @param SessionInterface $session
     */
    public function __construct(
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private SessionInterface $session,
        private array $options,
    ) {}

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

        if ($this->options['csrf_enabled'] && empty($credientials['csrf_token'])) {
            throw AuthenticationException::from('CSRF token is missing.');
        }

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
            $this->session->flash('authentication_error', $exception->getMessage());
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
            throw new InvalidCredentialsException('username must be string.');
        }
        $credientials['username'] = trim($username);

        $password = $request->getParsedBody()[$this->options['password_param']] ?? '';
        if (!is_string($password) || empty($password)) {
            throw new InvalidCredentialsException('password must be string.');
        }
        $credientials['password'] = trim($password);

        $credientials['csrf_token'] = $request->getParsedBody()[$this->options['csrf_param']] ?? '';

        return $credientials;
    }
}

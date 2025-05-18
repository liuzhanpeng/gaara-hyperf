<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard\Authenticator;

use Hyperf\Contract\SessionInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Session\Session;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\InvalidCredentialsException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Passport\PasswordBadge;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\UserProvider\UserProviderInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 表单登录认证器
 * 
 * 基于Session
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
        private array $options,
        private ?AuthenticationSuccessHandlerInterface $successHandler,
        private ?AuthenticationFailureHandlerInterface $failureHandler,
        private UserProviderInterface $userProvider,
        private \Hyperf\HttpServer\Contract\ResponseInterface $response,
        private SessionInterface $session
    ) {
        if (!isset($options['check_path'])) {
            throw new \InvalidArgumentException('The "check_path" option must be set.');
        }

        $this->options = array_merge([
            'target_path' => '/',
            'failure_path' => $options['check_path'],
            'use_redirect_path' => true,
            'redirect_path_param' => '_redirect_to',
            'username_param' => 'username',
            'password_param' => 'password',
        ], $options);
    }

    /**
     * @inheritDoc
     */
    public function supports(RequestInterface $request): bool
    {
        return $request->getPathInfo() === $this->options['check_path']
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

        if ($this->options['use_redirect_path'] && $request->has($this->options['redirect_path_param'])) {
            return $this->response->redirect($request->query($this->options['redirect_path_param']));
        }

        return $this->response->redirect($this->options['target_path']);
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

        return $this->response->redirect($this->options['failure_path']);
    }

    /**
     * 获取认证凭证
     *
     * @param RequestInterface $request
     * @return array
     */
    private function getCredentials(RequestInterface $request): array
    {
        $credientials = [];
        $username = $request->post($this->options['username_param'], '');
        if (!is_string($username) || empty($username)) {
            throw new InvalidCredentialsException('username must be string.');
        }
        $credientials['username'] = trim($username);

        $password = $request->post($this->options['password_param'], '');
        if (!is_string($password) || empty($password)) {
            throw new InvalidCredentialsException('password must be string.');
        }
        $credientials['password'] = trim($password);

        return $credientials;
    }
}

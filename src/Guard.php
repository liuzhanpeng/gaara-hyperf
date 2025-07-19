<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Constants;
use Lzpeng\HyperfAuthGuard\Event\AuthenticatedTokenCreatedEvent;
use Lzpeng\HyperfAuthGuard\Event\AuthenticationFailureEvent;
use Lzpeng\HyperfAuthGuard\Event\AuthenticationSuccessEvent;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Event\LogoutEvent;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\InvalidCredentialsException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\RequestMatcher\RequestMatcherInterface;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use Lzpeng\HyperfAuthGuard\User\UserInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证守卫
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class Guard implements GuardInterface
{
    /**
     * @param string $name
     * @param RequestMatcherInterface $requestMatcher
     * @param TokenStorageInterface $tokenStorage
     * @param TokenContextInterface $tokenContext
     * @param array<string, AuthenticatorInterface> $authenticators
     * @param UnauthenticatedHandlerInterface $unauthenticatedHandler
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param AccessDeniedHandlerInterface $accessDeniedHandler
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private string $name,
        private RequestMatcherInterface $requestMatcher,
        private TokenStorageInterface $tokenStorage,
        private TokenContextInterface $tokenContext,
        private array $authenticators,
        private UnauthenticatedHandlerInterface $unauthenticatedHandler,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AccessDeniedHandlerInterface $accessDeniedHandler,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function supports(ServerRequestInterface $request): bool
    {
        return $this->requestMatcher->matchesPattern($request);
    }

    public function authenticateUser(UserInterface $user, ServerRequestInterface $request, ?string $authenticator = null, array $badges = []): ?ResponseInterface
    {
        $passport = new Passport($this->name, $user->getIdentifier(), fn() => $user, $badges);
        $authenticator = $this->resolveAuthenticator($authenticator);

        return $this->executeAuthenticator($authenticator, $request, $passport);
    }

    /**
     * 解析认证器
     *
     * @param string|null $authenticator
     * @return AuthenticatorInterface|null
     */
    private function resolveAuthenticator(?string $authenticator): ?AuthenticatorInterface
    {
        if ($authenticator === null) {
            return $this->authenticators[0] ?? null;
        }

        return $this->authenticators[$authenticator] ?? null;
    }

    public function authenticate(ServerRequestInterface $request): ?ResponseInterface
    {
        $token = $this->tokenStorage->get($this->name);
        $this->tokenContext->setToken($token);

        if ($this->requestMatcher->matchesExcluded($request)) {
            return null;
        }

        foreach ($this->authenticators as $authenticator) {
            if (!$authenticator->supports($request)) {
                continue;
            }

            $response = $this->executeAuthenticator($authenticator, $request);
            if ($response !== null) {
                return $response;
            }
        }

        // 未经过认证器认证 或 认证器处理认证逻辑后继续放行
        $token = $this->tokenContext->getToken();
        if ($token === null || !$token instanceof AuthenticatedToken) {
            return $this->unauthenticatedHandler->handle($request, $token);
        }

        // 处理注销请求
        if ($this->requestMatcher->matchesLogout($request)) {
            return $this->logout($token, $request);
        }

        // 授权检查
        return $this->checkAuthorization($request, $token);
    }

    /**
     * 执行指定的认证器
     *
     * @param AuthenticatorInterface $authenticator
     * @param ServerRequestInterface $request
     * @param Passport|null $passport
     * @return ResponseInterface|null
     */
    private function executeAuthenticator(AuthenticatorInterface $authenticator, ServerRequestInterface $request, ?Passport $passport = null): ?ResponseInterface
    {
        try {
            if ($passport === null) {
                $passport = $authenticator->authenticate($request, $this->name);
            }

            $checkPassportEvent = new CheckPassportEvent($authenticator, $passport);
            $this->eventDispatcher->dispatch($checkPassportEvent);

            foreach ($passport->getBadges() as $badge) {
                if (!$badge->isResolved()) {
                    throw AuthenticationException::from('Credential not resolved', $passport->getUser()->getIdentifier());
                }
            }

            $token = $authenticator->createToken($passport, $this->name);
            $token = $this->eventDispatcher->dispatch(new AuthenticatedTokenCreatedEvent($passport, $token))->getToken();

            return $this->handleAuthenticationSuccess($request, $authenticator, $passport, $token);
        } catch (AuthenticationException $exception) {
            return $this->handleAuthenticationFailure($request, $authenticator, $exception, $passport);
        }
    }

    /**
     * 认证成功处理函数
     *
     * @param ServerRequestInterface $request
     * @param AuthenticatorInterface $authenticator
     * @param TokenInterface $token
     * @return ResponseInterface|null
     */
    private function handleAuthenticationSuccess(ServerRequestInterface $request, AuthenticatorInterface $authenticator, Passport $passport, TokenInterface $token): ?ResponseInterface
    {
        $previousToken = $this->tokenContext->getToken();
        $this->tokenContext->setToken($token);
        if ($authenticator->isInteractive()) {
            $this->tokenStorage->set($this->name, $token);
        }

        $response = $authenticator->onAuthenticationSuccess($request, $token);

        $authenticationSuccess = new AuthenticationSuccessEvent(
            $authenticator,
            $passport,
            $token,
            $request,
            $response,
            $previousToken
        );

        $this->eventDispatcher->dispatch($authenticationSuccess);

        return $authenticationSuccess->getResponse();
    }

    /**
     * 认证失败处理函数
     *
     * @param ServerRequestInterface $request
     * @param AuthenticatorInterface $authenticator
     * @param AuthenticationException $exception
     * @param Passport|null $passport
     * @return ResponseInterface|null
     */
    private function handleAuthenticationFailure(ServerRequestInterface $request, AuthenticatorInterface $authenticator,  AuthenticationException $exception, ?Passport $passport): ?ResponseInterface
    {
        $response = $authenticator->onAuthenticationFailure($request, $exception, $passport);

        $authenticationFailureEvent = new AuthenticationFailureEvent(
            $authenticator,
            $passport,
            $exception,
            $request,
            $response
        );

        $this->eventDispatcher->dispatch($authenticationFailureEvent);

        return $authenticationFailureEvent->getResponse();
    }

    /**
     * 授权检查
     *
     * @param ServerRequestInterface $request
     * @param TokenInterface $token
     * @return ResponseInterface|null
     */
    private function checkAuthorization(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        $attribute = $request->getAttribute(Constants::REQUEST_AUTHORIZATION_ATTRIBUTE, '');
        $subject = $request->getAttribute(Constants::REQUEST_AUTHORIZATION_SUBJECT, null);
        if (!$this->authorizationChecker->check($token, $attribute, $subject)) {
            return $this->accessDeniedHandler->handle($request, $token, $attribute, $subject);
        }

        return null;
    }

    /**
     * 检查令牌所属用户是否具有指定的权限
     *
     * @param TokenInterface $token
     * @param string|array $attribute
     * @param mixed $subject
     * @return boolean
     */
    public function isGranted(TokenInterface $token, string|array $attribute, mixed $subject = null): bool
    {
        return $this->authorizationChecker->check($token, $attribute, $subject);
    }

    /**
     * 注销
     *
     * @param TokenInterface|null $token
     * @param ServerRequestInterface|null $request
     * @return ResponseInterface|null
     */
    public function logout(?TokenInterface $token = null, ?ServerRequestInterface $request = null): ?ResponseInterface
    {
        if ($token === null) {
            $token = $this->tokenContext->getToken();
        }

        if ($token === null) {
            return null;
        }

        $logoutEvent = new LogoutEvent($token, $request);
        $this->eventDispatcher->dispatch($logoutEvent);

        $this->tokenStorage->delete($token->getGuardName());
        $this->tokenContext->setToken(null);

        return $logoutEvent->getResponse();
    }
}

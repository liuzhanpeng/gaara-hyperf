<?php

declare(strict_types=1);

namespace GaaraHyperf;

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authorization\AccessDeniedHandlerInterface;
use GaaraHyperf\Authorization\AuthorizationCheckerInterface;
use GaaraHyperf\Constants;
use GaaraHyperf\Event\AuthenticationFailureEvent;
use GaaraHyperf\Event\AuthenticationSuccessEvent;
use GaaraHyperf\Event\CheckPassportEvent;
use GaaraHyperf\Event\LogoutEvent;
use GaaraHyperf\Exception\AuthenticationException;
use GaaraHyperf\Passport\Passport;
use GaaraHyperf\RequestMatcher\RequestMatcherInterface;
use GaaraHyperf\Token\AuthenticatedToken;
use GaaraHyperf\Token\TokenContextInterface;
use GaaraHyperf\Token\TokenInterface;
use GaaraHyperf\TokenStorage\TokenStorageInterface;
use GaaraHyperf\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use GaaraHyperf\User\UserInterface;
use GaaraHyperf\UserProvider\UserProviderInterface;
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
     * @param UserProviderInterface $userProvider
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
        private UserProviderInterface $userProvider,
        private array $authenticators,
        private UnauthenticatedHandlerInterface $unauthenticatedHandler,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AccessDeniedHandlerInterface $accessDeniedHandler,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * 返回认证守卫名称
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * 返回用户提供器
     *
     * @return UserProviderInterface
     */
    public function getUserProvider(): UserProviderInterface
    {
        return $this->userProvider;
    }

    /**
     * 检查请求是否匹配当前守卫
     *
     * @param ServerRequestInterface $request
     * @return boolean
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $this->requestMatcher->matchesPattern($request);
    }

    /**
     * 认证用户
     *
     * @param UserInterface $user
     * @param ServerRequestInterface $request
     * @param string|null $authenticator
     * @param array $badges
     * @return ResponseInterface|null
     */
    public function authenticateUser(UserInterface $user, ServerRequestInterface $request, ?string $authenticator = null, array $badges = []): ?ResponseInterface
    {
        $passport = new Passport($user->getIdentifier(), fn() => $user, $badges);
        $authenticator = $this->resolveAuthenticator($authenticator);

        return $this->executeAuthenticator($authenticator, $request, $passport);
    }

    /**
     * 解析认证器
     *
     * @param string|null $authenticator
     * @return AuthenticatorInterface
     */
    private function resolveAuthenticator(?string $authenticator): AuthenticatorInterface
    {
        if ($authenticator === null) {
            return $this->authenticators[0] ?? throw new \RuntimeException('No authenticator configured for guard ' . $this->name);
        }

        return $this->authenticators[$authenticator] ?? throw new \RuntimeException('Authenticator "' . $authenticator . '" not found for guard ' . $this->name);
    }

    /**
     * 认证请求
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function authenticate(ServerRequestInterface $request): ?ResponseInterface
    {
        $token = $this->tokenStorage->get($this->name);
        $this->tokenContext->setToken($token);

        // 在设置token上下文后再检查请求是否被排除是为了可以以在排除逻辑中使用token信息
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

        // 认证器处理认证逻辑后继续放行
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

            $checkPassportEvent = new CheckPassportEvent($this->name, $authenticator, $passport, $request);
            $this->eventDispatcher->dispatch($checkPassportEvent);

            foreach ($passport->getBadges() as $badge) {
                if (!$badge->isResolved()) {
                    throw new AuthenticationException(
                        message: 'Credential not resolved',
                        userIdentifier: $passport->getUser()->getIdentifier(),
                    );
                }
            }

            $token = $authenticator->createToken($passport, $this->name);

            return $this->handleAuthenticationSuccess($request, $authenticator, $passport, $token);
        } catch (AuthenticationException $exception) {
            return $this->handleAuthenticationFailure($request, $authenticator, $passport, $exception);
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

        $response = $authenticator->onAuthenticationSuccess($this->name, $request, $token, $passport);

        $authenticationSuccessEvent = $this->eventDispatcher->dispatch(new AuthenticationSuccessEvent($this->name, $authenticator, $token, $passport, $request, $response, $previousToken));
        $token = $authenticationSuccessEvent->getToken();
        $response = $authenticationSuccessEvent->getResponse();

        $this->tokenContext->setToken($token);
        if ($authenticator->isInteractive()) {
            $this->tokenStorage->set($this->name, $token);
        }

        return $response;
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
    private function handleAuthenticationFailure(ServerRequestInterface $request, AuthenticatorInterface $authenticator, ?Passport $passport, AuthenticationException $exception): ?ResponseInterface
    {
        $response = $authenticator->onAuthenticationFailure($this->name, $request, $exception, $passport);
        $response = $this->eventDispatcher->dispatch(new AuthenticationFailureEvent($this->name, $authenticator, $exception, $passport, $request, $response))->getResponse();

        return $response;
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

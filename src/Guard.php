<?php

declare(strict_types=1);

namespace GaaraHyperf;

use GaaraHyperf\Authenticator\AuthenticatorInterface;
use GaaraHyperf\Authorization\AccessDeniedHandlerInterface;
use GaaraHyperf\Authorization\AuthorizationCheckerInterface;
use GaaraHyperf\Authorization\AuthorizationRuleResolverInterface;
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
use RuntimeException;

/**
 * 认证守卫.
 */
class Guard implements GuardInterface
{
    /**
     * @param array<string, AuthenticatorInterface> $authenticators
     */
    public function __construct(
        private string $name,
        private RequestMatcherInterface $requestMatcher,
        private TokenStorageInterface $tokenStorage,
        private TokenContextInterface $tokenContext,
        private UserProviderInterface $userProvider,
        private array $authenticators,
        private UnauthenticatedHandlerInterface $unauthenticatedHandler,
        private AuthorizationRuleResolverInterface $authorizationRuleResolver,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AccessDeniedHandlerInterface $accessDeniedHandler,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * 返回认证守卫名称.
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * 返回用户提供器.
     */
    public function getUserProvider(): UserProviderInterface
    {
        return $this->userProvider;
    }

    /**
     * 检查请求是否匹配当前守卫.
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return $this->requestMatcher->matchesPattern($request);
    }

    /**
     * 认证用户.
     */
    public function authenticateUser(UserInterface $user, ServerRequestInterface $request, ?string $authenticator = null, array $badges = []): ?ResponseInterface
    {
        $passport = new Passport($user->getIdentifier(), fn () => $user, $badges);
        $authenticator = $this->resolveAuthenticator($authenticator);

        return $this->executeAuthenticator($authenticator, $request, $passport);
    }

    /**
     * 认证请求
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
            if (! $authenticator->supports($request)) {
                continue;
            }

            $response = $this->executeAuthenticator($authenticator, $request);
            if ($response !== null) {
                return $response;
            }

            break;
        }

        // 认证器处理认证逻辑后继续放行
        $token = $this->tokenContext->getToken();
        if (! $this->isTokenAuthenticated($token)) {
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
     * 判断令牌是否已通过当前守卫的信任判定.
     */
    public function isTokenAuthenticated(?TokenInterface $token): bool
    {
        return $token instanceof AuthenticatedToken;
    }

    /**
     * 检查令牌所属用户是否具有指定的权限.
     */
    public function isGranted(TokenInterface $token, mixed $object, mixed $action = null): bool
    {
        return $this->authorizationChecker->check($token, $object, $action);
    }

    /**
     * 注销
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

    /**
     * 解析认证器.
     */
    private function resolveAuthenticator(?string $authenticator): AuthenticatorInterface
    {
        if ($authenticator === null) {
            return $this->authenticators[0] ?? throw new RuntimeException('No authenticator configured for guard ' . $this->name);
        }

        return $this->authenticators[$authenticator] ?? throw new RuntimeException('Authenticator "' . $authenticator . '" not found for guard ' . $this->name);
    }

    /**
     * 执行指定的认证器.
     */
    private function executeAuthenticator(AuthenticatorInterface $authenticator, ServerRequestInterface $request, ?Passport $passport = null): ?ResponseInterface
    {
        try {
            if ($passport === null) {
                $passport = $authenticator->authenticate($request);
            }

            $checkPassportEvent = new CheckPassportEvent($this->name, $authenticator, $passport, $request);
            $this->eventDispatcher->dispatch($checkPassportEvent);

            foreach ($passport->getBadges() as $badge) {
                if (! $badge->isResolved()) {
                    throw new AuthenticationException(
                        message: 'Credential not resolved',
                        userIdentifier: $passport->getUserIdentifier(),
                    );
                }
            }

            $passport->getUser(); // 确保用户存在
            $token = $authenticator->createToken($passport, $this->name);

            return $this->handleAuthenticationSuccess($request, $authenticator, $passport, $token);
        } catch (AuthenticationException $exception) {
            return $this->handleAuthenticationFailure($request, $authenticator, $passport, $exception);
        }
    }

    /**
     * 认证成功处理函数.
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
     * 认证失败处理函数.
     */
    private function handleAuthenticationFailure(ServerRequestInterface $request, AuthenticatorInterface $authenticator, ?Passport $passport, AuthenticationException $exception): ?ResponseInterface
    {
        $response = $authenticator->onAuthenticationFailure($this->name, $request, $exception, $passport);
        return $this->eventDispatcher->dispatch(new AuthenticationFailureEvent($this->name, $authenticator, $exception, $passport, $request, $response))->getResponse();
    }

    /**
     * 授权检查.
     */
    private function checkAuthorization(ServerRequestInterface $request, TokenInterface $token): ?ResponseInterface
    {
        ['object' => $object, 'action' => $action] = $this->authorizationRuleResolver->resolve($request);
        if (! $this->authorizationChecker->check($token, $object, $action)) {
            return $this->accessDeniedHandler->handle($request, $token, $object, $action);
        }

        return null;
    }
}

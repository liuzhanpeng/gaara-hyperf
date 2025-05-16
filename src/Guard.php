<?php

declare(strict_types=1);

namespace Lzpeng\HyperfAuthGuard;

use Hyperf\HttpServer\Contract\RequestInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorInterface;
use Lzpeng\HyperfAuthGuard\Authenticator\AuthenticatorResolverInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AccessDeniedHandlerInterface;
use Lzpeng\HyperfAuthGuard\Authorization\AuthorizationCheckerInterface;
use Lzpeng\HyperfAuthGuard\Event\AuthenticatedTokenCreatedEvent;
use Lzpeng\HyperfAuthGuard\Event\AuthenticationFailureEvent;
use Lzpeng\HyperfAuthGuard\Event\AuthenticationSuccessEvent;
use Lzpeng\HyperfAuthGuard\Event\CheckPassportEvent;
use Lzpeng\HyperfAuthGuard\Event\LoginFailureEvent;
use Lzpeng\HyperfAuthGuard\Event\LoginSuccessEvent;
use Lzpeng\HyperfAuthGuard\Exception\AccessDeniedException;
use Lzpeng\HyperfAuthGuard\Exception\AuthenticationException;
use Lzpeng\HyperfAuthGuard\Exception\UnauthenticatedException;
use Lzpeng\HyperfAuthGuard\Passport\Passport;
use Lzpeng\HyperfAuthGuard\Token\AuthenticatedToken;
use Lzpeng\HyperfAuthGuard\Token\TokenContextInterface;
use Lzpeng\HyperfAuthGuard\Token\TokenInterface;
use Lzpeng\HyperfAuthGuard\TokenStorage\TokenStorageInterface;
use Lzpeng\HyperfAuthGuard\UnauthenticatedHandler\UnauthenticatedHandlerInterface;
use Lzpeng\HyperfAuthGuard\User\UserInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 认证守卫
 * 
 * @author lzpeng <liuzhanpeng@gmail.com>
 */
class Guard implements GuardInterface
{
    public function __construct(
        private string $name,
        private AuthenticatorResolverInterface $authenticatorResolver,
        private TokenStorageInterface $tokenStorage,
        private TokenContextInterface $tokenContext,
        private UnauthenticatedHandlerInterface $unauthenticatedHandler,
        private AuthorizationCheckerInterface $authorizationChecker,
        private AccessDeniedHandlerInterface $accessDeniedHandler,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * 返回认证守卫名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function authenticateUser(UserInterface $user, AuthenticatorInterface $authenticator, RequestInterface $request, array $badges = []): ?ResponseInterface
    {
        $passport = new Passport($user->getIdentifier(), fn() => $user, $badges);
        $token = $authenticator->createToken($passport, $this->name);
        $token = $this->eventDispatcher->dispatch(new AuthenticatedTokenCreatedEvent($passport, $token))->getToken();

        return $this->handleAuthenticationSuccess($token, $request, $authenticator, $passport);
    }

    /**
     * @inheritDoc
     */
    public function authenticate(RequestInterface $request): ?ResponseInterface
    {
        $token = $this->tokenStorage->get($this->name);
        $this->tokenContext->setToken($token);

        foreach ($this->authenticatorResolver->getAuthenticatorIds() as $authenticatorId) {
            $authenticator = $this->authenticatorResolver->resolve($authenticatorId);
            if (!$authenticator->supports($request)) {
                continue;
            }

            $response = $this->executeAuthenticator($authenticator, $request);
            if (!is_null($response)) {
                return $response;
            }
        }

        $token = $this->tokenContext->getToken();
        if (is_null($token) || !$token instanceof AuthenticatedToken) {
            return $this->unauthenticatedHandler->handle($request, UnauthenticatedException::from($token));
        }

        $attribute = $request->getAttribute('authorization_attribute', '');
        $subject = $request->getAttribute('authorization_subject', null);
        $request->withoutAttribute('authorization_attribute')->withoutAttribute('authorization_subject');
        if (!$this->authorizationChecker->check($token, $attribute, $subject)) {
            return $this->accessDeniedHandler->handle($request, AccessDeniedException::from($token, $attribute, $subject));
        }

        return null;
    }

    /**
     * 执行指定的认证器认证逻辑
     *
     * @param AuthenticatorInterface $authenticator
     * @param RequestInterface $request
     * @return ResponseInterface|null
     */
    private function executeAuthenticator(AuthenticatorInterface $authenticator, RequestInterface $request): ?ResponseInterface
    {
        $passport = null;
        try {
            $passport = $authenticator->authenticate($request);
            $checkPassportEvent = new CheckPassportEvent($authenticator, $passport);
            $this->eventDispatcher->dispatch($checkPassportEvent);

            foreach ($passport->getBadges() as $badge) {
                if (!$badge->isResolved()) {
                    throw new AuthenticationException('Credential not resolved');
                }
            }

            $token = $authenticator->createToken($passport, $this->name);
            $token = $this->eventDispatcher->dispatch(new AuthenticatedTokenCreatedEvent($passport, $token))->getToken();

            $this->eventDispatcher->dispatch(new AuthenticationSuccessEvent($token));
        } catch (AuthenticationException $exception) {
            return $this->handleAuthenticationFailure($exception, $request, $authenticator, $passport);
        }

        return $this->handleAuthenticationSuccess($token, $request, $authenticator, $passport);
    }

    /**
     * 处理认证成功
     *
     * @param TokenInterface $token
     * @param RequestInterface $request
     * @param AuthenticatorInterface $authenticator
     * @param Passport $passport
     * @return ResponseInterface|null
     */
    public function handleAuthenticationSuccess(TokenInterface $token, RequestInterface $request, AuthenticatorInterface $authenticator, Passport $passport): ?ResponseInterface
    {
        $previousToken = $this->tokenContext->getToken();
        $this->tokenContext->setToken($token);
        $this->tokenStorage->set($this->name, $token);

        $response = $authenticator->onAuthenticationSuccess($request, $token);

        $loginSuccessEvent = new LoginSuccessEvent(
            $authenticator,
            $passport,
            $token,
            $request,
            $response,
            $previousToken
        );

        $this->eventDispatcher->dispatch($loginSuccessEvent);

        return $loginSuccessEvent->getResponse();
    }

    /**
     * 处理认证失败
     *
     * @param AuthenticationException $exception
     * @param RequestInterface $request
     * @param AuthenticatorInterface $authenticator
     * @param Passport|null $passport
     * @return ResponseInterface
     */
    private function handleAuthenticationFailure(AuthenticationException $exception, RequestInterface $request, AuthenticatorInterface $authenticator, ?Passport $passport): ?ResponseInterface
    {
        $response = $authenticator->onAuthenticationFailure($request, $exception, $passport);

        $loginFailureEvent = new LoginFailureEvent(
            $authenticator,
            $passport,
            $exception,
            $request,
            $response
        );

        $this->eventDispatcher->dispatch($loginFailureEvent);

        return $loginFailureEvent->getResponse();
    }
}
